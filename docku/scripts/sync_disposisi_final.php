<?php
/**
 * DOCKU SYNC DISPOSISI FINAL (Robust Version)
 * Implements: 
 * 1. UUID Gatekeeper (Skip null/empty)
 * 2. Idempotent Sync (ON DUPLICATE KEY UPDATE)
 * 3. Sync Quarantine (Log invalid data)
 * 4. Safe Sequential Ingestion
 */

require_once __DIR__ . '/../config/database.php';

// Configuration
$apiBaseUrl = 'https://api.sidiksae.my.id'; 
$apiKey = 'sk_live_camat_c4m4t2026';

if (!isset($pdo)) {
    die("❌ Error: DB connection failed.\n");
}

echo "=== SYNC DISPOSISI DOCKU FINAL ===\n";

// 0. Ensure Quarantine Table Exists
$pdo->exec("CREATE TABLE IF NOT EXISTS sync_quarantine (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    payload JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 1. Get Local Roles (Targets)
// Group by role to avoid redundant API calls
$stmt = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role != 'admin'");
$target_roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Found " . count($target_roles) . " unique roles to sync: " . implode(', ', $target_roles) . "\n";

$stats = ['total_items' => 0, 'synced' => 0, 'quarantined' => 0, 'errors' => 0];

foreach ($target_roles as $roleSlug) {
    echo ">> Syncing for Role: " . $roleSlug . "\n";
    
    // 2. Call API (New Role-Based Endpoint)
    // Mapping format: Uppercase with underscores (e.g. KASI_PEM)
    $apiRole = strtoupper(str_replace(' ', '_', $roleSlug));
    $endpoint = $apiBaseUrl . '/api/v1/disposisi/role/' . $apiRole;
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-KEY: ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "   ❌ API Fail ($httpCode)\n";
        continue;
    }
    
    $response = json_decode($resp, true);
    if (!isset($response['success']) || !$response['success']) {
        echo "   ⚠️ API Error for " . $user['nama'] . ": " . ($response['message'] ?? 'Unknown') . "\n";
        continue;
    }
    
    $items = $response['data'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }

    foreach ($items as $item) {
        $stats['total_items']++;
        
        // 3. GATEKEEPER: Check for NULL UUIDs
        if (empty($item['uuid_surat']) || empty($item['uuid'])) {
            $reason = empty($item['uuid']) ? "MISSING_DISPOSISI_UUID" : "MISSING_SURAT_UUID";
            echo "   📥 [QUARANTINE] $reason\n";
            
            $stmtQuar = $pdo->prepare("INSERT INTO sync_quarantine (source, reason, payload) VALUES (?, ?, ?)");
            $stmtQuar->execute(['API_DISPOSISI', $reason, json_encode($item)]);
            
            $stats['quarantined']++;
            continue;
        }

        try {
            $pdo->beginTransaction();
            
            // 4. STEP A: SYNC SURAT (Idempotent)
            $sqlSurat = "INSERT INTO surat (uuid, nomor_surat, perihal, asal_surat, tanggal_surat, created_at, updated_at) 
                         VALUES (:uuid, :nomor, :perihal, :asal, :tgl, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE 
                            nomor_surat = VALUES(nomor_surat),
                            perihal = VALUES(perihal),
                            asal_surat = VALUES(asal_surat),
                            tanggal_surat = VALUES(tanggal_surat),
                            updated_at = NOW()";
            
            $stmtSurat = $pdo->prepare($sqlSurat);
            $stmtSurat->execute([
                ':uuid'   => $item['uuid_surat'],
                ':nomor'  => $item['nomor_surat'] ?? '-',
                ':perihal' => $item['perihal'] ?? '-',
                ':asal'   => $item['asal_surat'] ?? '-',
                ':tgl'     => $item['tanggal_surat'] ?? date('Y-m-d')
            ]);
            
            // Get internal ID for Disposisi FK (if needed, though UUID is better)
            $stmtGetSurat = $pdo->prepare("SELECT id FROM surat WHERE uuid = ?");
            $stmtGetSurat->execute([$item['uuid_surat']]);
            $suratId = $stmtGetSurat->fetchColumn();

            // 5. STEP B: SYNC DISPOSISI (Idempotent)
            $instruksiText = "";
            if (!empty($item['instruksi'])) {
                if (is_array($item['instruksi'])) {
                    foreach ($item['instruksi'] as $ins) {
                        $instruksiText .= "- " . ($ins['isi'] ?? '') . "\n";
                    }
                } else {
                    $instruksiText = $item['instruksi'];
                }
            } else {
                $instruksiText = $item['catatan'] ?? '';
            }

            $sqlDisp = "INSERT INTO disposisi (uuid, uuid_surat, instruksi, tgl_disposisi, created_at, status_global) 
                        VALUES (:uuid, :uuid_surat, :instruksi, :tgl, NOW(), :stat)
                        ON DUPLICATE KEY UPDATE 
                            instruksi = VALUES(instruksi),
                            status_global = VALUES(status_global),
                            updated_at = NOW()";
            
            $stmtDisp = $pdo->prepare($sqlDisp);
            $stmtDisp->execute([
                ':uuid'       => $item['uuid'],
                ':uuid_surat' => $item['uuid_surat'],
                ':instruksi'  => trim($instruksiText),
                ':tgl'        => $item['created_at'],
                ':stat'       => $item['status_global'] ?? 'PROSES'
            ]);
            
            $stmtGetDisp = $pdo->prepare("SELECT id FROM disposisi WHERE uuid = ?");
            $stmtGetDisp->execute([$item['uuid']]);
            $dispId = $stmtGetDisp->fetchColumn();

            // 6. STEP C: SYNC PENERIMA (Idempotent)
            $statusP = ($item['status_personal'] === 'BARU') ? 'baru' : strtolower($item['status_personal'] ?? 'baru');
            
            // Find ALL local users with this role to distribute the disposition
            $stmtRoleUsers = $pdo->prepare("SELECT id FROM users WHERE role = ?");
            $stmtRoleUsers->execute([$roleSlug]);
            $localUserIds = $stmtRoleUsers->fetchAll(PDO::FETCH_COLUMN);

            foreach ($localUserIds as $localUid) {
                $sqlPenerima = "INSERT INTO disposisi_penerima (disposisi_id, disposisi_uuid, user_id, status, created_at, updated_at) 
                                VALUES (:did, :duuid, :uid, :stat, NOW(), NOW())
                                ON DUPLICATE KEY UPDATE 
                                    status = VALUES(status),
                                    updated_at = NOW()";
                
                $stmtPenerima = $pdo->prepare($sqlPenerima);
                $stmtPenerima->execute([
                    ':did'   => $dispId,
                    ':duuid' => $item['uuid'],
                    ':uid'   => $localUid,
                    ':stat'  => $statusP
                ]);
            }
            
            $pdo->commit();
            $stats['synced']++;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "   ❌ DB Error: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }
}

echo "\n=== SYNC SUMMARY ===\n";
echo "Total Items Found: " . $stats['total_items'] . "\n";
echo "Successfully Synced: " . $stats['synced'] . "\n";
echo "Quarantined (Invalid): " . $stats['quarantined'] . "\n";
echo "Failed (Errors): " . $stats['errors'] . "\n";
echo "====================\n";

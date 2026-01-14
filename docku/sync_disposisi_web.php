<?php
// docku/sync_disposisi_web.php
// WEB Accessible Sync Tool
// Usage: https://docku.sidiksae.my.id/sync_disposisi_web.php?key=StartSync2026

require_once 'config/database.php';

// Security Check
$key = $_GET['key'] ?? '';
if ($key !== 'StartSync2026') {
    die("⛔ Access Denied.");
}

// Configuration
$apiBaseUrl = 'https://api.sidiksae.my.id'; 
// Correct API Domain
$apiKey = 'sk_live_camat_c4m4t2026'; 
// $apiKey = 'sk_sync_docku_2026'; // DISABLED (Undefined Key) 

header('Content-Type: text/plain; charset=utf-8');
// Disable buffering
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
while (@ob_end_flush());
ini_set('implicit_flush', true);
ob_implicit_flush(true);

echo "=== 🔄 SYNC DOCKU DISPOSISI ===\n";
echo "Founding API: $apiBaseUrl\n\n";

try {
    // 1. Get Local Users with UUID
    // Removed is_active check as production DB lacks this column
    $stmt = $pdo->query("SELECT id, uuid, nama FROM users WHERE uuid IS NOT NULL");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "👥 Found " . count($users) . " active users with UUID.\n";

    // Ensure Quarantine Table Exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS sync_quarantine (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(50) NOT NULL,
        reason VARCHAR(255) NOT NULL,
        payload JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stats = ['total_items' => 0, 'synced' => 0, 'quarantined' => 0, 'errors' => 0];

    foreach ($users as $u) {
        if (empty($u['uuid'])) continue;
        
        echo "\n👤 Checking: " . $u['nama'] . " ... ";
        
        // 2. Call API
        $endpoint = "$apiBaseUrl/disposisi/penerima/" . $u['uuid'] . "?api_key=" . $apiKey;
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-API-KEY: $apiKey",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            echo "❌ API Fail ($httpCode)\n";
            $stats['errors']++;
            continue;
        }
        
        $items = json_decode($resp, true);
        if (!is_array($items)) {
            echo "❌ Invalid JSON\n";
            continue;
        }
        
        $count = count($items);
        echo "📥 $count pending items.\n";
        
        if ($count === 0) continue;
        
        // 3. Process Items
        foreach ($items as $item) {
            $stats['total_items']++;

            // GATEKEEPER: Check for NULL UUIDs
            if (empty($item['uuid_surat']) || empty($item['uuid'])) {
                $reason = empty($item['uuid']) ? "MISSING_DISPOSISI_UUID" : "MISSING_SURAT_UUID";
                echo "   📥 [QUARANTINE] $reason\n";
                
                $stmtQuar = $pdo->prepare("INSERT INTO sync_quarantine (source, reason, payload) VALUES (?, ?, ?)");
                $stmtQuar->execute(['API_WEB_SYNC', $reason, json_encode($item)]);
                
                $stats['quarantined']++;
                continue;
            }

            try {
                $pdo->beginTransaction();
                
                // A. Surat Upsert (Idempotent)
                $sqlS = "INSERT INTO surat (uuid, nomor_surat, perihal, asal_surat, tanggal_surat, created_at, updated_at) 
                         VALUES (:uuid, :nomor, :perihal, :asal, :tgl, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE 
                            nomor_surat = VALUES(nomor_surat),
                            perihal = VALUES(perihal),
                            asal_surat = VALUES(asal_surat),
                            tanggal_surat = VALUES(tanggal_surat),
                            updated_at = NOW()";
                
                $stmtInsS = $pdo->prepare($sqlS);
                $stmtInsS->execute([
                    ':uuid' => $item['uuid_surat'],
                    ':nomor' => $item['nomor_surat'] ?? '-',
                    ':perihal' => $item['perihal'] ?? '-',
                    ':asal' => $item['asal_surat'] ?? '-',
                    ':tgl' => $item['tanggal_surat'] ?? date('Y-m-d')
                ]);
                
                $stmtGetS = $pdo->prepare("SELECT id FROM surat WHERE uuid = ?");
                $stmtGetS->execute([$item['uuid_surat']]);
                $suratId = $stmtGetS->fetchColumn();
                
                // B. Disposisi Upsert (Idempotent)
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
                
                $sqlD = "INSERT INTO disposisi (uuid, uuid_surat, instruksi, tgl_disposisi, created_at, status_global) 
                         VALUES (:uuid, :uuid_surat, :instruksi, :tgl, NOW(), :stat)
                         ON DUPLICATE KEY UPDATE 
                            instruksi = VALUES(instruksi),
                            status_global = VALUES(status_global),
                            updated_at = NOW()";
                
                $stmtInsD = $pdo->prepare($sqlD);
                $stmtInsD->execute([
                    ':uuid' => $item['uuid'],
                    ':uuid_surat' => $item['uuid_surat'],
                    ':instruksi' => trim($instruksiText),
                    ':tgl' => $item['created_at'],
                    ':stat' => $item['status_global'] ?? 'PROSES'
                ]);
                
                $stmtGetD = $pdo->prepare("SELECT id FROM disposisi WHERE uuid = ?");
                $stmtGetD->execute([$item['uuid']]);
                $dispId = $stmtGetD->fetchColumn();
                
                // C. Link User (Idempotent)
                $statusP = ($item['status_personal'] === 'BARU') ? 'baru' : strtolower($item['status_personal'] ?? 'baru');
                
                $sqlDP = "INSERT INTO disposisi_penerima (disposisi_id, disposisi_uuid, user_id, status, created_at, updated_at) 
                           VALUES (:did, :duuid, :uid, :stat, NOW(), NOW())
                           ON DUPLICATE KEY UPDATE 
                                status = VALUES(status),
                                updated_at = NOW()";
                
                $stmtInsDP = $pdo->prepare($sqlDP);
                $stmtInsDP->execute([
                    ':did'   => $dispId,
                    ':duuid' => $item['uuid'],
                    ':uid'   => $u['id'], 
                    ':stat'  => $statusP
                ]);
                
                $pdo->commit();
                $stats['synced']++;
                
            } catch (Exception $ex) {
                $pdo->rollBack();
                echo "   ⚠️ DB Error: " . $ex->getMessage() . "\n";
                $stats['errors']++;
            }
        }
    }
    
    echo "\n=== 🎉 SYNC COMPLETE ===\n";
    echo "Total Items Found: " . $stats['total_items'] . "\n";
    echo "Successfully Synced: " . $stats['synced'] . "\n";
    echo "Quarantined (Invalid): " . $stats['quarantined'] . "\n";
    echo "Errors: " . $stats['errors'] . "\n";


} catch (Exception $e) {
    die("⛔ CRITICAL ERROR: " . $e->getMessage());
}

<?php
/**
 * DOCKU SYNC DISPOSISI (CLI Version)
 * Usage: php docku/scripts/sync_disposisi_api.php
 */

require_once __DIR__ . '/../config/database.php';

// Configuration
$apiBaseUrl = 'https://camat.sidiksae.my.id/api'; 
$apiKey = 'sk_sync_docku_2026';

if (!isset($pdo)) {
    die("❌ Error: DB connection failed.\n");
}

echo "=== SYNC DISPOSISI DOCKU (CLI) ===\n";

// 1. Get Local Users (Targets)
$stmt = $pdo->query("SELECT id, uuid, nama FROM users WHERE uuid IS NOT NULL AND is_active=1");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($users) . " users.\n";

$stats = ['synced' => 0, 'errors' => 0];

foreach ($users as $u) {
    if (empty($u['uuid'])) continue;
    
    echo ">> Checking: " . $u['nama'] . "\n";
    
    // 2. Call API
    $endpoint = $apiBaseUrl . '/disposisi/penerima/' . $u['uuid'];
    
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
    
    $items = json_decode($resp, true);
    if (!is_array($items)) continue;
    
    if (count($items) > 0) {
        echo "   📥 Processing " . count($items) . " items...\n";
    }

    foreach ($items as $item) {
        try {
            $pdo->beginTransaction();
            
            // A. Surat
            $stmtS = $pdo->prepare("SELECT id FROM surat WHERE uuid = ?");
            $stmtS->execute([$item['uuid_surat']]);
            $suratId = $stmtS->fetchColumn();
            
            if (!$suratId) {
                $sqlS = "INSERT INTO surat (uuid, nomor_surat, perihal, asal_surat, tanggal_surat, created_at, updated_at) 
                         VALUES (:uuid, :nomor, :perihal, :asal, :tgl, NOW(), NOW())";
                $stmtInsS = $pdo->prepare($sqlS);
                $stmtInsS->execute([
                    ':uuid' => $item['uuid_surat'],
                    ':nomor' => $item['nomor_surat'] ?? '-',
                    ':perihal' => $item['perihal'] ?? '-',
                    ':asal' => $item['asal_surat'] ?? '-',
                    ':tgl' => $item['tanggal_surat'] ?? date('Y-m-d')
                ]);
                $suratId = $pdo->lastInsertId();
            }
            
            // B. Disposisi
            $instruksiText = "";
            if (!empty($item['instruksi']) && is_array($item['instruksi'])) {
                foreach ($item['instruksi'] as $ins) {
                    $instruksiText .= "- " . ($ins['isi'] ?? '') . "\n";
                }
            } else {
                $instruksiText = $item['catatan'] ?? '';
            }
            
            $stmtD = $pdo->prepare("SELECT id FROM disposisi WHERE uuid = ?");
            $stmtD->execute([$item['uuid']]);
            $dispId = $stmtD->fetchColumn();
            
            if (!$dispId) {
                $sqlD = "INSERT INTO disposisi (uuid, uuid_surat, instruksi, tgl_disposisi, created_at, status_global) 
                         VALUES (:uuid, :uuid_surat, :instruksi, :tgl, NOW(), :stat)";
                $stmtInsD = $pdo->prepare($sqlD);
                $stmtInsD->execute([
                    ':uuid' => $item['uuid'],
                    ':uuid_surat' => $item['uuid_surat'],
                    ':instruksi' => trim($instruksiText),
                    ':tgl' => $item['created_at'],
                    ':stat' => $item['status_global']
                ]);
                $dispId = $pdo->lastInsertId();
            }
            
            // C. Link User (Legacy ID)
            $stmtDP = $pdo->prepare("SELECT id FROM disposisi_penerima WHERE disposisi_uuid = ? AND user_id = ?");
            $stmtDP->execute([$item['uuid'], $u['id']]); // Use ID logic
            $exists = $stmtDP->fetchColumn();
            
            if (!$exists) {
                 $statusP = ($item['status_personal'] === 'BARU') ? 'baru' : strtolower($item['status_personal']);
                 $sqlDP = "INSERT INTO disposisi_penerima (disposisi_id, disposisi_uuid, user_id, status, created_at, updated_at) 
                           VALUES (:did, :duuid, :uid, :stat, NOW(), NOW())";
                 $stmtInsDP = $pdo->prepare($sqlDP);
                 $stmtInsDP->execute([
                     ':did' => $dispId,
                     ':duuid' => $item['uuid'],
                     ':uid' => $u['id'], // INT ID
                     ':stat' => $statusP
                 ]);
            }
            
            $pdo->commit();
            $stats['synced']++;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "   ⚠️ DB Error: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }
}

echo "Done.\n";

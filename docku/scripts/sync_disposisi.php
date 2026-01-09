<?php
// /home/beni/projectku/kantor/docku/scripts/sync_disposisi.php
// RUN VIA CRON OR MANUAL TRIGGER

require_once __DIR__ . '/../config/database.php';

// 1. Get All Active Users
$stmt = $pdo->query("SELECT id, nama_lengkap FROM users WHERE status_aktif = 1");
$users = $stmt->fetchAll();

echo "Starting Sync for " . count($users) . " users...\n";

// PRODUCTION DOMAIN
$api_base = "https://api.sidiksae.my.id/api/disposisi/penerima/"; 
$api_key  = "sk_live_docku_x9y8z7w6v5u4t3s2"; // Adjust if needed

foreach ($users as $u) {
    $uid = $u['id'];
    echo "Syncing User ID: $uid ({$u['nama_lengkap']})... ";

    // 2. Call API
    $ch = curl_init($api_base . $uid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-KEY: $api_key",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "FAILED (HTTP $httpCode)\n";
        continue;
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['data'])) {
        echo "NO DATA/INVALID JSON\n";
        continue;
    }

    $count = 0;
    foreach ($data['data'] as $d) {
        // 3. Upsert Disposisi Local
        // Logic: Check by UUID. If exists, update status? Or ignore?
        // Rule: "Simpan ke DB Docku (mirror)"
        
        try {
            // A. Insert Disposisi Master
            $stmt_d = $pdo->prepare("INSERT INTO disposisi (uuid, uuid_surat, sifat, catatan, deadline, status, created_at) 
                                     VALUES (:uuid, :surat, :sifat, :cat, :dl, :st, :created)
                                     ON DUPLICATE KEY UPDATE status = VALUES(status)");
            $stmt_d->execute([
                ':uuid' => $d['uuid'],
                ':surat' => $d['uuid_surat'],
                ':sifat' => $d['sifat'],
                ':cat' => $d['catatan'],
                ':dl' => $d['deadline'],
                ':st' => $d['status_global'], // Use global status from API
                ':created' => $d['created_at']
            ]);

            // B. Insert Disposisi Penerima (Self)
            // We need to know OUR status. The API returns `status_personal`.
            $stmt_dp = $pdo->prepare("INSERT INTO disposisi_penerima (disposisi_uuid, user_id, status)
                                      VALUES (:uuid, :uid, :st)
                                      ON DUPLICATE KEY UPDATE status = VALUES(status)");
            $stmt_dp->execute([
                ':uuid' => $d['uuid'],
                ':uid' => $uid,
                ':st' => $d['status_personal']
            ]);

            // C. Insert Instruksi (If any)
            if (!empty($d['instruksi'])) {
                $stmt_i = $pdo->prepare("INSERT IGNORE INTO kegiatan (disposisi_uuid, deskripsi, target_selesai) VALUES (:uuid, :desc, :target)");
                foreach ($d['instruksi'] as $ins) {
                    $stmt_i->execute([
                        ':uuid' => $d['uuid'],
                        ':desc' => $ins['isi'],
                        ':target' => $ins['target_selesai']
                    ]);
                }
            }

            $count++;
        } catch (Exception $e) {
            echo "ERR: " . $e->getMessage();
        }
    }
    echo "OK ($count items)\n";
}
echo "Sync Completed.\n";

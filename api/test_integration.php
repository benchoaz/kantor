<?php
// test_integration.php

require 'config/database.php';

echo "--- TEST: CREATE DISPOSISI FLOW ---\n";

try {
    // 1. Setup: Create Mock Users ("Camat" and "Staff")
    $db = new PDO("mysql:host=localhost;dbname=sidiksae_api", "root", "Belajaran123!");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sender: Camat (Role: camat - Operational, Non-Admin)
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'camat' LIMIT 1");
    $stmt->execute();
    $senderId = $stmt->fetchColumn();
    if (!$senderId) {
        $db->exec("INSERT INTO users (nama, username, password, role, jabatan, user_type, external_id, client_id) VALUES ('Camat Test', 'camat_test', '123', 'camat', 'Camat', 'login', 9991, 1)");
        $senderId = $db->lastInsertId();
    }

    // Receiver: Staff
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'staff' LIMIT 1");
    $stmt->execute();
    $receiverId = $stmt->fetchColumn();
    if (!$receiverId) {
        $db->exec("INSERT INTO users (nama, username, password, role, jabatan, user_type, external_id, client_id) VALUES ('Staff Test', 'staff_test', '123', 'staff', 'Staf', 'login', 9992, 1)");
        $receiverId = $db->lastInsertId();
    }
    
    // Receiver 2: Sekcam (Tembusan)
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'sekcam' LIMIT 1");
    $stmt->execute();
    $ccId = $stmt->fetchColumn();
    if (!$ccId) {
         $db->exec("INSERT INTO users (nama, username, password, role, jabatan, user_type, external_id, client_id) VALUES ('Sekcam Test', 'sekcam_test', '123', 'sekcam', 'Sekcam', 'login', 9993, 1)");
         $ccId = $db->lastInsertId();
    }


    // 2. Setup: Create Mock Surat (Valid with PDF)
    $uuid_surat = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $db->prepare("INSERT INTO surat (uuid, file_hash, file_path, file_size, origin_app) VALUES (?, 'dummy_hash', 'uploads/dummy.pdf', 1024, 'TestScript')")->execute([$uuid_surat]);
    
    echo "[SETUP] Surat Mocked: $uuid_surat\n";
    echo "[SETUP] Sender: $senderId, Receiver: $receiverId\n";

    // 3. Payload Check
    $payload = [
        'uuid_surat' => $uuid_surat,
        'sender_id' => $senderId,
        'sifat' => 'SEGERA',
        'catatan' => 'Mohon ditindaklanjuti segera.',
        'deadline' => date('Y-m-d', strtotime('+1 day')),
        'penerima' => [
            ['user_id' => $receiverId, 'tipe' => 'TINDAK_LANJUT'],
            ['user_id' => $ccId, 'tipe' => 'TEMBUSAN']
        ],
        'instruksi' => [
            ['isi' => 'Koordinasi dengan Desa', 'target_selesai' => date('Y-m-d')]
        ]
    ];

    // 4. Send Request to Local Server
    $ch = curl_init('http://localhost:9999/api/disposisi'); // Assuming Router maps this or direct controller access
    // Note: Assuming Router.php handles /v1/disposisi -> DisposisiController::create
    // If not, we might need to adjust or hit a specific file if simple routing.
    // Let's assume there is a router or we hit index.php?endpoint=disposisi
    
    // Actually, checking index.php usually handles routing.
    // If complex routing, let's try standard path.
    
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-KEY: sk_live_camat_c4m4t2026'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "[API] Response Code: $httpCode\n";
    echo "[API] Response Body: $response\n";

    // 5. Verification
    if ($httpCode == 200) {
        $respData = json_decode($response, true);
        $uuid_dispo = $respData['data']['uuid_disposisi'] ?? null;
        
        if ($uuid_dispo) {
            // Check DB
            $chk = $db->prepare("SELECT * FROM disposisi WHERE uuid = ?");
            $chk->execute([$uuid_dispo]);
            $dispo = $chk->fetch();
            
            $chk2 = $db->prepare("SELECT * FROM disposisi_penerima WHERE disposisi_uuid = ?");
            $chk2->execute([$uuid_dispo]);
            $recipients = $chk2->fetchAll();

            echo "\n--- VERIFICATION RESULT ---\n";
            echo "1. Disposisi Created: " . ($dispo ? "YES" : "NO") . "\n";
            echo "   - Sender: " . $dispo['created_by'] . " (Expected: $senderId)\n";
            echo "2. Penerima Count: " . count($recipients) . " (Expected: 2)\n";
            if (count($recipients) > 0) {
                 echo "   - Status Awal: " . $recipients[0]['status'] . " (Expected: baru)\n";
                 echo "   - Tipe Penerima 1: " . $recipients[0]['tipe_penerima'] . "\n";
            }

            if ($dispo && count($recipients) == 2 && $dispo['created_by'] == $senderId) {
                echo "\n✅ SUCCESS: Integrated Disposition Flow Works!\n";
            } else {
                echo "\n❌ FAILED: Logic Error in DB Persistence.\n";
            }
        } else {
            echo "\n❌ FAILED: API Success but No UUID returned.\n";
        }
    } else {
        echo "\n❌ FAILED: API Error.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

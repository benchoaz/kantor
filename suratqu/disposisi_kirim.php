<?php
// disposisi_kirim.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/sidiksae_api_client.php';

// Auth Check
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['kirim_disposisi'])) {
    try {
        $id_sm = $_POST['id_sm'];
        
        // Load API Config
        $apiConfig = require 'config/integration.php';
        $client = new SidikSaeApiClient($apiConfig['sidiksae']);
        
        // Prepare Payload
        // Note: Assuming 'role' in session matches API expectations (camat, etc)
        $senderRole = $_SESSION['role'] ?? 'staff'; 
        
        // Map Recipient
        // If API expects 'to_role', and we select multiple users, 
        // normally disposisi flows down hierarchy. 
        // For C4, we'll assume the primary recipient role or just pass 'bawahan'
        // The API controller logic for 'to_role' is mostly for logging/routing.
        
        // Construct Penerima List for API
        $penerimaList = [];
        if (isset($_POST['penerima']) && is_array($_POST['penerima'])) {
            foreach ($_POST['penerima'] as $uid) {
                // We need to map Local User ID to UUID if possible, or pass ID and let API resolve?
                // API expects 'user_id' which checks against 'users' table in API DB.
                // Assuming Sync Step B has run, Local ID might not match API ID unless synced.
                // ideally we sends UUIDs. 
                // Let's fetch UUID for the selected users from Local DB
                
                $stmtU = $db->prepare("SELECT uuid_user FROM users WHERE id_user = ?");
                $stmtU->execute([$uid]);
                $uData = $stmtU->fetch();
                
                if ($uData && $uData['uuid_user']) {
                     $penerimaList[] = [
                        'user_id' => $uData['uuid_user'], // Sending UUID
                        'tipe' => 'TINDAK_LANJUT'
                    ];
                }
            }
        }
        
        if (empty($penerimaList)) {
            throw new Exception("Pilih minimal satu penerima disposisi.");
        }
        
        // Construct Instruksi
        $instruksiList = [];
        if (isset($_POST['instruksi']) && is_array($_POST['instruksi'])) {
            foreach ($_POST['instruksi'] as $ins) {
                $instruksiList[] = [
                    'isi' => $ins,
                    'target_selesai' => $_POST['deadline'] ?? null
                ];
            }
        }

        $payload = [
            'uuid_surat' => $_POST['uuid_surat'], // Using ID_SM/UUID
            'from' => [
                'user_id' => $_SESSION['uuid_user'] ?? '', // Must be set during login
                'role' => $senderRole,
                'source' => 'suratqu_camat'
            ],
            'to' => [
                'role' => 'subordinate' // Generic target
            ],
            'sifat' => $_POST['sifat'],
            'catatan' => $_POST['catatan'],
            'deadline' => $_POST['deadline'],
            'penerima' => $penerimaList,
            'instruksi' => $instruksiList
        ];
        
        // Call API
        $res = $client->createDisposisi($payload);
        
        if ($res['success']) {
            $_SESSION['alert'] = ['msg' => 'Disposisi berhasil dikirim!', 'type' => 'success'];
        } else {
             // Backward Compat: If API fails, maybe save locally?
             // For Step C4, we want to enforce API usage.
             throw new Exception("Gagal mengirim ke API: " . $res['message']);
        }
        
    } catch (Exception $e) {
        $_SESSION['alert'] = ['msg' => 'Error: ' . $e->getMessage(), 'type' => 'danger'];
    }
    
    // Redirect back
    header("Location: surat_masuk_detail.php?id=" . $_POST['id_sm']);
    exit;
}
?>

<?php
// disposisi_kirim.php (CAMAT APP VERSION)
define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAuth();

// Init API
$api = new ApiClient();

if (isset($_POST['kirim_disposisi'])) {
    
    $suratId = $_POST['uuid_surat'] ?? null;
    $idSm = $_POST['id_sm'] ?? null; // For redirect
    
    if (!$suratId) {
        setFlashMessage('error', 'UUID Surat tidak valid.');
        redirect('surat-masuk.php');
    }

    try {
        // 1. Prepare Penerima
        // API expects 'penerima' as array of objects {user_id: "uuid", "tipe": "..."}
        $penerimaList = [];
        if (isset($_POST['penerima']) && is_array($_POST['penerima'])) {
            // In CAMAT APP, IDs might be integers or UUIDs depending on implementation.
            // If they are local Integer IDs, we might need to map them.
            // But usually Camat App fetches users from API? 
            // Let's assume the values in the form are what API expects (UUIDs) 
            // OR if they are local IDs, we need to hope API accepts them or they are synced.
            
            // NOTE: In manual_disposisi.php we query local DB. 
            // But Camat App might not have local DB fully synced?
            // Let's check where modal_disposisi.php gets users from.
            
            foreach ($_POST['penerima'] as $uid) {
                $penerimaList[] = [
                    'user_id' => $uid, 
                    'tipe' => 'TINDAK_LANJUT'
                ];
            }
        }
        
        if (empty($penerimaList)) {
            throw new Exception("Pilih minimal satu penerima disposisi.");
        }
        
        // 2. Prepare Instruksi
        $instruksiList = [];
        if (isset($_POST['instruksi']) && is_array($_POST['instruksi'])) {
            foreach ($_POST['instruksi'] as $ins) {
                $instruksiList[] = [
                    'isi' => $ins,
                    'target_selesai' => $_POST['deadline'] ?? null
                ];
            }
        }

        // 3. Payload
        $payload = [
            'uuid_surat' => $suratId,
            'from_role' => 'camat', // Hardcoded as this is Camat App
            'to_role' => 'bawahan', // Generic
            'sifat' => $_POST['sifat'],
            'catatan' => $_POST['catatan'],
            'deadline' => $_POST['deadline'],
            'penerima' => $penerimaList,
            'instruksi' => $instruksiList
        ];
        
        // 4. Send to API
        // Endpoint: POST /api/disposisi
        $response = $api->post('/disposisi', $payload);
        
        if ($response['success']) {
            setFlashMessage('success', 'Disposisi berhasil dikirim ke Server!');
        } else {
            $lastError = $response['message'] ?? 'Unknown Error';
            if (isset($response['errors']) && is_array($response['errors'])) {
                $lastError .= ' (' . implode(', ', $response['errors']) . ')';
            }
            throw new Exception("Gagal kirim API: " . $lastError);
        }
        
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
    
    // Redirect back to detail
    // We use ID if available, else UUID
    $redirectId = $idSm ? $idSm : $suratId; 
    // Wait, surat-detail.php uses 'id' which might be local ID.
    // If $idSm is passed from modal, use it.
    redirect('surat-detail.php?id=' . $redirectId);
    exit;
}
?>

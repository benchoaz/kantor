<?php
/**
 * SuratQu: Handler Kirim ke Camat
 * File: suratqu/kirim_camat_handler.php
 * 
 * TUGAS:
 * - Validasi surat sudah final
 * - Validasi scan_surat ada
 * - Kirim request ke API
 * - Update UI status lokal
 */

session_start();
require_once 'config/database.php';
require_once 'config/integration.php';
require_once 'includes/functions.php';

// Authentication check
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'message' => 'Tidak terautentikasi']);
    exit;
}

// Only process POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data
$uuid_surat = $_POST['uuid_surat'] ?? '';
$pengantar = $_POST['pengantar_disposisi'] ?? '';

// VALIDATION
$errors = [];

if (empty($uuid_surat)) {
    $errors[] = 'UUID surat tidak valid';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Get surat data from local database
    $stmt = $db->prepare("
        SELECT 
            uuid,
            no_surat,
            asal_surat,
            perihal,
            tgl_surat,
            scan_surat,
            status
        FROM surat_masuk
        WHERE uuid = :uuid
    ");
    $stmt->execute([':uuid' => $uuid_surat]);
    $surat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$surat) {
        echo json_encode(['success' => false, 'message' => 'Surat tidak ditemukan']);
        exit;
    }
    
    // CRITICAL VALIDATION: Must have scan
    if (empty($surat['scan_surat'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Surat belum memiliki scan dokumen. Upload scan terlebih dahulu.'
        ]);
        exit;
    }
    
   // Prepare payload for API
    $payload = [
        'uuid_surat' => $surat['uuid'],
        'nomor_surat' => $surat['no_surat'],
        'asal_surat' => $surat['asal_surat'],
        'perihal' => $surat['perihal'],
        'tanggal_surat' => $surat['tgl_surat'],
        'scan_surat' => $surat['scan_surat'],
        'pengantar_disposisi' => $pengantar,
        'dikirim_oleh' => $_SESSION['uuid_user'] ?? 'legacy-' . $_SESSION['id_user'],
        'source_app' => 'suratqu'
    ];
    
    // Call API
    $ch = curl_init(API_BASE_URL . '/surat/kirim-ke-camat');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-KEY: ' . API_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Handle API response
    if ($httpCode === 200) {
        $apiResponse = json_decode($response, true);
        
        // Update local status (optional - for UI display)
        $stmtUpdate = $db->prepare("
            UPDATE surat_masuk 
            SET status = 'dikirim_camat',
                updated_at = NOW()
            WHERE uuid = :uuid
        ");
        $stmtUpdate->execute([':uuid' => $uuid_surat]);
        
        // Log locally
        logActivity('KIRIM_KE_CAMAT', 'surat_masuk', $surat['uuid'], $_SESSION['id_user']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Surat berhasil dikirim ke Camat untuk disposisi',
            'data' => $apiResponse['data'] ?? []
        ]);
        
    } else {
        // API error
        $apiResponse = json_decode($response, true);
        $errorMsg = $apiResponse['message'] ?? $curlError ?? 'Gagal mengirim ke API';
        
        error_log("Kirim Camat API Error: HTTP $httpCode - $errorMsg");
        
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengirim ke API: ' . $errorMsg
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Kirim Camat DB Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

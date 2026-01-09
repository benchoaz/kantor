<?php
// retry_push.php - Manually trigger API push for a specific disposition
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/integrasi_sistem_handler.php';

// Only allow authenticated users
require_auth();

$id = $_GET['id'] ?? null;
$redirect = $_GET['redirect'] ?? 'surat_masuk_detail.php';
$sm_id = $_GET['sm_id'] ?? null;

if (!$id) {
    $_SESSION['alert'] = ['msg' => 'ID Disposisi tidak valid.', 'type' => 'danger'];
    header("Location: surat_masuk.php");
    exit;
}

try {
    // Attempt to push
    $result = pushDisposisiToSidikSae($db, $id);
    
    if ($result && $result['success']) {
        $_SESSION['alert'] = ['msg' => 'Berhasil sinkronisasi dengan API!', 'type' => 'success'];
    } else {
        $error = $result['error'] ?? $result['message'] ?? 'Gagal menghubungi API.';
        $_SESSION['alert'] = ['msg' => 'Gagal Sinkronisasi: ' . $error, 'type' => 'danger'];
    }
} catch (Exception $e) {
    $_SESSION['alert'] = ['msg' => 'Error: ' . $e->getMessage(), 'type' => 'danger'];
}

// Redirect back to detail page
if ($sm_id) {
    header("Location: $redirect?id=$sm_id");
} else {
    header("Location: surat_masuk.php");
}
exit;

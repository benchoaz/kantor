<?php
// FILE: modules/disposisi/manage.php
// Purpose: Handle management actions (Cancel, Update) for dispositions

require_once __DIR__ . '/../../helpers/session_helper.php';
require_once __DIR__ . '/../../helpers/api_helper.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /monitoring.php");
    exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? null;
$token = get_token();

if (!$id) {
    set_flash('error', 'ID Disposisi tidak valid.');
    header("Location: /monitoring.php");
    exit;
}

if ($action === 'cancel') {
    // === ACTION: CANCEL DISPOSITION ===
    $alasan = trim($_POST['alasan'] ?? '');
    
    if (empty($alasan)) {
        set_flash('error', 'Alasan pembatalan wajib diisi.');
        header("Location: /monitoring.php");
        exit;
    }

    $payload = [
        'disposisi_id' => $id,
        'alasan' => $alasan
    ];

    $response = call_api('POST', ENDPOINT_DISPOSISI_CANCEL, $payload, $token);

    if ($response['success']) {
        set_flash('success', 'Disposisi berhasil dibatalkan.');
    } else {
        set_flash('error', $response['message'] ?? 'Gagal membatalkan disposisi.');
    }

} elseif ($action === 'update') {
    // === ACTION: UPDATE/CORRECT DISPOSITION ===
    // Assuming we receive updated fields
    // Note: For full edit, we might need a separate edit form page. 
    // This handler assumes a modal edit for simplicity or processes the form from edit page.
    
    // Validate inputs
    $tujuan = $_POST['tujuan'] ?? ''; // Expecting string or array? API usually expects string/array handle it consistently
    if (is_array($tujuan)) {
        $tujuan = implode(', ', $tujuan);
    }
    
    $catatan = trim($_POST['catatan'] ?? '');
    $deadline = $_POST['deadline'] ?? '';
    $sifat = $_POST['sifat'] ?? '';

    if (empty($catatan) || empty($deadline)) {
        set_flash('error', 'Catatan dan Deadline wajib diisi.');
        header("Location: /monitoring.php");
        exit;
    }

    $payload = [
        'disposisi_id' => $id,
        'tujuan' => $tujuan,
        'catatan' => $catatan,
        'deadline' => $deadline,
        'sifat_disposisi' => $sifat
    ];

    $response = call_api('POST', ENDPOINT_DISPOSISI_UPDATE, $payload, $token);

    if ($response['success']) {
        set_flash('success', 'Disposisi berhasil diralat.');
    } else {
        set_flash('error', $response['message'] ?? 'Gagal meralat disposisi.');
    }

} else {
    set_flash('error', 'Aksi tidak dikenal.');
}

header("Location: /monitoring.php");
exit;

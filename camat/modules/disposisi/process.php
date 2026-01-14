<?php
// FILE: modules/disposisi/process.php
require_once __DIR__ . '/../../helpers/session_helper.php';
require_once __DIR__ . '/../../helpers/api_helper.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /index.php");
    exit;
}

// 1. Get Params
$id_surat = $_POST['id_surat'] ?? null;
$diteruskan_kepada = $_POST['diteruskan_kepada'] ?? [];
$isi_disposisi = trim($_POST['isi_disposisi'] ?? '');
$sifat = $_POST['sifat'] ?? 'Biasa';
$batas_waktu = $_POST['batas_waktu'] ?? date('Y-m-d');

// Extra params for context (Dasar Surat)
$nomor_surat = $_POST['nomor_surat'] ?? '-';
$asal_surat = $_POST['asal_surat'] ?? '-';
$perihal = $_POST['perihal'] ?? '-';
$tanggal_surat = $_POST['tanggal_surat'] ?? '-';
$scan_surat = $_POST['scan_surat'] ?? '';

// 2. Validate
if (!$id_surat || empty($diteruskan_kepada) || empty($isi_disposisi)) {
    set_flash('error', 'Semua kolom yang bertanda * wajib diisi.');
    header("Location: /modules/surat/detail.php?surat_id=" . urlencode($id_surat) . "#form-disposisi");
    exit;
}

$user = current_user();

// 3. Construct Payload
// ALIGNED WITH API CONTRACT (config/api.php)
// 3. Construct Payload (STRICT API FORMAT)
// Mapped from: Camat Legacy -> Strict API

// A. Map Penerima (Array of Objects)
$penerima_list = [];
$primary_role = '';
if (is_array($diteruskan_kepada)) {
    foreach ($diteruskan_kepada as $slug) {
        $penerima_list[] = [
            "user_id" => $slug, // Now a string (ROLE_SLUG)
            "tipe" => "TINDAK_LANJUT"
        ];
        if (!$primary_role) $primary_role = $slug;
    }
}

// B. Map Instruksi (Array of Objects)
$instruksi_list = [];
if (!empty($isi_disposisi)) {
    $instruksi_list[] = [
        "isi" => $isi_disposisi,
        "target_selesai" => $batas_waktu
    ];
}

$payload = [
    "uuid_surat" => $id_surat,
    "sifat"      => $sifat,
    "catatan"    => "Disposisi dari Camat",
    "deadline"   => $batas_waktu,
    "penerima"   => $penerima_list,
    "instruksi"  => $instruksi_list,
    "from" => [
        "user_id" => $user['uuid_user'] ?? $user['id'],
        "role" => $user['role'] ?? 'camat'
    ],
    "to" => [
        "role" => $primary_role ?: 'STAF'
    ]
];

// 4. Send to API
$token = get_token();
$response = call_api('POST', ENDPOINT_DISPOSISI_CREATE, $payload, $token);

// 5. Handle Response
if ($response['success']) {
    set_flash('success', 'Disposisi berhasil dikirim!');
    header("Location: /index.php"); // Go back to inbox
    exit;
} else {
    $msg = $response['message'] ?? 'Gagal mengirim disposisi.';
    set_flash('error', $msg);
    // Stay on detail page to retry
    header("Location: /modules/surat/detail.php?surat_id=" . urlencode($id_surat) . "#form-disposisi");
    exit;
}

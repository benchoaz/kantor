<?php
// google_drive_upload.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/google_drive.php';
require_login();

$id = $_GET['id'] ?? 0;

// CATATAN: Ini adalah placeholder. Implementasi penuh membutuhkan Refresh Token
// yang didapat dari proses OAuth2. Untuk demo ini, saya menyediakan strukturnya.

$page_title = 'Upload ke Google Drive';
include 'includes/header.php';
?>

<div class="card border-0 shadow-sm col-md-6 mx-auto mt-5">
    <div class="card-body p-5 text-center">
        <i class="bi bi-google fs-1 text-primary mb-3"></i>
        <h4 class="fw-bold">Integrasi Google Drive</h4>
        <p class="text-muted">Fitur ini memerlukan konfigurasi API Key dan OAuth2 Refresh Token pada server cPanel Anda.</p>
        
        <div class="alert alert-info small">
            Langkah Konfigurasi:<br>
            1. Buat Project di Google Cloud Console.<br>
            2. Aktifkan Google Drive API.<br>
            3. Buat OAuth 2.0 Client ID.<br>
            4. Gunakan file <code>google_auth_setup.php</code> untuk mendapatkan access token.
        </div>
        
        <a href="kegiatan_detail.php?id=<?= $id ?>" class="btn btn-primary px-4 mt-3">Kembali ke Detail</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

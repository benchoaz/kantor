<?php
/**
 * Detail Surat - API-based (WAJIB)
 * ======================================
 * PRINSIP:
 * 1. Ambil id_surat dari parameter URL ?id_surat={id}
 * 2. Panggil API GET /api/v1/surat/{id_surat}
 * 3. Jika HTTP 200: Tampilkan data
 * 4. Jika HTTP ≠ 200: Tampilkan pesan error (JANGAN redirect)
 * 
 * LARANGAN:
 * ❌ Mengambil data dari database lokal
 * ❌ Redirect otomatis tanpa notifikasi
 * ❌ Form kosong tanpa pesan error
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// Load API Client
require_once 'includes/sidiksae_api_client.php';
$integration_config = require 'config/integration.php';
$apiClient = new SidikSaeApiClient($integration_config['sidiksae']);

// WAJIB: Ambil id_surat dari parameter
$id_surat = $_GET['id_surat'] ?? null;

if (!$id_surat) {
    // Error jelas: parameter tidak ada
    include 'includes/header.php';
    ?>
    <div class="container mt-5">
        <div class="alert alert-warning border-warning shadow-sm">
            <h4 class="alert-heading"><i class="fa-solid fa-triangle-exclamation me-2"></i>Parameter Tidak Valid</h4>
            <p class="mb-3">Parameter <code>id_surat</code> tidak ditemukan di URL.</p>
            <hr>
            <a href="surat_masuk.php" class="btn btn-primary">
                <i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Daftar Surat
            </a>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

// WAJIB: Panggil API untuk mendapatkan detail surat
$response = $apiClient->getSuratDetail($id_surat);

// WAJIB: Validasi response API
if (!$response['success'] || $response['http_code'] !== 200) {
    // Error handling yang jelas - TIDAK redirect
    include 'includes/header.php';
    
    $error_icon = 'fa-circle-exclamation';
    $error_class = 'alert-danger';
    
    if ($response['http_code'] === 404) {
        $error_icon = 'fa-magnifying-glass';
        $error_class = 'alert-warning';
    } elseif ($response['http_code'] === 0) {
        $error_icon = 'fa-wifi';
        $error_class = 'alert-secondary';
    }
    ?>
    <div class="container mt-5">
        <div class="alert <?= $error_class ?> border shadow-sm">
            <h4 class="alert-heading">
                <i class="fa-solid <?= $error_icon ?> me-2"></i>
                <?php if ($response['http_code'] === 404): ?>
                    Surat Tidak Ditemukan
                <?php elseif ($response['http_code'] === 0): ?>
                    Gagal Terhubung ke API
                <?php else: ?>
                    Gagal Memuat Data Surat
                <?php endif; ?>
            </h4>
            <p class="mb-3">
                <?= htmlspecialchars($response['message'] ?? 'Terjadi kesalahan yang tidak diketahui') ?>
            </p>
            <?php if ($response['http_code'] !== 0): ?>
                <div class="small text-muted mb-3">
                    <strong>HTTP Status:</strong> <?= $response['http_code'] ?> | 
                    <strong>ID Surat:</strong> <?= htmlspecialchars($id_surat) ?>
                </div>
            <?php endif; ?>
            <hr>
            <div class="d-flex gap-2">
                <a href="surat_masuk.php" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Daftar Surat
                </a>
                <?php if ($response['http_code'] !== 404): ?>
                    <a href="?id_surat=<?= htmlspecialchars($id_surat) ?>" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-rotate-right me-2"></i>Coba Lagi
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

// Data surat dari API
$surat = $response['data'];

// Validasi data tidak null (defense programming)
if (!$surat) {
    include 'includes/header.php';
    ?>
    <div class="container mt-5">
        <div class="alert alert-danger border-danger shadow-sm">
            <h4 class="alert-heading"><i class="fa-solid fa-database me-2"></i>Data Tidak Valid</h4>
            <p class="mb-3">API mengembalikan HTTP 200 tetapi data surat kosong (null).</p>
            <p class="small text-muted mb-3">
                Ini adalah BUG pada API. Seharusnya API mengembalikan HTTP 404 jika surat tidak ditemukan.
            </p>
            <hr>
            <a href="surat_masuk.php" class="btn btn-primary">
                <i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Daftar Surat
            </a>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-2">
    <div>
        <h2 class="fw-bold mb-1">Detail Surat</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="surat_masuk.php">Surat Masuk</a></li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </nav>
    </div>
    <div class="btn-group">
        <a href="surat_masuk.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Informasi Surat dari API -->
        <div class="card card-custom p-4 border-0 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <h5 class="fw-bold text-primary mb-0">
                    <i class="fa-solid fa-envelope-open-text me-2"></i>Informasi Surat
                </h5>
                <span class="badge bg-success-subtle text-success border border-success">
                    <i class="fa-solid fa-cloud-check me-1"></i>Data dari API Pusat
                </span>
            </div>
            
            <div class="row g-3">
                <div class="col-sm-4 fw-bold text-muted">ID Surat</div>
                <div class="col-sm-8 text-primary fw-bold">: <?= htmlspecialchars($surat['id_surat'] ?? '-') ?></div>
                
                <div class="col-sm-4 fw-bold text-muted">Nomor Surat</div>
                <div class="col-sm-8">: <?= htmlspecialchars($surat['nomor_surat'] ?? '-') ?></div>
                
                <div class="col-sm-4 fw-bold text-muted">Asal Surat</div>
                <div class="col-sm-8">: <?= htmlspecialchars($surat['asal_surat'] ?? '-') ?></div>
                
                <div class="col-sm-4 fw-bold text-muted">Tanggal Surat</div>
                <div class="col-sm-8">: <?= isset($surat['tanggal_surat']) ? format_tgl_indo($surat['tanggal_surat']) : '-' ?></div>
                
                <?php if (!empty($surat['jam_surat'])): ?>
                <div class="col-sm-4 fw-bold text-muted">Jam Surat</div>
                <div class="col-sm-8">: <?= format_jam_wib($surat['jam_surat']) ?></div>
                <?php endif; ?>
                
                <div class="col-sm-4 fw-bold text-muted">Perihal</div>
                <div class="col-sm-8">: <?= htmlspecialchars($surat['perihal'] ?? '-') ?></div>
                
                <?php if (!empty($surat['sifat'])): ?>
                <div class="col-sm-4 fw-bold text-muted">Sifat Surat</div>
                <div class="col-sm-8">: 
                    <?php
                    $sifat_class = [
                        'Sangat Segera' => 'bg-danger',
                        'Segera' => 'bg-warning',
                        'Biasa' => 'bg-info'
                    ];
                    $badge = $sifat_class[$surat['sifat']] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?= $badge ?> rounded-pill">
                        <?= htmlspecialchars($surat['sifat']) ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($surat['nomor_agenda'])): ?>
                <div class="col-sm-4 fw-bold text-muted">Nomor Agenda</div>
                <div class="col-sm-8">: <?= htmlspecialchars($surat['nomor_agenda']) ?></div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($surat['scan_surat'])): ?>
            <div class="mt-4 pt-3 border-top">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-paperclip me-2"></i>File Scan Surat</h6>
                <div class="bg-light p-3 rounded d-flex align-items-center justify-content-between">
                    <span>
                        <i class="fa-solid fa-file-pdf text-danger me-2 scale-110"></i>
                        Berkas Digital Surat Asli
                    </span>
                    <a href="<?= htmlspecialchars($surat['scan_surat']) ?>" target="_blank" class="btn btn-sm btn-danger">
                        <i class="fa-solid fa-external-link me-1"></i>Buka PDF
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="mt-4 pt-3 border-top">
                <div class="alert alert-warning mb-0 small">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    File scan surat tidak tersedia atau belum diunggah.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Informasi Tambahan / Metadata -->
        <div class="card card-custom p-4 border-0 shadow-sm bg-light">
            <h6 class="fw-bold mb-3 text-secondary">
                <i class="fa-solid fa-info-circle me-2"></i>Metadata
            </h6>
            <div class="small">
                <div class="mb-2">
                    <strong>Sumber Data:</strong><br>
                    <span class="text-muted">API Pusat SidikSae</span>
                </div>
                <div class="mb-2">
                    <strong>Endpoint:</strong><br>
                    <code class="small">GET /api/v1/surat/<?= htmlspecialchars($id_surat) ?></code>
                </div>
                <div class="mb-2">
                    <strong>Status HTTP:</strong><br>
                    <span class="badge bg-success">200 OK</span>
                </div>
                <?php if (isset($response['timestamp'])): ?>
                <div>
                    <strong>Waktu Respon:</strong><br>
                    <span class="text-muted"><?= htmlspecialchars($response['timestamp']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

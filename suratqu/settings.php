<?php
// settings.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// Only admin can access settings
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['alert'] = ['msg' => 'Akses ditolak!', 'type' => 'danger'];
    header("Location: index.php");
    exit;
}

include 'includes/header.php';

// General settings logic (if any)
?>

<div class="mb-4 pt-2">
    <h2 class="fw-bold mb-1">Pengaturan Sistem</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Pengaturan</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card card-custom p-4 border-0 shadow-sm mb-4">
            <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="fa-solid fa-user-gear me-2 text-primary"></i> Profil Pengguna</h5>
            <div class="d-flex align-items-center mb-4">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama_lengkap']) ?>&background=34C759&color=fff" class="rounded-circle me-3" width="60">
                <div>
                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></h5>
                    <span class="badge bg-light text-primary border border-primary px-3"><?= ucfirst($_SESSION['role']) ?></span>
                </div>
            </div>
            
            <hr>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['username']) ?>" readonly>
            </div>
            
            <div class="mb-4">
                <label class="form-label fw-bold">Jabatan</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['nama_jabatan'] ?? 'Administrator') ?>" readonly>
            </div>

            <div class="pt-2">
                <a href="#" class="btn btn-primary px-4"><i class="fa-solid fa-key me-2"></i> Ubah Password</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card card-custom p-4 bg-light border-0 shadow-sm border-start border-5 border-primary mb-4">
             <h6 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i> Pengaturan Kop Surat</h6>
             <p class="small text-muted mb-3">Untuk mengubah identitas instansi, logo daerah, dan logo unit kerja pada Kop Surat resmi, silakan gunakan modul khusus Manajemen Kop.</p>
             <a href="kop_surat.php" class="btn btn-outline-primary btn-sm px-4">Buka Manajemen Kop <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>

        <div class="card card-custom p-4 border-0 shadow-sm">
             <h6 class="fw-bold mb-2">Informasi Sistem</h6>
             <table class="table table-sm table-borderless small mb-0">
                 <tr><td class="ps-0 text-muted">Versi Aplikasi</td><td class="text-end fw-bold">SuratQu v1.0</td></tr>
                 <tr><td class="ps-0 text-muted">PHP Version</td><td class="text-end fw-bold"><?= PHP_VERSION ?></td></tr>
                 <tr><td class="ps-0 text-muted">Log Server</td><td class="text-end fw-bold text-success">Active</td></tr>
             </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

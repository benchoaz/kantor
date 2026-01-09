<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

$id_user = $_SESSION['id_user'];

// Fetch User Data with Position
$stmt = $db->prepare("SELECT u.*, j.nama_jabatan 
                      FROM users u 
                      LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan 
                      WHERE u.id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

if (!$user) { redirect('logout.php'); }

$title = 'Profil Saya';
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-4">
            <div class="card card-custom border-0 shadow-sm text-center p-4 mb-4">
                <div class="mx-auto bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mb-3 shadow" style="width: 100px; height: 100px; font-size: 40px;">
                    <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                </div>
                <h5 class="fw-bold mb-0"><?= htmlspecialchars($user['nama_lengkap']) ?></h5>
                <p class="text-muted small mb-3">@<?= htmlspecialchars($user['username']) ?></p>
                
                <div class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill mb-2">
                    <i class="fa-solid fa-id-badge me-2"></i> <?= htmlspecialchars($user['nama_jabatan'] ?: 'Tanpa Jabatan') ?>
                </div>
                <div class="small text-muted">
                    <i class="fa-solid fa-shield-halved me-1"></i> Peran: <?= ucfirst($user['role']) ?>
                </div>
            </div>
            
            <div class="card card-custom border-0 shadow-sm p-4">
                <h6 class="fw-bold mb-3 border-bottom pb-2">Informasi Akun</h6>
                <div class="mb-2">
                    <small class="text-muted d-block">NIP</small>
                    <span class="fw-medium"><?= $user['nip'] ?: '-' ?></span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Pangkat / Golongan</small>
                    <span class="fw-medium"><?= $user['golongan'] ?: '-' ?></span>
                </div>
                <div class="mb-0">
                    <small class="text-muted d-block">Status Akun</small>
                    <span class="badge bg-success rounded-pill">Aktif</span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-custom border-0 shadow-sm p-4">
                <h5 class="fw-bold mb-4"><i class="fa-solid fa-user-gear me-2 text-primary"></i> Pengaturan Profil & Keamanan</h5>
                
                <?php if (isset($_SESSION['alert'])): ?>
                    <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <?= $_SESSION['alert']['msg'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['alert']); ?>
                <?php endif; ?>

                <form action="users_proses.php" method="POST">
                    <input type="hidden" name="id_user" value="<?= $id_user ?>">
                    <input type="hidden" name="from_profile" value="1">
                    
                    <div class="row g-3">
                        <div class="col-md-6 text-muted">
                            <label class="form-label small fw-bold">Username (Permanent)</label>
                            <input type="text" class="form-control bg-light" value="<?= $user['username'] ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= $user['nama_lengkap'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">NIP</label>
                            <input type="text" name="nip" class="form-control" value="<?= $user['nip'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Golongan</label>
                            <input type="text" name="golongan" class="form-control" value="<?= $user['golongan'] ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-primary"><i class="fa-brands fa-telegram me-1"></i> Telegram ID (Untuk Notifikasi)</label>
                            <input type="text" name="telegram_id" class="form-control" value="<?= $user['telegram_id'] ?>" placeholder="Contoh: 123456789">
                            <small class="text-muted" style="font-size: 10px;">Dapatkan ID Anda melalui bot @userinfobot di Telegram.</small>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-danger"><i class="fa-solid fa-key me-2"></i> Ubah Password</h6>
                            <p class="text-muted small">Kosongkan jika tidak ingin mengubah password.</p>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Password Baru</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password baru...">
                        </div>
                        
                        <!-- Hidden fields to maintain role/status when updated from profile -->
                        <input type="hidden" name="role" value="<?= $user['role'] ?>">
                        <input type="hidden" name="id_jabatan" value="<?= $user['id_jabatan'] ?>">
                        <input type="hidden" name="is_active" value="1">

                        <div class="col-12 pt-3">
                            <button type="submit" name="update" class="btn btn-primary px-4 shadow">
                                <i class="fa-solid fa-save me-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.bg-soft-primary { background-color: rgba(52, 199, 89, 0.1); }
</style>

<?php include 'includes/footer.php'; ?>

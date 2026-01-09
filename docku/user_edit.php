<?php
// user_edit.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/integration_helper.php';
require_role(['admin']);

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit;
}

// CENTRALIZED USER MANAGEMENT ENFORCEMENT
// Redirect or show error
die('<div style="text-align:center; padding:50px; font-family:sans-serif;">
    <h1>Akses Tidak Diizinkan</h1>
    <p>Manajemen User (Edit) sekarang terpusat di Panel Admin API.</p>
    <a href="https://api.sidiksae.my.id/admin" style="background:#0d6efd; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">Ke Admin Panel Pusat</a>
    <br><br>
    <a href="users.php">Kembali</a>
</div>');
?>

$page_title = 'Edit User';
$active_page = 'users';

// Fetch bidang list
$bidang_list = $pdo->query("SELECT * FROM bidang ORDER BY nama_bidang")->fetchAll();

include 'includes/header.php';
?>

<div class="row justify-content-center animate-up">
    <div class="col-lg-7 col-md-9">
        <div class="d-flex align-items-center mb-4">
            <a href="users.php" class="btn btn-light border-0 rounded-circle p-2 me-3 shadow-sm">
                <i class="bi bi-arrow-left fs-5"></i>
            </a>
            <div>
                <h3 class="title-main mb-0">Ubah Profil User</h3>
                <p class="text-muted small mb-0">Perbarui identitas: <strong><?= htmlspecialchars($user['nama']) ?></strong></p>
            </div>
        </div>

        <div class="card-modern border-0 p-4 mb-5">
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="text-label mb-2">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-2 border-end-0 border-light"><i class="bi bi-at text-muted"></i></span>
                            <input type="text" name="username" class="form-control border-2 border-start-0 shadow-none ps-0 <?= ($user['username'] === 'admin') ? 'bg-light text-muted' : '' ?>" value="<?= htmlspecialchars($user['username']) ?>" required <?= ($user['username'] === 'admin') ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-label mb-2">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control border-2 shadow-none" value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-label mb-2">Role / Peran Akses <span class="text-danger">*</span></label>
                    <?php if ($user['username'] === 'admin'): ?>
                        <div class="p-3 bg-primary bg-opacity-10 border border-primary border-opacity-10 rounded-4 d-flex align-items-center">
                            <i class="bi bi-shield-lock-fill text-primary me-3 fs-4"></i>
                            <div>
                                <div class="fw-bold text-dark">Administrator Utama</div>
                                <div class="extra-small text-muted">Role akun sistem utama tidak dapat diubah.</div>
                            </div>
                            <input type="hidden" name="role" value="admin">
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check card-modern bg-white p-3 border m-0 h-100 cursor-pointer">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="role" id="role_operator" value="operator" <?= ($user['role'] === 'operator') ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold d-block" for="role_operator" style="margin-left: 25px;">
                                        Operator
                                        <small class="text-muted d-block fw-normal extra-small">Akses entry data harian.</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check card-modern bg-white p-3 border m-0 h-100 cursor-pointer">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="role" id="role_pimpinan" value="pimpinan" <?= ($user['role'] === 'pimpinan') ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold d-block" for="role_pimpinan" style="margin-left: 25px;">
                                        Pimpinan
                                        <small class="text-muted d-block fw-normal extra-small">Akses monitoring & verifikasi.</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check card-modern bg-white p-3 border m-0 h-100 cursor-pointer text-danger">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="role" id="role_admin" value="admin" <?= ($user['role'] === 'admin') ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold d-block" for="role_admin" style="margin-left: 25px;">
                                        Admin
                                        <small class="text-muted d-block fw-normal extra-small">Akses penuh kelola sistem.</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-4 bg-light rounded-4 mb-4">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <label class="text-label mb-2">Jabatan (Tanda Tangan)</label>
                            <select name="jabatan" class="form-select border-0 shadow-sm">
                                <option value="">-- Pilih Jabatan --</option>
                                <optgroup label="▸ Pimpinan">
                                    <option value="Camat" <?= $user['jabatan'] == 'Camat' ? 'selected' : '' ?>>Camat</option>
                                    <option value="Sekretaris Camat" <?= $user['jabatan'] == 'Sekretaris Camat' ? 'selected' : '' ?>>Sekretaris Camat (Sekcam)</option>
                                </optgroup>
                                <optgroup label="▸ Sekretariat">
                                    <option value="Kasubbag Perencanaan dan Keuangan" <?= $user['jabatan'] == 'Kasubbag Perencanaan dan Keuangan' ? 'selected' : '' ?>>Kasubbag Perencanaan dan Keuangan</option>
                                    <option value="Kasubbag Umum dan Kepegawaian" <?= $user['jabatan'] == 'Kasubbag Umum dan Kepegawaian' ? 'selected' : '' ?>>Kasubbag Umum dan Kepegawaian</option>
                                    <option value="Staf Sekretariat" <?= $user['jabatan'] == 'Staf Sekretariat' ? 'selected' : '' ?>>Staf Sekretariat</option>
                                </optgroup>
                                <optgroup label="▸ Kepala Seksi">
                                    <option value="Kasi Pemerintahan" <?= $user['jabatan'] == 'Kasi Pemerintahan' ? 'selected' : '' ?>>Kasi Pemerintahan</option>
                                    <option value="Kasi Ekonomi dan Pembangunan" <?= $user['jabatan'] == 'Kasi Ekonomi dan Pembangunan' ? 'selected' : '' ?>>Kasi Ekonomi dan Pembangunan</option>
                                    <option value="Kasi Kesejahteraan Rakyat" <?= $user['jabatan'] == 'Kasi Kesejahteraan Rakyat' ? 'selected' : '' ?>>Kasi Kesejahteraan Rakyat</option>
                                    <option value="Kasi Trantibum" <?= $user['jabatan'] == 'Kasi Trantibum' ? 'selected' : '' ?>>Kasi Trantibum</option>
                                </optgroup>
                                <optgroup label="▸ Pelaksana">
                                    <option value="Staf Pemerintahan" <?= $user['jabatan'] == 'Staf Pemerintahan' ? 'selected' : '' ?>>Staf Pemerintahan</option>
                                    <option value="Staf Ekonomi dan Pembangunan" <?= $user['jabatan'] == 'Staf Ekonomi dan Pembangunan' ? 'selected' : '' ?>>Staf Ekonomi dan Pembangunan</option>
                                    <option value="Staf Kesejahteraan Rakyat" <?= $user['jabatan'] == 'Staf Kesejahteraan Rakyat' ? 'selected' : '' ?>>Staf Kesejahteraan Rakyat</option>
                                    <option value="Staf Trantibum" <?= $user['jabatan'] == 'Staf Trantibum' ? 'selected' : '' ?>>Staf Trantibum</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="text-label mb-2">NIP</label>
                            <input type="text" name="nip" class="form-control border-0 shadow-sm" value="<?= htmlspecialchars($user['nip'] ?? '') ?>" placeholder="198001012000011001" maxlength="20">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-label mb-2">No. WhatsApp</label>
                            <input type="text" name="no_hp" class="form-control border-0 shadow-sm" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" placeholder="081234567xxx">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-label mb-2">Telegram Chat ID</label>
                            <input type="text" name="telegram_id" class="form-control border-0 shadow-sm" value="<?= htmlspecialchars($user['telegram_id'] ?? '') ?>" placeholder="123456789">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-label mb-2">Hak Akses Bidang</label>
                    <select name="bidang_id" class="form-select border-2 shadow-none">
                        <option value="">-- Semua Bidang (Akses Penuh) --</option>
                        <?php foreach ($bidang_list as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ($user['bidang_id'] == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['nama_bidang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="text-label mb-2">Reset Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-2 border-end-0 border-light"><i class="bi bi-shield-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control border-2 border-start-0 shadow-none ps-0" placeholder="Biarkan kosong jika tidak diubah">
                    </div>
                    <div class="extra-small text-muted mt-2">Gunakan minimal 6 karakter kombinasi huruf dan angka.</div>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="users.php" class="btn btn-modern btn-light border px-4 text-muted">Batalkan</a>
                    <button type="submit" class="btn btn-modern btn-primary-modern flex-fill py-3 shadow-sm">
                        <i class="bi bi-save-fill me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

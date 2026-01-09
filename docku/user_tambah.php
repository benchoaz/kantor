<?php
// user_tambah.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role(['admin']);

// CENTRALIZED USER MANAGEMENT ENFORCEMENT
// Redirect or show error
die('<div style="text-align:center; padding:50px; font-family:sans-serif;">
    <h1>Akses Tidak Diizinkan</h1>
    <p>Manajemen User (Tambah/Edit) sekarang terpusat di Panel Admin API.</p>
    <a href="https://api.sidiksae.my.id/admin" style="background:#0d6efd; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">Ke Admin Panel Pusat</a>
    <br><br>
    <a href="users.php">Kembali</a>
</div>');
?>
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Username sudah digunakan.";
            } else {
                $error = "Gagal menambah user: " . $e->getMessage();
            }
        }
    } else {
        $error = "Harap isi semua field.";
    }
}

$page_title = 'Tambah User';
$active_page = 'users';

// Fetch bidang list for dropdown
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
                <h3 class="title-main mb-0">Tambah User Baru</h3>
                <p class="text-muted small mb-0">Daftarkan personil baru ke dalam sistem BESUK SAE</p>
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
                            <span class="input-group-text bg-light border-2 border-end-0 border-light"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" name="username" class="form-control border-2 border-start-0 shadow-none ps-0" required placeholder="Contoh: budi123">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-label mb-2">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control border-2 shadow-none" required placeholder="Nama lengkap personil">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-label mb-2">Role / Peran Akses <span class="text-danger">*</span></label>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-check card-modern bg-white p-3 border m-0 h-100 cursor-pointer">
                                <input class="form-check-input ms-0 me-2" type="radio" name="role" id="role_operator" value="operator" checked>
                                <label class="form-check-label fw-bold d-block" for="role_operator" style="margin-left: 25px;">
                                    Operator
                                    <small class="text-muted d-block fw-normal extra-small">Akses entry data harian.</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check card-modern bg-white p-3 border m-0 h-100 cursor-pointer">
                                <input class="form-check-input ms-0 me-2" type="radio" name="role" id="role_pimpinan" value="pimpinan">
                                <label class="form-check-label fw-bold d-block" for="role_pimpinan" style="margin-left: 25px;">
                                    Pimpinan
                                    <small class="text-muted d-block fw-normal extra-small">Akses monitoring & verifikasi.</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check card-modern bg-white p-3 border m-0 h-100 cursor-pointer text-danger">
                                <input class="form-check-input ms-0 me-2" type="radio" name="role" id="role_admin" value="admin">
                                <label class="form-check-label fw-bold d-block" for="role_admin" style="margin-left: 25px;">
                                    Admin
                                    <small class="text-muted d-block fw-normal extra-small">Akses penuh kelola sistem.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-4 mb-4">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <label class="text-label mb-2">Jabatan (Opsional)</label>
                            <select name="jabatan" class="form-select border-0 shadow-sm">
                                <option value="">-- Pilih Jabatan --</option>
                                <optgroup label="▸ Pimpinan">
                                    <option value="Camat">Camat</option>
                                    <option value="Sekretaris Camat">Sekretaris Camat (Sekcam)</option>
                                </optgroup>
                                <optgroup label="▸ Sekretariat">
                                    <option value="Kasubbag Perencanaan dan Keuangan">Kasubbag Perencanaan dan Keuangan</option>
                                    <option value="Kasubbag Umum dan Kepegawaian">Kasubbag Umum dan Kepegawaian</option>
                                    <option value="Staf Sekretariat">Staf Sekretariat</option>
                                </optgroup>
                                <optgroup label="▸ Kepala Seksi">
                                    <option value="Kasi Pemerintahan">Kasi Pemerintahan</option>
                                    <option value="Kasi Ekonomi dan Pembangunan">Kasi Ekonomi dan Pembangunan</option>
                                    <option value="Kasi Kesejahteraan Rakyat">Kasi Kesejahteraan Rakyat</option>
                                    <option value="Kasi Trantibum">Kasi Trantibum</option>
                                </optgroup>
                                <optgroup label="▸ Pelaksana">
                                    <option value="Staf Pemerintahan">Staf Pemerintahan</option>
                                    <option value="Staf Ekonomi dan Pembangunan">Staf Ekonomi dan Pembangunan</option>
                                    <option value="Staf Kesejahteraan Rakyat">Staf Kesejahteraan Rakyat</option>
                                    <option value="Staf Trantibum">Staf Trantibum</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="text-label mb-2">NIP (Opsional)</label>
                            <input type="text" name="nip" class="form-control border-0 shadow-sm" placeholder="198001012000011001" maxlength="20">
                            <div class="extra-small text-muted mt-2">Nomor Induk Pegawai 18 digit untuk tanda tangan elektronik.</div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-label mb-2">Bidang / Bagian (Opsional)</label>
                    <select name="bidang_id" class="form-select border-2 shadow-none">
                        <option value="">-- Semua Bidang (Akses Penuh) --</option>
                        <?php foreach ($bidang_list as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama_bidang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="extra-small text-muted mt-2">Batasi akses hanya pada data bidang tertentu saja.</div>
                </div>

                <div class="mb-5">
                    <label class="text-label mb-2">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-2 border-end-0 border-light"><i class="bi bi-shield-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control border-2 border-start-0 shadow-none ps-0" required minlength="6" placeholder="Minimal 6 karakter">
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="users.php" class="btn btn-modern btn-light border px-4 text-muted">Kembali</a>
                    <button type="submit" class="btn btn-modern btn-primary-modern flex-fill py-3 shadow-sm">
                        <i class="bi bi-person-plus-fill me-2"></i>Daftarkan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php include 'includes/footer.php'; ?>

<?php
// profil.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_login(); // Allow any logged in user

$id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] === 'admin');

// Fetch User Data
$stmt = $pdo->prepare("SELECT users.*, bidang.nama_bidang FROM users LEFT JOIN bidang ON users.bidang_id = bidang.id WHERE users.id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: index.php");
    exit;
}

// Fetch Settings Data (Only if Admin)
$settings = [];
if ($is_admin) {
    $stmtS = $pdo->query("SELECT * FROM pengaturan LIMIT 1");
    $settings = $stmtS->fetch();
    if (!$settings) {
        // Fallback default
        $settings = [
            'nama_instansi_1' => 'PEMERINTAH KABUPATEN PROBOLINGGO',
            'nama_instansi_2' => 'KECAMATAN BESUK',
            'alamat_1' => 'Jalan Raya Besuk No. 1, Besuk, Probolinggo',
            'alamat_2' => 'Email: kecamatan.besuk@probolinggokab.go.id',
            'nama_camat' => 'PUJA KURNIAWAN, S.STP., M.Si',
            'nip_camat' => '19800101 200001 1 001'
        ];
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Update Profile (User)
    if (isset($_POST['update_profile'])) {
        $nama = $_POST['nama'] ?? '';
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

        if ($nama) {
            try {
                $pdo->beginTransaction();
                $no_hp = $_POST['no_hp'] ?? '';
                $telegram_id = $_POST['telegram_id'] ?? '';
                
                // Handle Profile Photo Upload
                $foto_profil_name = $user['foto_profil'] ?? null;
                if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['foto_profil']['tmp_name'];
                    $file_size = $_FILES['foto_profil']['size'];
                    $file_ext = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
                    $allowed = ['png', 'jpg', 'jpeg'];
                    
                    // Validate file type
                    if (!in_array($file_ext, $allowed)) {
                        throw new Exception('Format foto tidak didukung. Gunakan JPG atau PNG.');
                    }
                    
                    // Validate file size (max 2MB)
                    if ($file_size > 2 * 1024 * 1024) {
                        throw new Exception('Ukuran foto terlalu besar. Maksimal 2MB.');
                    }
                    
                    $new_name = 'profil_' . $id . '_' . time() . '.' . $file_ext;
                    $upload_dir = 'uploads/profil/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
                        // Delete old photo if exists
                        if ($foto_profil_name && file_exists($upload_dir . $foto_profil_name)) {
                            unlink($upload_dir . $foto_profil_name);
                        }
                        $foto_profil_name = $new_name;
                    } else {
                        throw new Exception('Gagal mengupload foto profil.');
                    }
                }

                // Update base profile info
                $stmt = $pdo->prepare("UPDATE users SET nama = ?, no_hp = ?, telegram_id = ?, foto_profil = ? WHERE id = ?");
                $stmt->execute([$nama, $no_hp, $telegram_id, $foto_profil_name, $id]);

                // Handle Password Change
                if ($password_baru) {
                    // 1. Verify Old Password
                    if (empty($password_lama)) {
                        throw new Exception("Password lama wajib diisi untuk mengubah password.");
                    }
                    
                    // Fetch current password hash to verify
                    // Note: $user is fetched at top of file, but let's re-fetch to be safe inside transaction or just use $user['password']
                    if (!password_verify($password_lama, $user['password'])) {
                        throw new Exception("Password lama yang Anda masukkan salah.");
                    }

                    // 2. Validate New Password Confirmation
                    if ($password_baru !== $konfirmasi_password) {
                        throw new Exception("Konfirmasi password baru tidak cocok.");
                    }

                    // 3. Update Password
                    $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                    $stmt_pw = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt_pw->execute([$hashed_password, $id]);
                }

                $_SESSION['nama'] = $nama;
                $pdo->commit();
                $success = "Profil berhasil diperbarui.";
                
                // Refresh user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Gagal update profil: " . $e->getMessage();
            }
        } else {
            $error = "Nama tidak boleh kosong.";
        }
    }
    
    // 2. Update Settings (Admin Only)
    if ($is_admin && isset($_POST['update_settings'])) {
        try {
            $s_instansi1 = $_POST['instansi_1'];
            $s_instansi2 = $_POST['instansi_2'];
            $s_alamat1 = $_POST['alamat_1'];
            $s_alamat2 = $_POST['alamat_2'];
            $s_camat = $_POST['nama_camat'];
            $s_nip = $_POST['nip_camat'];
            $s_jabatan_ttd = $_POST['jabatan_ttd'];
            $s_golongan_ttd = $_POST['golongan_ttd'];

            // Handle Logo Upload
            $logo_name = $settings['logo'] ?? null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['logo']['tmp_name'];
                $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $allowed = ['png', 'jpg', 'jpeg'];
                
                if (in_array($file_ext, $allowed)) {
                    $new_name = 'logo_' . time() . '.' . $file_ext;
                    $upload_dir = 'assets/img/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
                        // Delete old logo if exists and not default
                        if ($logo_name && file_exists($upload_dir . $logo_name) && !str_contains($logo_name, 'default')) {
                            unlink($upload_dir . $logo_name);
                        }
                        $logo_name = $new_name;
                    }
                } else {
                    $error = "Format logo tidak didukung (Gunakan PNG/JPG).";
                }
            }

            // Ensure row exists
            $pdo->exec("INSERT IGNORE INTO pengaturan (id) VALUES (1)");
            
            $stmtUpd = $pdo->prepare("UPDATE pengaturan SET 
                nama_instansi_1 = ?, nama_instansi_2 = ?, 
                alamat_1 = ?, alamat_2 = ?, 
                nama_camat = ?, nip_camat = ?,
                jabatan_ttd = ?, golongan_ttd = ?,
                logo = ?
                WHERE id = 1");
            $stmtUpd->execute([$s_instansi1, $s_instansi2, $s_alamat1, $s_alamat2, $s_camat, $s_nip, $s_jabatan_ttd, $s_golongan_ttd, $logo_name]);
            
            $success = "Pengaturan Kop Surat berhasil disimpan.";
            
            // Refresh settings
            $stmtS = $pdo->query("SELECT * FROM pengaturan LIMIT 1");
            $settings = $stmtS->fetch();
        } catch (PDOException $e) {
            $error = "Gagal simpan pengaturan: " . $e->getMessage();
        }
    }
}

$page_title = 'Profil & Pengaturan';
include 'includes/header.php';

// Get unread disposisi count for quick links
require_once 'includes/notification_helper.php';
$unreadDisposisiCount = getUnreadDispositionCount($pdo, $_SESSION['user_id']);
?>

<!-- Quick Links Section (Mobile-Friendly) -->
<div class="row mb-4 animate-up">
    <div class="col-12">
        <h5 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-warning me-2"></i>Akses Cepat</h5>
        <div class="row g-3">
            <!-- Disposisi Quick Link -->
            <div class="col-6 col-md-4">
                <a href="modules/disposisi/index.php" class="text-decoration-none">
                    <div class="card-modern p-3 text-center border-0 h-100 <?= $unreadDisposisiCount > 0 ? 'border-2 border-danger border-opacity-25' : '' ?>" style="<?= $unreadDisposisiCount > 0 ? 'border-style: solid;' : '' ?>">
                        <div class="position-relative d-inline-block mb-2">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-2 d-inline-flex">
                                <i class="bi bi-envelope-fill text-primary fs-3"></i>
                            </div>
                            <?php if($unreadDisposisiCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $unreadDisposisiCount ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h6 class="fw-bold mb-0 small">Disposisi</h6>
                        <?php if($unreadDisposisiCount > 0): ?>
                            <small class="text-danger fw-bold"><?= $unreadDisposisiCount ?> Baru!</small>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            
            <!-- Galeri Quick Link -->
            <div class="col-6 col-md-4">
                <a href="galeri.php" class="text-decoration-none">
                    <div class="card-modern p-3 text-center border-0 h-100">
                        <div class="bg-success bg-opacity-10 rounded-3 p-2 d-inline-flex mb-2">
                            <i class="bi bi-images-fill text-success fs-3"></i>
                        </div>
                        <h6 class="fw-bold mb-0 small">Galeri Foto</h6>
                    </div>
                </a>
            </div>
            
            <!-- e-Kinerja Quick Link -->
            <div class="col-6 col-md-4">
                <a href="ekinerja.php" class="text-decoration-none">
                    <div class="card-modern p-3 text-center border-0 h-100">
                        <div class="bg-info bg-opacity-10 rounded-3 p-2 d-inline-flex mb-2">
                            <i class="bi bi-file-earmark-check-fill text-info fs-3"></i>
                        </div>
                        <h6 class="fw-bold mb-0 small">e-Kinerja</h6>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 animate-up">
    <!-- User Profile Column -->
    <div class="col-lg-5">
        <div class="card-modern h-100 p-4">
            <!-- Profile Photo Section - Click to Upload -->
            <div class="text-center mb-4">
                <?php 
                $profile_photo_path = null;
                if (!empty($user['foto_profil']) && file_exists('uploads/profil/' . $user['foto_profil'])) {
                    $profile_photo_path = 'uploads/profil/' . $user['foto_profil'];
                }
                ?>
                
                <!-- Clickable Profile Photo Container -->
                <div class="position-relative d-inline-block mb-3" 
                     style="cursor: pointer;" 
                     onclick="document.getElementById('foto_profil_input').click()"
                     title="Klik untuk ganti foto profil">
                    
                    <?php if ($profile_photo_path): ?>
                        <!-- Show uploaded photo -->
                        <img src="<?= $profile_photo_path ?>?v=<?= time() ?>" 
                             alt="Profile Photo" 
                             id="main-profile-photo"
                             class="rounded-circle" 
                             style="width: 140px; height: 140px; object-fit: cover; border: 5px solid var(--primary-color); box-shadow: 0 8px 25px rgba(0,0,0,0.15); transition: transform 0.3s ease;"
                             onmouseover="this.style.transform='scale(1.05)'"
                             onmouseout="this.style.transform='scale(1)'">
                    <?php else: ?>
                        <!-- Show green circle (clickable) -->
                        <div id="main-profile-photo" 
                             class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 140px; height: 140px; border: 5px solid var(--primary-color); box-shadow: 0 8px 25px rgba(0,0,0,0.1); transition: all 0.3s ease;"
                             onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 12px 30px rgba(0,0,0,0.2)'"
                             onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'">
                            <i class="bi bi-person-circle" style="font-size: 4rem;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Loading Overlay -->
                    <div id="upload-loading" 
                         class="position-absolute top-0 start-0 w-100 h-100 rounded-circle bg-dark bg-opacity-50 d-flex align-items-center justify-content-center" 
                         style="display: none !important;">
                        <div class="spinner-border text-light" role="status">
                            <span class="visually-hidden">Uploading...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden File Input -->
                <input type="file" 
                       id="foto_profil_input" 
                       accept="image/png, image/jpeg, image/jpg" 
                       style="display: none;"
                       onchange="uploadProfilePhoto(this)">
                
                <h4 class="fw-bold title-main mb-1">Identitas Saya</h4>
                <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 px-3 text-uppercase"><?= ucfirst($_SESSION['role']) ?> SYSTEM</div>
                
                <!-- Upload Instructions -->
                <div class="mt-3">
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary rounded-pill"
                            onclick="document.getElementById('foto_profil_input').click()">
                        <i class="bi bi-camera-fill me-1"></i> 
                        <?= $profile_photo_path ? 'Ganti' : 'Upload' ?> Foto Profil
                    </button>
                    <small class="text-muted d-block mt-2">
                        JPG/PNG, Maksimal 2MB
                    </small>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="mb-4">
                    <label class="text-label mb-2">Username</label>
                    <input type="text" class="form-control border-2 shadow-none bg-light text-muted" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                </div>

                <div class="mb-4">
                    <label class="text-label mb-2">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control border-2 shadow-none" value="<?= htmlspecialchars($user['nama']) ?>" required>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold text-uppercase d-block mb-1">Jabatan</label>
                        <div class="fw-bold text-primary"><?= $user['jabatan'] ? htmlspecialchars($user['jabatan']) : '<em>Belum diatur</em>' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold text-uppercase d-block mb-1">NIP</label>
                        <div class="fw-bold"><?= $user['nip'] ? htmlspecialchars($user['nip']) : '-' ?></div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-muted small fw-bold text-uppercase d-block mb-1">Hak Akses Bidang (Read-Only)</label>
                    <div class="p-3 bg-light border rounded-3 text-muted">
                        <?php if ($user['nama_bidang']): ?>
                            <i class="bi bi-building-fill me-2"></i><?= htmlspecialchars($user['nama_bidang']) ?>
                        <?php else: ?>
                            <i class="bi bi-globe me-2"></i>Semua Bidang (Akses Penuh)
                        <?php endif; ?>
                    </div>
                    <div class="extra-small text-muted mt-2">Hubungi Administrator jika ingin mengubah akses bidang.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">No. WhatsApp</label>
                    <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" placeholder="081234567xxx">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold d-flex justify-content-between">
                        Telegram Chat ID
                        <a href="https://t.me/userinfobot" target="_blank" class="small text-decoration-none"><i class="bi bi-question-circle me-1"></i>Cari ID Saya</a>
                    </label>
                    <input type="text" name="telegram_id" class="form-control font-monospace" value="<?= htmlspecialchars($user['telegram_id'] ?? '') ?>" placeholder="123456789">
                    <div class="form-text small">Diperlukan agar Pimpinan bisa menerima notifikasi tugas selesai di Telegram.</div>
                </div>
                <div class="mb-3 border-top pt-3 mt-3">
                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-shield-lock me-2"></i>Ganti Password</h6>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Password Lama (Wajib jika ingin mengganti)</label>
                        <input type="password" name="password_lama" class="form-control" placeholder="••••••••">
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Password Baru</label>
                            <input type="password" name="password_baru" class="form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Konfirmasi Password</label>
                            <input type="password" name="konfirmasi_password" class="form-control" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="form-text small mt-2">Biarkan semua field password kosong jika hanya ingin update profil.</div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-modern btn-primary-modern py-2">
                        <i class="bi bi-shield-check me-2"></i> Update Profil
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin Settings Column -->
    <?php if ($is_admin): ?>
    <div class="col-lg-7">
        <div class="card-modern h-100 p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 me-3">
                    <i class="bi bi-file-earmark-richtext fs-3"></i>
                </div>
                <div>
                    <h4 class="fw-bold title-main mb-0">Pengaturan Kop Surat</h4>
                    <p class="text-muted small mb-0">Atur dokumen identitas instansi untuk laporan PDF</p>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_settings" value="1">
                
                <!-- Logo Upload Section -->
                <div class="p-3 bg-light rounded-4 mb-4 border-2 border-dashed text-center">
                    <label class="text-label mb-2 d-inline-block">Logo Instansi</label>
                    <div class="mb-3">
                        <?php 
                        $current_logo = 'assets/img/logo.png'; // fallback global
                        if(!empty($settings['logo']) && file_exists('assets/img/'.$settings['logo'])) {
                            $current_logo = 'assets/img/'.$settings['logo'];
                        }
                        ?>
                        <img src="<?= $current_logo ?>" alt="Logo" class="img-thumbnail shadow-sm mb-2" style="max-height: 80px;">
                    </div>
                    <div class="input-group input-group-sm">
                        <input type="file" name="logo" class="form-control" accept="image/png, image/jpeg">
                        <label class="input-group-text"><i class="bi bi-upload"></i></label>
                    </div>
                    <small class="text-muted mt-2 d-block">PNG/JPG, Ratio 1:1 direkomendasikan</small>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="text-label mb-2">Baris 1 (Instansi Induk)</label>
                        <input type="text" name="instansi_1" class="form-control border-2 shadow-none" value="<?= htmlspecialchars($settings['nama_instansi_1'] ?? '') ?>" placeholder="PEMERINTAH KABUPATEN...">
                    </div>
                    <div class="col-md-6">
                        <label class="text-label mb-2">Baris 2 (Unit Kerja)</label>
                        <input type="text" name="instansi_2" class="form-control border-2 shadow-none fw-bold" value="<?= htmlspecialchars($settings['nama_instansi_2'] ?? '') ?>" placeholder="KECAMATAN...">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="text-label mb-2">Alamat Kantor</label>
                    <input type="text" name="alamat_1" class="form-control border-2 shadow-none" value="<?= htmlspecialchars($settings['alamat_1'] ?? '') ?>" placeholder="Jl. Raya Besuk No. 1...">
                </div>
                
                <div class="mb-4">
                    <label class="text-label mb-2">Informasi Kontak</label>
                    <input type="text" name="alamat_2" class="form-control border-2 shadow-none" value="<?= htmlspecialchars($settings['alamat_2'] ?? '') ?>" placeholder="Email / Telp...">
                </div>

                <div class="p-3 bg-light rounded-4 mb-4">
                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-pen-fill me-2"></i>Pejabat Penandatangan</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Jabatan Penandatangan</label>
                            <input type="text" name="jabatan_ttd" class="form-control border-0 shadow-sm fw-bold" value="<?= htmlspecialchars($settings['jabatan_ttd'] ?? 'Camat Besuk') ?>" placeholder="Contoh: Camat Besuk / Sekcam Besuk">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Pangkat / Golongan</label>
                            <select name="golongan_ttd" class="form-select border-0 shadow-sm">
                                <?php 
                                $pangkat_list = [
                                    "Juru Muda (I/a)", "Juru Muda Tingkat I (I/b)", "Juru (I/c)", "Juru Tingkat I (I/d)",
                                    "Pengatur Muda (II/a)", "Pengatur Muda Tingkat I (II/b)", "Pengatur (II/c)", "Pengatur Tingkat I (II/d)",
                                    "Penata Muda (III/a)", "Penata Muda Tingkat I (III/b)", "Penata (III/c)", "Penata Tingkat I (III/d)",
                                    "Pembina (IV/a)", "Pembina Tingkat I (IV/b)", "Pembina Utama Muda (IV/c)", "Pembina Utama Madya (IV/d)", "Pembina Utama (IV/e)"
                                ];
                                foreach($pangkat_list as $pl): ?>
                                    <option value="<?= $pl ?>" <?= ($settings['golongan_ttd'] ?? '') == $pl ? 'selected' : '' ?>><?= $pl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Nama Lengkap</label>
                            <input type="text" name="nama_camat" class="form-control border-0 shadow-sm" value="<?= htmlspecialchars($settings['nama_camat'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold text-uppercase mb-1 d-block">NIP</label>
                            <input type="text" name="nip_camat" class="form-control border-0 shadow-sm" value="<?= htmlspecialchars($settings['nip_camat'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-modern btn-light border py-2 fw-bold">
                        <i class="bi bi-save me-2 text-primary"></i> Simpan Konfigurasi Kop
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Alerts / Toasts -->
<?php if ($success || $error): ?>
<div class="position-fixed bottom-0 end-0 p-4" style="z-index: 2000;">
    <div class="toast show animate-up border-0 shadow-lg rounded-4 overflow-hidden" role="alert">
        <div class="d-flex p-3 <?= $success ? 'bg-success text-white' : 'bg-danger text-white' ?>">
            <div class="me-2"><i class="bi <?= $success ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i></div>
            <div class="fw-bold"><?= $success ?: $error ?></div>
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Modern AJAX Upload with Auto-Save
function uploadProfilePhoto(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    
    // Validate file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
        showToast('Ukuran file terlalu besar! Maksimal 2MB.', 'error');
        input.value = '';
        return;
    }
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
        showToast('Format file tidak didukung! Gunakan JPG atau PNG.', 'error');
        input.value = '';
        return;
    }
    
    // Show loading
    const loadingOverlay = document.getElementById('upload-loading');
    const photoElement = document.getElementById('main-profile-photo');
    loadingOverlay.style.display = 'flex';
    
    // Preview image immediately
    const reader = new FileReader();
    reader.onload = function(e) {
        // Update photo preview
        if (photoElement.tagName === 'IMG') {
            photoElement.src = e.target.result;
        } else {
            // Replace div with img
            const container = document.getElementById('profile-photo-container');
            const newImg = document.createElement('img');
            newImg.id = 'main-profile-photo';
            newImg.src = e.target.result;
            newImg.alt = 'Profile Photo';
            newImg.className = 'rounded-circle';
            newImg.style.cssText = 'width: 140px; height: 140px; object-fit: cover; border: 5px solid var(--primary-color); box-shadow: 0 8px 25px rgba(0,0,0,0.15); transition: all 0.3s ease;';
            container.replaceChild(newImg, photoElement);
        }
    };
    reader.readAsDataURL(file);
    
    // Upload via AJAX
    const formData = new FormData();
    formData.append('foto_profil', file);
    
    fetch('upload_foto_profil.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loadingOverlay.style.display = 'none';
        
        if (data.success) {
            // Update photo with new URL
            const photoElement = document.getElementById('main-profile-photo');
            if (photoElement.tagName === 'IMG') {
                photoElement.src = data.photo_url;
            }
            
            showToast('✓ Foto profil berhasil diperbarui!', 'success');
            
            // Reload page after 1.5 seconds to update header avatar
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        loadingOverlay.style.display = 'none';
        showToast('Terjadi kesalahan saat upload. Silakan coba lagi.', 'error');
        console.error('Upload error:', error);
    });
    
    // Clear input
    input.value = '';
}

// Toast Notification Function
function showToast(message, type = 'success') {
    // Remove existing toast if any
    const existingToast = document.getElementById('upload-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast
    const toast = document.createElement('div');
    toast.id = 'upload-toast';
    toast.className = 'position-fixed bottom-0 end-0 p-4 animate-up';
    toast.style.zIndex = '2000';
    
    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
    const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    
    toast.innerHTML = `
        <div class="toast show border-0 shadow-lg rounded-4 overflow-hidden" role="alert">
            <div class="d-flex p-3 ${bgClass} text-white">
                <div class="me-2"><i class="bi ${icon} fs-5"></i></div>
                <div class="fw-bold">${message}</div>
                <button type="button" class="btn-close btn-close-white ms-auto" onclick="this.closest('#upload-toast').remove()"></button>
            </div>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        if (document.getElementById('upload-toast')) {
            toast.remove();
        }
    }, 4000);
}
</script>

<?php include 'includes/footer.php'; ?>

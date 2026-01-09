<?php
// jabatan_tambah.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

if ($_SESSION['role'] !== 'admin') {
    $_SESSION['alert'] = ['msg' => 'Akses ditolak!', 'type' => 'danger'];
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? null;
$jabatan = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM jabatan WHERE id_jabatan = ?");
    $stmt->execute([$id]);
    $jabatan = $stmt->fetch();
}

include 'includes/header.php';

// Fetch all positions for parent selection
$stmt = $db->query("SELECT id_jabatan, nama_jabatan, level_hierarki FROM jabatan ORDER BY level_hierarki ASC");
$all_positions = $stmt->fetchAll();
?>

<div class="mb-4 pt-2">
    <h2 class="fw-bold mb-1"><?= $id ? 'Edit' : 'Tambah' ?> Jabatan</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="jabatan.php">Struktur Organisasi</a></li>
            <li class="breadcrumb-item active"><?= $id ? 'Edit' : 'Tambah' ?></li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom p-4 border-0 shadow-sm mb-4">
            <form action="jabatan_proses.php" method="POST">
                <?php if ($id): ?>
                    <input type="hidden" name="id_jabatan" value="<?= $id ?>">
                <?php endif; ?>
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Nama Jabatan</label>
                        <input type="text" name="nama_jabatan" class="form-control" placeholder="Contoh: Kasi Pemerintahan" value="<?= $jabatan['nama_jabatan'] ?? '' ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Level Hierarki</label>
                        <select name="level_hierarki" class="form-select" required>
                            <option value="1" <?= (isset($jabatan['level_hierarki']) && $jabatan['level_hierarki'] == 1) ? 'selected' : '' ?>>Level 1 (Pimpinan Tertinggi / Camat)</option>
                            <option value="2" <?= (isset($jabatan['level_hierarki']) && $jabatan['level_hierarki'] == 2) ? 'selected' : '' ?>>Level 2 (Sekcam)</option>
                            <option value="3" <?= (isset($jabatan['level_hierarki']) && $jabatan['level_hierarki'] == 3) ? 'selected' : '' ?>>Level 3 (Kasi / Kasubbag)</option>
                            <option value="4" <?= (isset($jabatan['level_hierarki']) && $jabatan['level_hierarki'] == 4) ? 'selected' : '' ?>>Level 4 (Staf / Pelaksana)</option>
                        </select>
                        <small class="text-muted">Angka lebih kecil berarti posisi lebih tinggi.</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Atasan Langsung (Parent)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- Tidak Ada Atasan --</option>
                            <?php foreach ($all_positions as $p): ?>
                                <?php if ($id && $p['id_jabatan'] == $id) continue; // Prevent self-parenting ?>
                                <option value="<?= $p['id_jabatan'] ?>" <?= (isset($jabatan['parent_id']) && $jabatan['parent_id'] == $p['id_jabatan']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nama_jabatan']) ?> (Level <?= $p['level_hierarki'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 mt-4">
                        <label class="form-label fw-bold d-block mb-3 border-bottom pb-2">Kewenangan Jabatan (Role Permissions)</label>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="form-check form-switch p-2 border rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="can_buat_surat" value="1" id="perm1" <?= (!empty($jabatan['can_buat_surat'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold small" for="perm1">Buat Surat</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="form-check form-switch p-2 border rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="can_disposisi" value="1" id="perm2" <?= (!empty($jabatan['can_disposisi'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold small" for="perm2">Disposisi</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="form-check form-switch p-2 border rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="can_verifikasi" value="1" id="perm3" <?= (!empty($jabatan['can_verifikasi'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold small" for="perm3">Verifikasi</label>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="form-check form-switch p-2 border rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="can_tanda_tangan" value="1" id="perm4" <?= (!empty($jabatan['can_tanda_tangan'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold small" for="perm4">Tanda Tangan</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 pt-4 border-top">
                        <button type="submit" name="<?= $id ? 'update' : 'simpan' ?>" class="btn btn-primary px-4 me-2">
                            <i class="fa-solid fa-save me-2"></i> Simpan Data
                        </button>
                        <a href="jabatan.php" class="btn btn-light px-4">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-custom p-4 bg-light border-0 shadow-sm border-start border-5 border-primary">
            <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i> Ketentuan Struktur:</h5>
            <ol class="small mb-0">
                <li class="mb-2"><strong>Level Hierarki</strong> digunakan untuk menentukan urutan jabatan secara vertikal (Camat adalah Level 1).</li>
                <li class="mb-2"><strong>Atasan Langsung</strong> mendefinisikan hubungan pelaporan. Hal ini akan berpengaruh pada default tujuan disposisi surat.</li>
                <li class="mb-2"><strong>Kewenangan</strong> memberikan akses ke fitur-fitur spesifik di aplikasi (Role-Based Access Control).</li>
                <li class="mb-0">Perubahan pada jabatan akan langsung berdampak pada user yang memegang jabatan tersebut.</li>
            </ol>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

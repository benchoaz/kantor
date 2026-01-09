<?php
// surat_keluar_edit.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: surat_keluar.php");
    exit;
}

// Fetch Existing Data
$stmt = $db->prepare("SELECT * FROM surat_keluar WHERE id_sk = ?");
$stmt->execute([$id]);
$surat = $stmt->fetch();

if (!$surat) {
    $_SESSION['alert'] = ['msg' => 'Data surat tidak ditemukan!', 'type' => 'danger'];
    header("Location: surat_keluar.php");
    exit;
}

include 'includes/header.php';
?>

<div class="mb-4 pt-2">
    <h2 class="fw-bold mb-1">Edit Surat Keluar</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="surat_keluar.php">Surat Keluar</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom p-4 border-0 shadow-sm mb-4">
            <form action="surat_keluar_proses.php" method="POST">
                <input type="hidden" name="id_sk" value="<?= $id ?>">
                
                <div class="row g-3">
                    <div class="col-md-12 text-center py-2 bg-light rounded mb-2">
                        <span class="small text-muted text-uppercase fw-bold">Status Saat Ini: </span>
                        <span class="badge bg-info text-uppercase"><?= $surat['status'] ?></span>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Tujuan Surat</label>
                        <input type="text" name="tujuan" class="form-control" value="<?= htmlspecialchars($surat['tujuan']) ?>" placeholder="Contoh: Kepala Dinas Pendidikan Kota..." required>
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Perihal</label>
                        <input type="text" name="perihal" class="form-control" value="<?= htmlspecialchars($surat['perihal']) ?>" placeholder="Isi ringkas perihal surat..." required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tanggal Surat</label>
                        <input type="date" name="tgl_surat" class="form-control" value="<?= $surat['tgl_surat'] ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Klasifikasi</label>
                        <select name="klasifikasi" class="form-select">
                            <option value="400" <?= ($surat['klasifikasi'] == '400') ? 'selected' : '' ?>>Kesejahteraan Rakyat (400)</option>
                            <option value="100" <?= ($surat['klasifikasi'] == '100') ? 'selected' : '' ?>>Pemerintahan (100)</option>
                            <option value="500" <?= ($surat['klasifikasi'] == '500') ? 'selected' : '' ?>>Perekonomian (500)</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Isi Surat</label>
                        <textarea name="isi_surat" class="form-control" rows="10" placeholder="Ketikkan narasi surat di sini..." required><?= htmlspecialchars($surat['isi_surat']) ?></textarea>
                    </div>

                    <?php if(!empty($surat['no_surat'])): ?>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Nomor Surat (Sudah Teregistrasi)</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($surat['no_surat']) ?>" readonly>
                        <small class="text-danger">* Nomor surat yang sudah digenerate tidak dapat diubah manual di sini.</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-12 pt-3 border-top mt-4">
                        <button type="submit" name="update" class="btn btn-primary px-5 fw-bold shadow">
                            <i class="fa-solid fa-save me-2"></i> Perbarui Surat
                        </button>
                        <a href="surat_keluar.php" class="btn btn-light px-4 ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-custom p-4 bg-light border-0 shadow-sm border-start border-5 border-primary">
            <h6 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-circle-info me-2"></i> Catatan Edit:</h6>
            <ol class="small mb-0 text-muted ps-3">
                <li class="mb-2">Anda hanya dapat mengubah isi perihal, tujuan, dan klasifikasi.</li>
                <li class="mb-2">Setelah diubah, status surat akan tetap pada status sebelumnya kecuali ada aksi verifikasi ulang.</li>
                <li class="mb-0">Aktivitas pengeditan ini akan tercatat dalam log audit sistem.</li>
            </ol>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

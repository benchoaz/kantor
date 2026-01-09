<?php
// camera_keterangan.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role(['admin', 'operator', 'pimpinan', 'staff']);

$file = $_GET['file'] ?? '';
if (!$file || !file_exists('uploads/foto/' . $file)) {
    header("Location: index.php");
    exit;
}

$bidang_list = $pdo->query("SELECT * FROM bidang")->fetchAll();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = $_POST['judul'] ?? 'Dokumentasi Cepat';
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $lokasi = $_POST['lokasi'] ?? '';
    $bidang_id = $_POST['bidang_id'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? date('H:i');
    $jam_selesai = $_POST['jam_selesai'] ?? date('H:i');
    $created_by = $_SESSION['user_id'];

    if ($bidang_id) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO kegiatan (judul, tanggal, jam_mulai, jam_selesai, lokasi, bidang_id, penanggung_jawab, deskripsi, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$judul, $tanggal, $jam_mulai, $jam_selesai, $lokasi, $bidang_id, $_SESSION['nama'], $deskripsi, $created_by]);
            $kegiatan_id = $pdo->lastInsertId();

            $stmt_foto = $pdo->prepare("INSERT INTO foto_kegiatan (kegiatan_id, file) VALUES (?, ?)");
            $stmt_foto->execute([$kegiatan_id, $file]);

            $pdo->commit();
            header("Location: kegiatan_detail.php?id=$kegiatan_id&msg=success");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = "Harap pilih bidang.";
    }
}

$page_title = 'Detail Foto Kamera';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm overflow-hidden mb-4">
            <img src="uploads/foto/<?= htmlspecialchars($file) ?>" class="card-img-top" alt="Captured Photo">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Lengkapi Informasi Foto</h5>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Judul / Nama Kegiatan</label>
                        <input type="text" name="judul" class="form-control" required placeholder="Contoh: Dokumentasi Lapangan">
                    </div>
                    
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control" value="<?= date('H:i') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control" value="<?= date('H:i') ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Bidang</label>
                            <select name="bidang_id" class="form-select" required>
                                <option value="">Pilih Bidang</option>
                                <?php foreach ($bidang_list as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama_bidang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Lokasi</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Tempat pengambilan foto">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Penjelasan / Keterangan</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Berikan sedikit keterangan tentang foto ini..."></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Simpan Dokumentasi
                        </button>
                        <a href="index.php" class="btn btn-link text-muted" onclick="return confirm('Batalkan dan hapus foto ini?')">Batalkan</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

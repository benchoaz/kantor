<?php
// galeri.php
require_once 'config/database.php';
$page_title = 'Galeri Foto';
$active_page = 'galeri';
include 'includes/header.php';

$bidang_id = $_GET['bidang_id'] ?? '';
$bulan = $_GET['bulan'] ?? '';

$params = [];
$sql = "SELECT f.*, k.judul, k.tanggal, b.nama_bidang 
        FROM foto_kegiatan f 
        JOIN kegiatan k ON f.kegiatan_id = k.id 
        JOIN bidang b ON k.bidang_id = b.id";
$where = [];

if ($bidang_id) {
    $where[] = "k.bidang_id = ?";
    $params[] = $bidang_id;
}
if ($bulan) {
    $where[] = "MONTH(k.tanggal) = ?";
    $params[] = $bulan;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY k.tanggal DESC, f.uploaded_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fotos = $stmt->fetchAll();

$bidang_list = $pdo->query("SELECT * FROM bidang")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-up">
    <div>
        <h3 class="title-main mb-0">Galeri Dokumentasi</h3>
        <p class="text-muted small mb-0">Total ditemukan: <?= count($fotos) ?> foto</p>
    </div>
</div>

<!-- Filters -->
<div class="card-modern border-0 mb-4 animate-up" style="animation-delay: 0.1s;">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="text-label mb-2">Bidang</label>
                <select name="bidang_id" class="form-select border-2 shadow-none">
                    <option value="">Semua Bidang</option>
                    <?php foreach ($bidang_list as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($bidang_id == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['nama_bidang']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="text-label mb-2">Bulan</label>
                <select name="bulan" class="form-select border-2 shadow-none">
                    <option value="">Semua Bulan</option>
                    <?php 
                    $months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                    foreach ($months as $idx => $m): ?>
                        <option value="<?= $idx + 1 ?>" <?= ($bulan == ($idx + 1)) ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-modern btn-light border w-100">
                    <i class="bi bi-filter me-2"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Gallery Grid -->
<div class="row g-3 animate-up" style="animation-delay: 0.2s;">
    <?php foreach ($fotos as $f): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card-modern border-0 h-100 overflow-hidden group">
                <a href="kegiatan_detail.php?id=<?= $f['kegiatan_id'] ?>" class="text-decoration-none p-0">
                    <div class="position-relative overflow-hidden" style="height: 180px;">
                        <img src="uploads/foto/<?= $f['file'] ?>" class="w-100 h-100 object-fit-cover transition-all group-hover-scale" alt="Foto">
                        <div class="position-absolute bottom-0 start-0 w-100 p-2 bg-gradient-dark text-white opacity-0 group-hover-opacity-100 transition-all">
                            <small><i class="bi bi-eye me-1"></i> Detail Kegiatan</small>
                        </div>
                    </div>
                </a>
                <div class="card-body p-3">
                    <p class="fw-bold text-dark small mb-1 text-truncate" title="<?= htmlspecialchars($f['judul']) ?>">
                        <?= htmlspecialchars($f['judul']) ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge rounded-pill bg-light text-muted border extra-small px-2" style="font-size: 0.65rem;">
                            <?= htmlspecialchars($f['nama_bidang']) ?>
                        </span>
                        <span class="text-muted" style="font-size: 0.65rem;">
                            <i class="bi bi-calendar-event me-1"></i><?= date('d/m/y', strtotime($f['tanggal'])) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (empty($fotos)): ?>
        <div class="col-12 text-center py-5">
            <img src="assets/img/empty.svg" alt="Empty" style="width: 150px; opacity: 0.3;" class="mb-4">
            <h5 class="text-muted">Tidak ada foto ditemukan.</h5>
            <p class="text-muted small">Coba sesuaikan filter pencarian Anda.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.bg-gradient-dark {
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
}
.group:hover .group-hover-scale {
    transform: scale(1.1);
}
.group:hover .group-hover-opacity-100 {
    opacity: 1 !important;
}
.transition-all {
    transition: all 0.3s ease-in-out;
}
</style>

<?php include 'includes/footer.php'; ?>

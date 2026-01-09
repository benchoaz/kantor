<?php
// kegiatan.php
require_once 'config/database.php';
$page_title = 'Daftar Kegiatan';
$active_page = 'kegiatan';
include 'includes/header.php';

$bidang_id = $_GET['bidang_id'] ?? '';
$bulan = $_GET['bulan'] ?? '';

$params = [];
$sql = "SELECT k.*, b.nama_bidang FROM kegiatan k JOIN bidang b ON k.bidang_id = b.id";
$where = [];

// Role-based filtering
if (!has_role(['admin'])) {
    // Non-admins only see their own Bidang
    if (!empty($_SESSION['bidang_id'])) {
        $where[] = "k.bidang_id = ?";
        $params[] = $_SESSION['bidang_id'];
    }
    
    // Optional: Also restrict to own created items if strictly private? 
    // Request says "sesuai bidangnya", so Bidang-level sharing is implied.
} else {
    // Admin filters
    if ($bidang_id) {
        $where[] = "k.bidang_id = ?";
        $params[] = $bidang_id;
    }
}

if ($bulan) {
    $where[] = "MONTH(k.tanggal) = ?";
    $params[] = $bulan;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY k.tanggal DESC, k.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();

$bidang_list = $pdo->query("SELECT * FROM bidang")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-up">
    <div>
        <h3 class="title-main mb-0">Daftar Kegiatan</h3>
        <p class="text-muted small mb-0">Total ditemukan: <?= count($activities) ?> data</p>
    </div>
    <a href="kegiatan_tambah.php" class="btn btn-modern btn-primary-modern shadow-sm">
        <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Tambah Kegiatan</span>
    </a>
</div>

<!-- Filters -->
<div class="card-modern border-0 mb-4 animate-up" style="animation-delay: 0.1s;">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="text-label mb-2">Filter Bidang</label>
                <select name="bidang_id" class="form-select border-2 shadow-none" <?= (!has_role(['admin']) && !empty($_SESSION['bidang_id'])) ? 'disabled' : '' ?>>
                    <option value="">Semua Bidang</option>
                    <?php foreach ($bidang_list as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($bidang_id == $b['id'] || (!has_role(['admin']) && $_SESSION['bidang_id'] == $b['id'])) ? 'selected' : '' ?>><?= htmlspecialchars($b['nama_bidang']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if(!has_role(['admin']) && !empty($_SESSION['bidang_id'])): ?>
                    <input type="hidden" name="bidang_id" value="<?= $_SESSION['bidang_id'] ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="text-label mb-2">Filter Bulan</label>
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
                    <i class="bi bi-search me-2"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Data List -->
<div class="kegiatan-list animate-up" style="animation-delay: 0.2s;">
    <?php if (empty($activities)): ?>
        <div class="card-modern border-0 p-5 text-center">
            <img src="assets/img/empty.svg" alt="Empty" style="width: 140px; opacity: 0.5;" class="mb-3 d-block mx-auto">
            <h5 class="text-muted">Tidak ada kegiatan yang ditemukan.</h5>
            <p class="small text-muted">Coba ubah filter atau tambah kegiatan baru.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($activities as $k): ?>
                <?php
                $st = $k['status'] ?? 'draft';
                $badges = [
                    'draft' => 'bg-secondary',
                    'pending' => 'bg-warning text-dark',
                    'verified' => 'bg-success',
                    'rejected' => 'bg-danger',
                    'revision' => 'bg-danger text-white'
                ];
                $lbls = [
                    'draft' => 'Draft',
                    'pending' => 'Menunggu',
                    'verified' => 'Terverifikasi',
                    'rejected' => 'Ditolak',
                    'revision' => 'Revisi'
                ];
                ?>
                <div class="col-12">
                    <div class="card-modern border-0 overflow-hidden activity-card-pro">
                        <div class="card-body p-0">
                            <div class="d-flex flex-column flex-md-row">
                                <!-- Info Side -->
                                <div class="p-3 p-md-4 flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge rounded-pill <?= $badges[$st] ?? 'bg-secondary' ?> mb-2">
                                            <?= $lbls[$st] ?? ucfirst($st) ?>
                                        </span>
                                        <div class="text-muted small d-flex align-items-center">
                                            <i class="bi bi-calendar3 me-1"></i> <?= format_tanggal_indonesia($k['tanggal'], false) ?>
                                        </div>
                                    </div>
                                    
                                    <h5 class="fw-bold mb-2"><?= htmlspecialchars($k['judul']) ?></h5>
                                    
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <div class="d-flex align-items-center text-muted small me-3">
                                            <div class="icon-circle-xs bg-light me-2">
                                                <i class="bi bi-briefcase"></i>
                                            </div>
                                            <?= htmlspecialchars($k['nama_bidang']) ?>
                                        </div>
                                        <?php if ($k['tipe_kegiatan'] !== 'biasa'): ?>
                                            <div class="d-flex align-items-center text-primary fw-bold small">
                                                <i class="bi bi-star-fill me-1"></i> <?= strtoupper($k['tipe_kegiatan']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Action Side -->
                                <div class="bg-light bg-opacity-50 p-3 p-md-4 d-flex align-items-center justify-content-between justify-content-md-center border-top border-md-start border-light" style="min-width: 150px;">
                                    <a href="kegiatan_detail.php?id=<?= $k['id'] ?>" class="btn btn-primary-modern rounded-pill px-4 shadow-sm w-100 w-md-auto">
                                        Detail
                                    </a>
                                    
                                    <?php if (has_role(['admin', 'operator'])): ?>
                                        <div class="dropdown ms-2">
                                            <button class="btn btn-white shadow-sm rounded-circle p-2" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                <li><a class="dropdown-item" href="kegiatan_edit.php?id=<?= $k['id'] ?>"><i class="bi bi-pencil me-2"></i> Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="kegiatan_hapus.php?id=<?= $k['id'] ?>" onclick="return confirm('Hapus kegiatan ini?')"><i class="bi bi-trash me-2"></i> Hapus</a></li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .activity-card-pro { transition: transform 0.2s, box-shadow 0.2s; }
    .activity-card-pro:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important; }
    .icon-circle-xs {
        width: 24px; height: 24px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.75rem;
    }
    @media (min-width: 768px) {
        .border-md-start { border-left: 1px solid #dee2e6 !important; }
    }
</style>

<?php include 'includes/footer.php'; ?>

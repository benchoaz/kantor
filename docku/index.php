<?php
// index.php
require_once 'config/database.php';
$page_title = 'Beranda';
$active_page = 'dashboard';
include 'includes/header.php';

// Get some stats
$stmt = $pdo->query("SELECT COUNT(*) FROM kegiatan");
$total_kegiatan = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM foto_kegiatan");
$total_foto = $stmt->fetchColumn();

try {
    // Fetch latest activities with visibility rules
    $where_dash = [];
    $params_dash = [];

    // Check if status column exists effectively by trying the query or just handling exception
    // We assume column existence check is skipped for performance, catch exception instead.
    
    if (!is_management_role() && !has_role(['operator'])) {
        $where_dash[] = "k.created_by = ?";
        $params_dash[] = $_SESSION['user_id'];
    } else {
        // Try/Catch will handle if 'status' column is missing (migration not run)
        $where_dash[] = "(k.status != 'draft' OR k.created_by = ?)";
        $params_dash[] = $_SESSION['user_id'];
    }

    $sql_dash = "SELECT k.*, b.nama_bidang FROM kegiatan k JOIN bidang b ON k.bidang_id = b.id";
    if (!empty($where_dash)) {
        $sql_dash .= " WHERE " . implode(" AND ", $where_dash);
    }
    $sql_dash .= " ORDER BY k.tanggal DESC, k.created_at DESC LIMIT 5";

    $stmt = $pdo->prepare($sql_dash);
    $stmt->execute($params_dash);
    $recent_activities = $stmt->fetchAll();
} catch (Exception $e) {
    // Fail gracefully so the dashboard still loads buttons
    $recent_activities = [];
    $db_error = "Database belum dimigrasi! Silakan jalankan tools/migrate_002.php";
}

// Check for reports needing revision (for current user)
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as revision_count 
        FROM kegiatan 
        WHERE created_by = ? AND status = 'revision'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $revision_data = $stmt->fetch();
    $revision_count = $revision_data['revision_count'] ?? 0;
} catch (Exception $e) {
    $revision_count = 0;
}
?>

<!-- Welcome Hero Section (Compact for Admin) -->
<?php if (isset($db_error)): ?>
<div class="row animate-up">
    <div class="col-12">
        <div class="alert alert-danger shadow-sm border-0 rounded-4 mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>PENTING:</strong> <?= $db_error ?>
            <a href="tools/migrate_002.php" class="btn btn-sm btn-danger ms-2">Klik Disini untuk Fix</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($revision_count > 0): ?>
<div class="row animate-up">
    <div class="col-12">
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #fff3cd 0%, #fff8e1 100%);">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="bi bi-exclamation-circle-fill text-warning" style="font-size: 2rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="alert-heading fw-bold mb-1">
                        <i class="bi bi-arrow-repeat me-1"></i> Laporan Perlu Diralat
                    </h6>
                    <p class="mb-2">Anda memiliki <strong><?= $revision_count ?></strong> laporan yang dikembalikan untuk perbaikan.</p>
                    <a href="kegiatan.php?status=revision" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil-square me-1"></i> Lihat & Perbaiki Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mb-<?= ($_SESSION['role'] === 'admin') ? '4' : '5' ?>">
    <div class="col-md-12">
        <div class="hero-gradient shadow-lg <?= ($_SESSION['role'] === 'admin') ? 'py-4' : 'py-5' ?>">
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="kegiatan_tambah.php" class="btn-elegant btn-white-elegant text-decoration-none px-<?= ($_SESSION['role'] === 'admin') ? '4' : '5' ?> py-3 fs-6">
                    <i class="bi bi-plus-circle-fill me-2"></i> Tambah Kegiatan
                </a>
                <a href="join_kegiatan.php" class="btn-elegant btn-white-elegant text-decoration-none px-<?= ($_SESSION['role'] === 'admin') ? '4' : '5' ?> py-3 fs-6">
                    <i class="bi bi-people-fill me-2"></i> Gabung Tim Lapangan
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (is_management_role()): ?>
<!-- Admin & Management Panel -->
<div class="row g-3 mb-5 animate-up" style="animation-delay: 0.1s;">
    <div class="col-12">
        <h5 class="fw-bold mb-3 d-flex align-items-center">
            <i class="bi bi-shield-lock-fill text-primary me-2"></i> Panel Manajemen
        </h5>
    </div>
    <div class="col-6 col-md-3">
        <a href="users.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 text-center admin-card">
                <div class="icon-box bg-success bg-opacity-10 text-success mx-auto mb-2">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="fw-bold small text-dark">User</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="output_kinerja.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 text-center admin-card">
                <div class="icon-box bg-primary bg-opacity-10 text-primary mx-auto mb-2">
                    <i class="bi bi-list-check"></i>
                </div>
                <div class="fw-bold small text-dark">Output</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="modules/disposisi/index.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 text-center admin-card">
                <div class="icon-box bg-warning bg-opacity-10 text-warning mx-auto mb-2">
                    <i class="bi bi-envelope-paper-fill"></i>
                </div>
                <div class="fw-bold small text-dark">Disposisi</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="modules/integrasi/settings.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 text-center admin-card">
                <div class="icon-box bg-purple-light text-purple mx-auto mb-2">
                    <i class="bi bi-cpu-fill"></i>
                </div>
                <div class="fw-bold small text-dark">Integrasi</div>
            </div>
        </a>
    </div>
</div>

<style>
    .admin-card { transition: all 0.3s; }
    .admin-card:active { transform: scale(0.95); background: #f8f9fa; }
    .icon-box {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        font-size: 1.5rem;
    }
    .bg-purple-light { background: rgba(111, 66, 193, 0.1); }
    .text-purple { color: #6f42c1; }
</style>
<?php endif; ?>

<!-- Kamera Cepat Shortcut (Only show prominently for Staff, or secondary for Admin) -->
<div class="row mb-5 animate-up" style="animation-delay: <?= (is_management_role()) ? '0.2s' : '0.1s' ?>;">
    <div class="col-md-12">
        <a href="camera.php" class="text-decoration-none">
            <div class="shortcut-iphone shadow-lg <?= (is_management_role()) ? 'py-4' : '' ?>" style="<?= (is_management_role()) ? 'background: #2d3436; opacity: 0.9;' : '' ?>">
                <?php if (is_management_role()): ?>
                    <div class="d-flex align-items-center px-4">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="bi bi-camera-fill text-dark"></i>
                        </div>
                        <div class="text-start">
                            <h5 class="text-white fw-bold mb-0">Kamera Dokumentasi</h5>
                            <small class="text-white-50">Ambil foto lapangan cepat</small>
                        </div>
                        <i class="bi bi-chevron-right ms-auto text-white"></i>
                    </div>
                <?php else: ?>
                    <div class="shutter-btn">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                    <h2 class="title-tech mb-1">KLIK KAMERA CEPAT</h2>
                    <p class="desc-tech px-3 mb-0">Otomatis tambah Lokasi, Waktu, & GPS ke laporan</p>
                <?php endif; ?>
            </div>
        </a>
    </div>
</div>

<!-- Recent Activities List -->
<div class="row animate-up" style="animation-delay: 0.2s;">
    <div class="col-md-12">
        <div class="card-modern shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0" style="color: var(--text-main);">Kegiatan Terbaru</h5>
                <a href="kegiatan.php" class="text-decoration-none fw-bold small text-primary">Lihat Semua</a>
            </div>
            <div class="card-body px-0 px-lg-4 pb-4">
                <div class="activity-list">
                    <?php if (empty($recent_activities)): ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Belum ada data kegiatan.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $k): ?>
                            <?php
                            $st = $k['status'] ?? 'draft';
                            $st_badge = match($st) {
                                'draft' => 'bg-secondary',
                                'pending' => 'bg-warning text-dark',
                                'verified' => 'bg-success',
                                'rejected', 'revision' => 'bg-danger',
                                default => 'bg-light text-dark border'
                            };
                            ?>
                            <div class="activity-card p-3 border-bottom border-light hover-bg d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="activity-icon-box me-3 d-none d-sm-flex">
                                        <i class="bi bi-journal-text text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold fs-6 mb-1 activity-title"><?= htmlspecialchars($k['judul']) ?></div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="small text-muted d-flex align-items-center">
                                                <i class="bi bi-calendar3 me-1"></i> <?= format_tanggal_indonesia($k['tanggal'], false) ?>
                                            </span>
                                            <span class="small text-muted d-flex align-items-center">
                                                <i class="bi bi-briefcase me-1"></i> <?= htmlspecialchars($k['nama_bidang']) ?>
                                            </span>
                                            <span class="badge rounded-pill <?= $st_badge ?>" style="font-size: 0.65rem;"><?= ucfirst($st) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <a href="kegiatan_detail.php?id=<?= $k['id'] ?>" class="btn-detail-circle">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <style>
                    .activity-card { transition: all 0.2s ease; cursor: pointer; text-decoration: none; color: inherit; }
                    .activity-card:hover { background-color: rgba(135, 163, 128, 0.05); }
                    .activity-card:last-child { border-bottom: none !important; }
                    .activity-icon-box {
                        width: 42px; height: 42px; border-radius: 12px;
                        background: rgba(135, 163, 128, 0.1);
                        display: flex; align-items: center; justify-content: center;
                        font-size: 1.2rem;
                    }
                    .btn-detail-circle {
                        width: 34px; height: 34px; border-radius: 50%;
                        background: #f8f9fa; color: #666;
                        display: flex; align-items: center; justify-content: center;
                        transition: all 0.2s;
                    }
                    .activity-card:hover .btn-detail-circle { background: var(--primary-color, #87A380); color: white; transform: translateX(3px); }
                    .activity-title { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
                    
                    @media (max-width: 576px) {
                        .activity-title { font-size: 0.95rem; }
                        .activity-card { padding: 1rem 0.5rem !important; }
                    }
                </style>
            </div>
        </div>
    </div>
</div>

<!-- Notification Modal -->
<?php 
require_once 'includes/notification_helper.php';
$latestDisposisi = getLatestUnreadDisposition($pdo, $_SESSION['user_id']);
if ($latestDisposisi): 
?>
<div class="modal fade" id="notifModal" tabindex="-1" aria-labelledby="notifModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold" id="notifModalLabel"><i class="bi bi-exclamation-circle-fill me-2"></i>Disposisi Baru!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="bg-light rounded-3 p-3 mb-3 text-start border-start border-4 border-danger">
                    <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($latestDisposisi['perihal']) ?></h6>
                    <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($latestDisposisi['instruksi'])) ?></p>
                </div>
                <p class="mb-4">Segera tindak lanjuti instruksi pimpinan ini.</p>
                <div class="d-grid gap-2">
                    <a href="modules/disposisi/detail.php?id=<?= $latestDisposisi['id'] ?>" class="btn btn-danger fw-bold">
                        <i class="bi bi-camera-fill me-2"></i>TINDAK LANJUTI SEKARANG
                    </a>
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Nanti Saja</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var notifModal = new bootstrap.Modal(document.getElementById('notifModal'));
    notifModal.show();
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

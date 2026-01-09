<?php
// index.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check Login
require_auth();

// 1. Get Statistics
$count_sm = $db->query("SELECT COUNT(*) FROM surat_masuk")->fetchColumn();
$count_sk = $db->query("SELECT COUNT(*) FROM surat_keluar")->fetchColumn();
$count_disposisi = $db->query("SELECT COUNT(*) FROM disposisi")->fetchColumn();
$count_selesai = $db->query("SELECT COUNT(*) FROM surat_masuk WHERE status = 'selesai'")->fetchColumn();

// 2. Get Recent Surat Masuk
$recent_sm = $db->query("SELECT * FROM surat_masuk ORDER BY tgl_diterima DESC LIMIT 5")->fetchAll();

// 3. Get Recent Activity Logs
$logs = $db->query("SELECT l.*, u.nama_lengkap FROM log_aktivitas l 
                    LEFT JOIN users u ON l.id_user = u.id_user 
                    ORDER BY l.waktu DESC LIMIT 5")->fetchAll();

include 'includes/header.php';
?>

<div class="row pt-3 pt-md-4">
    <div class="col-12 mb-4 px-3 px-md-0">
        <h2 class="fw-bold mb-2" style="font-size: 1.75rem;">Dashboard</h2>
        <p class="text-secondary mb-0" style="font-size: 0.95rem;">
            Halo, <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong>. 
            Selamat datang kembali di SuratQu.
        </p>

        <?php 
        if ($_SESSION['can_verifikasi'] == 1 || $_SESSION['role'] == 'admin'): 
            $pending_count = $db->query("SELECT COUNT(*) FROM surat_keluar WHERE status = 'verifikasi'")->fetchColumn();
            if ($pending_count > 0):
        ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mt-3 p-3" style="border-left: 4px solid var(--color-orange) !important; border-radius: 12px;">
            <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-bell text-warning"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1" style="font-size: 0.9rem;">Persetujuan Diperlukan</div>
                <div class="small text-muted"><?= $pending_count ?> draf surat keluar menunggu verifikasi Anda.</div>
            </div>
            <a href="verifikasi_surat.php" class="btn btn-sm btn-warning fw-semibold px-3 shadow-sm">
                <i class="fa-solid fa-arrow-right me-1"></i> Lihat
            </a>
        </div>
        <?php endif; endif; ?>
    </div>
</div>

<div class="row g-3 g-md-4 mb-4 px-2 px-md-0">
    <!-- Stat Cards - Modern Design -->
    <div class="col-6 col-md-3">
        <div class="card card-custom p-3 p-md-4 border-0 shadow-sm hover-lift">
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon bg-primary bg-opacity-10 rounded-circle p-2 p-md-3">
                        <i class="fa-solid fa-inbox text-primary" style="font-size: 1.2rem;"></i>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1" style="font-size: 0.7rem;">Total</span>
                </div>
                <h3 class="mb-0 fw-bold text-dark"><?= $count_sm ?></h3>
                <small class="text-muted" style="font-size: 0.8rem;">Surat Masuk</small>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-3">
        <div class="card card-custom p-3 p-md-4 border-0 shadow-sm hover-lift">
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon bg-danger bg-opacity-10 rounded-circle p-2 p-md-3">
                        <i class="fa-solid fa-share-nodes text-danger" style="font-size: 1.2rem;"></i>
                    </div>
                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1" style="font-size: 0.7rem;">Total</span>
                </div>
                <h3 class="mb-0 fw-bold text-dark"><?= $count_disposisi ?></h3>
                <small class="text-muted" style="font-size: 0.8rem;">Disposisi</small>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-3">
        <div class="card card-custom p-3 p-md-4 border-0 shadow-sm hover-lift">
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon bg-info bg-opacity-10 rounded-circle p-2 p-md-3">
                        <i class="fa-solid fa-paper-plane text-info" style="font-size: 1.2rem;"></i>
                    </div>
                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2 py-1" style="font-size: 0.7rem;">Total</span>
                </div>
                <h3 class="mb-0 fw-bold text-dark"><?= $count_sk ?></h3>
                <small class="text-muted" style="font-size: 0.8rem;">Surat Keluar</small>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-md-3">
        <div class="card card-custom p-3 p-md-4 border-0 shadow-sm hover-lift">
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon bg-success bg-opacity-10 rounded-circle p-2 p-md-3">
                        <i class="fa-solid fa-check-double text-success" style="font-size: 1.2rem;"></i>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1" style="font-size: 0.7rem;">Done</span>
                </div>
                <h3 class="mb-0 fw-bold text-dark"><?= $count_selesai ?></h3>
                <small class="text-muted" style="font-size: 0.8rem;">Selesai</small>
            </div>
        </div>
    </div>
</div>

<style>
.hover-lift {
    transition: all 0.3s ease;
}
.hover-lift:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
}
.stat-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}
@media (max-width: 768px) {
    .stat-icon {
        width: 36px;
        height: 36px;
    }
    .stat-icon i {
        font-size: 1rem !important;
    }
}
</style>

<div class="row">
    <div class="col-md-8">
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Surat Masuk Terbaru</h5>
                <a href="surat_masuk.php" class="small text-decoration-none">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Agenda</th>
                            <th>Asal Surat</th>
                            <th>Perihal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_sm)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted small italic">Belum ada data surat.</td></tr>
                        <?php else: foreach ($recent_sm as $row): ?>
                        <tr>
                            <td class="fw-medium text-primary"><?= htmlspecialchars($row['no_agenda']) ?></td>
                            <td><?= htmlspecialchars($row['asal_surat']) ?></td>
                            <td><?= htmlspecialchars(substr($row['perihal'], 0, 50)) . (strlen($row['perihal']) > 50 ? '...' : '') ?></td>
                            <td>
                                <?php
                                $badge = [
                                    'baru' => 'bg-info',
                                    'disposisi' => 'bg-warning',
                                    'proses' => 'bg-primary',
                                    'selesai' => 'bg-success'
                                ];
                                ?>
                                <span class="badge <?= $badge[$row['status']] ?> rounded-pill small"><?= ucfirst($row['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-4 border-0 shadow-sm" style="min-height: 485px;">
            <h5 class="fw-bold mb-4">Aktivitas Terbaru 📜</h5>
            <div class="timeline-activity">
                <?php if (empty($logs)): ?>
                    <p class="text-muted small text-center italic mt-5">Belum ada aktivitas tercatat.</p>
                <?php else: foreach ($logs as $log): ?>
                    <div class="mb-4 d-flex align-items-start">
                        <div class="bg-light p-2 rounded me-3 text-primary shadow-sm" style="width: 40px; height: 40px; text-align: center;">
                            <i class="fa-solid fa-bolt-lightning small"></i>
                        </div>
                        <div>
                            <p class="mb-0 small fw-bold text-dark"><?= htmlspecialchars($log['aksi']) ?></p>
                            <div class="d-flex align-items-center">
                                <small class="text-muted pe-2" style="font-size: 10px;">
                                    <i class="fa-regular fa-clock me-1"></i> <?= time_since($log['waktu']) ?>
                                </small>
                                <span class="badge bg-soft-primary text-primary" style="font-size: 8px;"><?= htmlspecialchars($log['nama_lengkap']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.bg-soft-primary { background: rgba(52, 199, 89, 0.08); }
.text-secondary { color: var(--color-text-secondary) !important; }
</style>

<?php include 'includes/footer.php'; ?>

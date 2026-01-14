<?php
/**
 * Dashboard Premium
 * Layout modern dengan carousel horizontal dan action grid
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAuth();

$pageTitle = 'Dashboard';
$user = getCurrentUser();

// Ambil data dashboard dari API
$api = new ApiClient();

// TEMPORARILY DISABLED: Dashboard endpoint not yet implemented
// TODO: Create /pimpinan/dashboard endpoint in API
// $dashboardData = $api->get('/pimpinan/dashboard');

// Default data (fallback)
$stats = [
    'surat_masuk_hari_ini' => 0,
    'disposisi_belum_dibaca' => 0,
    'deadline_h1' => 0,
    'kegiatan_berjalan' => 0,
    'laporan_menunggu' => 0
];
$errorMsg = null;

// Uncomment when dashboard endpoint is ready
/*
if ($dashboardData['success'] && isset($dashboardData['data'])) {
    $stats = array_merge($stats, $dashboardData['data']);
} elseif (!$dashboardData['success']) {
    $errorMsg = $dashboardData['message'] ?? 'Gagal mengambil data dashboard.';
}
*/

include 'includes/header.php';
?>

<div class="welcome-section">
    <div class="date-text"><?php echo date('l, d F Y'); ?></div>
    <h1 class="welcome-text">Halo, <?php echo e($user['name']); ?> 👋</h1>
</div>

<?php if ($errorMsg): ?>
    <div class="alert-box alert-critical" style="margin-bottom: 20px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?php echo e($errorMsg); ?></span>
    </div>
<?php endif; ?>

<!-- HORIZONTAL STATS CAROUSEL -->
<div class="stats-scroll-container">
    <!-- 1. Lembar Disposisi -->
    <div class="stat-card primary">
        <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
        </div>
        <div>
            <div class="stat-value"><?php echo number_format($stats['surat_masuk_hari_ini']); ?></div>
            <div class="stat-label">Surat Masuk</div>
        </div>
    </div>
    
    <!-- 2. Belum Dibaca -->
    <div class="stat-card warning">
        <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </div>
        <div>
            <div class="stat-value"><?php echo number_format($stats['disposisi_belum_dibaca']); ?></div>
            <div class="stat-label">Belum Dibaca</div>
        </div>
    </div>
    
    <!-- 3. Deadline -->
    <div class="stat-card danger">
        <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div>
            <div class="stat-value"><?php echo number_format($stats['deadline_h1']); ?></div>
            <div class="stat-label">Deadline H-1</div>
        </div>
    </div>

    <!-- 4. Laporan -->
    <div class="stat-card info">
        <div class="stat-icon" style="color: var(--accent);">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        </div>
        <div>
            <div class="stat-value"><?php echo number_format($stats['laporan_menunggu']); ?></div>
            <div class="stat-label">Cek Laporan</div>
        </div>
    </div>
</div>

<!-- ACTION GRID -->
<div class="section">
    <div class="section-title">
        Akses Cepat
    </div>
    
    <div class="action-grid">
        <a href="surat-masuk.php" class="action-card">
            <div class="action-icon-circle bg-sage-light">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <h3>Disposisi</h3>
            <p>Kelola Surat</p>
        </a>

        <a href="persetujuan-laporan.php" class="action-card">
            <div class="action-icon-circle bg-blue-light">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </div>
            <h3>Laporan</h3>
            <p>Persetujuan</p>
        </a>

        <a href="monitoring.php" class="action-card">
            <div class="action-icon-circle bg-orange-light">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
            </div>
            <h3>Monitor</h3>
            <p>Pantau Status</p>
        </a>

        <a href="disposisi.php" class="action-card">
            <div class="action-icon-circle bg-purple-light">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </div>
            <h3>Buat Baru</h3>
            <p>Disposisi Manual</p>
        </a>
    </div>
</div>

<?php if (TELEGRAM_ENABLED): ?>
<div class="telegram-info" style="margin-top: 30px; opacity: 0.8; font-size: 12px;">
    <span class="telegram-info-icon">🔔</span>
    <span><?php echo TELEGRAM_INFO_TEXT; ?></span>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<?php
/**
 * Laporan Disposisi
 * Halaman untuk Camat melihat laporan yang dikirim balik dari Docku/Staff
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAuth();

$pageTitle = 'Laporan Disposisi';

$api = new ApiClient();

// Fetch laporan from API
$response = $api->get('/pimpinan/laporan-disposisi');
$laporanList = [];

if ($response['success'] && isset($response['data'])) {
    $laporanList = $response['data'];
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Laporan Disposisi</h1>
    <p class="page-subtitle">Tindak lanjut dari staff yang telah diselesaikan</p>
</div>

<?php if (empty($laporanList)): ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 48px 24px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 16px; color: var(--text-muted); opacity: 0.5;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <h3 style="color: var(--text-muted); font-size: 16px; margin-bottom: 8px;">Belum Ada Laporan</h3>
            <p style="color: var(--text-muted); font-size: 14px;">Laporan akan muncul di sini setelah staff menyelesaikan disposisi</p>
        </div>
    </div>
<?php else: ?>
    <div class="section">
        <?php foreach ($laporanList as $laporan): 
            $disposisiId = $laporan['disposisi_id'] ?? '-';
            $suratId = $laporan['surat_id'] ?? null;
            $nomorSurat = $laporan['nomor_surat'] ?? '-';
            $tujuan = $laporan['tujuan_nama'] ?? $laporan['tujuan'] ?? '-';
            $perihal = $laporan['perihal'] ?? '-';
            $status = $laporan['status_followup'] ?? 'completed';
            $completedAt = $laporan['completed_at'] ?? null;
            $laporanCatatan = $laporan['laporan_catatan'] ?? '';
            $laporanFile = $laporan['laporan_file_url'] ?? '';
        ?>
        <div class="card" style="margin-bottom: 16px;">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; padding: 20px 24px; border-bottom: 1px solid #f0f0f0;">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">Disposisi #<?php echo e($disposisiId); ?></span>
                        <span class="badge-success">Selesai</span>
                    </div>
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--text-main); margin-bottom: 4px;">
                        <?php echo e($nomorSurat); ?>
                    </h3>
                    <p style="font-size: 14px; color: var(--text-muted); margin: 0;">
                        <?php echo e($perihal); ?>
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">Diselesaikan oleh</div>
                    <div style="font-size: 14px; font-weight: 600; color: var(--primary);">
                        <?php echo e($tujuan); ?>
                    </div>
                    <?php if ($completedAt): ?>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                        <?php echo formatTanggal($completedAt, true); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Body: Laporan -->
            <div style="padding: 0 24px 20px;">
                <?php if ($laporanCatatan): ?>
                <div style="background: var(--soft-gray); padding: 16px; border-radius: 12px; margin-bottom: 16px;">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 8px;">Catatan Laporan</div>
                    <div style="font-size: 14px; color: var(--text-main); line-height: 1.6; white-space: pre-wrap;">
                        <?php echo nl2br(e($laporanCatatan)); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <div style="display: flex; gap: 12px;">
                    <?php if ($laporanFile): ?>
                    <a href="<?php echo e($laporanFile); ?>" target="_blank" class="btn btn-primary" style="flex: 1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        Lihat File Laporan
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($suratId): ?>
                    <a href="modules/surat/detail.php?surat_id=<?php echo e($suratId); ?>" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        Lihat Surat Asli
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.badge-success {
    display: inline-block;
    background: #10B981;
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<?php include 'includes/footer.php'; ?>

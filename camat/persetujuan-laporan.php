<?php
/**
 * Persetujuan Laporan
 * Halaman untuk menyetujui atau mengembalikan laporan dari Docku
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAuth();

$pageTitle = 'Persetujuan Laporan';

// Proses approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ENABLE_CSRF_PROTECTION && !verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlashMessage('error', 'Token keamanan tidak valid');
        redirect('persetujuan-laporan.php');
    }
    
    $laporanId = $_POST['laporan_id'] ?? null;
    $action = $_POST['action'] ?? null; // approve atau reject
    $catatan = sanitize($_POST['catatan_penolakan'] ?? '');
    
    if ($laporanId && $action) {
        $api = new ApiClient();
        
        if ($action === 'approve') {
            $response = $api->post('/pimpinan/laporan/' . $laporanId . '/approve');
            if ($response['success']) {
                setFlashMessage('success', 'Laporan berhasil disetujui');
            } else {
                setFlashMessage('error', $response['message'] ?? 'Gagal menyetujui laporan');
            }
        } elseif ($action === 'reject') {
            if (empty($catatan)) {
                setFlashMessage('error', 'Catatan pengembalian wajib diisi');
            } else {
                $response = $api->post('/pimpinan/laporan/' . $laporanId . '/reject', [
                    'catatan' => $catatan
                ]);
                if ($response['success']) {
                    setFlashMessage('success', 'Laporan dikembalikan untuk perbaikan');
                } else {
                    setFlashMessage('error', $response['message'] ?? 'Gagal mengembalikan laporan');
                }
            }
        }
    }
    
    redirect('persetujuan-laporan.php');
}

// Ambil daftar laporan yang menunggu persetujuan
$api = new ApiClient();
$response = $api->get('/pimpinan/laporan');

$laporanList = [];
if ($response['success'] && isset($response['data']) && is_array($response['data'])) {
    $laporanList = array_filter($response['data'], function($item) {
        return is_array($item) && isset($item['id']);
    });
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Persetujuan</h1>
    <p class="page-subtitle">Tinjau laporan kinerja pegawai</p>
</div>

<?php if (empty($laporanList)): ?>
    <div class="empty-state" style="text-align: center; padding: 40px;">
        <div style="background: rgba(255,255,255,0.5); padding: 40px; border-radius: 24px; display: inline-block; width: 100%;">
             <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted); margin-bottom: 16px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
             <p style="margin: 0; color: var(--text-muted);">Tidak ada laporan menunggu persetujuan</p>
        </div>
    </div>
<?php else: ?>
    <!-- LIST ITEMS -->
    <div style="display: grid; gap: 16px;">
    <?php foreach ($laporanList as $laporan): ?>
    
    <!-- GLASS CARD -->
    <div class="action-card" style="padding: 24px; align-items: flex-start; text-align: left;">
        <div style="display: flex; justify-content: space-between; width: 100%; margin-bottom: 8px;">
            <div style="font-size: 11px; font-weight: 700; color: var(--primary); text-transform: uppercase;">LAPORAN KEGIATAN</div>
            <?php if (isset($laporan['kategori'])): ?>
                <span style="font-size: 10px; padding: 2px 8px; border-radius: 4px; background: var(--bg-sage-light); color: var(--text-main); font-weight: 600;"><?php echo e($laporan['kategori']); ?></span>
            <?php endif; ?>
        </div>
        
        <h3 style="font-size: 16px; margin-bottom: 4px; color: var(--text-main); line-height: 1.4; font-weight: 700;"><?php echo e($laporan['judul_kegiatan'] ?? '-'); ?></h3>
        
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
             Oleh <strong style="color: var(--text-main);"><?php echo e($laporan['pelaksana'] ?? '-'); ?></strong> • <?php echo formatTanggal($laporan['tanggal'] ?? ''); ?>

        </div>
        
        <?php if (!empty($laporan['ringkasan'])): ?>
        <div style="background: rgba(0,0,0,0.03); padding: 12px; border-radius: 12px; width: 100%; margin-bottom: 16px; font-size: 14px; color: var(--text-muted); line-height: 1.6;">
            <?php echo nl2br(e($laporan['ringkasan'])); ?>
        </div>
        <?php endif; ?>
        
        <div style="display: flex; gap: 12px; width: 100%;">
            <form method="POST" style="flex: 1;" onsubmit="return validateApprovalForm('reject')">
                <?php echo csrfField(); ?>
                <input type="hidden" name="laporan_id" value="<?php echo e($laporan['id']); ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="catatan_penolakan" value="Perbaiki laporan.">
                <button type="submit" class="btn" style="width: 100%; height: 40px; border-radius: 50px; background: #FED7D7; color: #C53030; border: none; font-weight: 700; font-size: 13px; cursor: pointer;">
                    Kembalikan
                </button>
            </form>
            
            <form method="POST" style="flex: 1;" onsubmit="return validateApprovalForm('approve')">
                <?php echo csrfField(); ?>
                <input type="hidden" name="laporan_id" value="<?php echo e($laporan['id']); ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 40px; border-radius: 50px; border: none; font-weight: 700; font-size: 13px; cursor: pointer;">
                    Setujui
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<?php
/**
 * Surat Masuk
 * Daftar surat masuk untuk dibaca dan didisposisikan
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAuth();

$pageTitle = 'Lembar Disposisi';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;

// Ambil data surat masuk dari API
$api = new ApiClient();
$response = $api->get('/pimpinan/surat-masuk', [
    'page' => $page,
    'limit' => $limit
]);

$suratList = [];
$totalData = 0;
$errorMsg = null;

if ($response['success']) {
    $data = $response['data'] ?? [];
    
    // Support standard pagination wrapper or direct array
    if (isset($data['items']) && is_array($data['items'])) {
        $suratList = $data['items'];
        $totalData = $data['total'] ?? count($suratList);
    } elseif (is_array($data)) {
        // Check if data itself is the list
        if (isset($data[0]) || empty($data)) {
            $suratList = $data;
            $totalData = count($suratList);
        } else {
            // Might be a single item or other structure
            $suratList = [$data];
            $totalData = 1;
        }
    }
} else {
    $errorMsg = $response['message'] ?? 'Gagal mengambil data dari server.';
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Lembar Disposisi</h1>
    <div class="page-subtitle">Daftar lembar disposisi siap proses</div>
</div>

<?php if ($errorMsg): ?>
    <div class="alert-box alert-critical">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?php echo e($errorMsg); ?></span>
    </div>
<?php endif; ?>

<?php if (empty($suratList)): ?>
    <div class="empty-state">
        <div style="background: rgba(255,255,255,0.5); padding: 40px; border-radius: 24px; text-align: center;">
            <div style="color: var(--text-muted); margin-bottom: 20px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </div>
            <h3 style="margin-bottom: 8px;">Tidak Ada Surat</h3>
            <p style="margin: 0; font-size: 14px; opacity: 0.7;">Belum ada lembar disposisi masuk saat ini.</p>
        </div>
    </div>
<?php else: ?>
    <!-- LIST CONTAINER -->
    <div style="display: grid; gap: 16px;">
    <?php foreach ($suratList as $surat): 
        // SINGLE SOURCE OF TRUTH: Prioritize 'id_surat' (SuratQu ID) over internal 'id'
        $linkId = $surat['id_surat'] ?? $surat['id'] ?? '';
        
        $nomorSurat = !empty($surat['nomor_surat']) ? $surat['nomor_surat'] : '(Nomor Surat Kosong)';
        $asalSurat = !empty($surat['asal_surat']) ? $surat['asal_surat'] : (!empty($surat['pengirim']) ? $surat['pengirim'] : '(Asal Surat Tidak Diketahui)');
        $perihal = !empty($surat['perihal']) ? $surat['perihal'] : '(Perihal Kosong / Tidak Ada Detail)';
        $tanggalSurat = $surat['tanggal_surat'] ?? $surat['tanggal_terima'] ?? date('Y-m-d');
        $scanSurat = $surat['scan_surat'] ?? $surat['file_url'] ?? '';
        $sifat = ucfirst($surat['sifat'] ?? 'Biasa');
        
        // Extra fields
        $nomorAgenda = $surat['nomor_agenda'] ?? '-';
        $sourceApp = $surat['source_app'] ?? 'SuratQu';
        
        $badgeClass = 'bg-sage-light';
        if(strtolower($sifat) == 'penting') $badgeClass = 'bg-orange-light';
        if(strtolower($sifat) == 'rahasia') $badgeClass = 'bg-purple-light';
        if(strtolower($sifat) == 'segera') $badgeClass = 'bg-blue-light';
    ?>
    
    <!-- GLASS CARD ITEM -->
    <div class="card" style="align-items: flex-start; text-align: left; padding: 24px;">
        <div style="display: flex; justify-content: space-between; width: 100%; margin-bottom: 12px; align-items: flex-start;">
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 10px; font-weight: 800; background: #2B6CB0; color: white; padding: 4px 8px; border-radius: 6px; text-transform: uppercase;"><?php echo e($sourceApp); ?></span>
                    <span class="<?php echo $badgeClass; ?>" style="font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 6px; text-transform: uppercase;"><?php echo e($sifat); ?></span>
                </div>
                <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;">ID SuratQu: <strong style="color:var(--primary-dark)"><?php echo e($linkId); ?></strong></div>
            </div>
            
            <div style="text-align: right;">
                <span style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 2px;"><?php echo formatTanggalRelatif($tanggalSurat); ?></span>
                <span style="display: block; font-size: 11px; color: var(--text-muted);">Agenda: <strong style="color: var(--text-main);"><?php echo e($nomorAgenda); ?></strong></span>
            </div>
        </div>
        
        <h3 style="font-size: 16px; margin-bottom: 4px; color: <?php echo strpos($nomorSurat, 'Kosong') !== false ? 'var(--color-critical)' : 'var(--text-main)'; ?>; line-height: 1.4; <?php echo strpos($nomorSurat, 'Kosong') !== false ? 'font-style: italic;' : ''; ?>"><?php echo e($nomorSurat); ?></h3>
        <div style="font-size: 13px; font-weight: 600; color: <?php echo strpos($asalSurat, 'Tidak Diketahui') !== false ? 'var(--color-warning)' : 'var(--primary-dark)'; ?>; margin-bottom: 8px; <?php echo strpos($asalSurat, 'Tidak Diketahui') !== false ? 'font-style: italic;' : ''; ?>"><?php echo e($asalSurat); ?></div>
        
        <p style="font-size: 14px; color: <?php echo strpos($perihal, 'Kosong') !== false ? 'var(--color-critical)' : 'var(--text-muted)'; ?>; margin-bottom: 20px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; <?php echo strpos($perihal, 'Kosong') !== false ? 'font-style: italic; background: rgba(255,0,0,0.05); padding: 4px;' : ''; ?>">
            <?php echo e($perihal); ?>
        </p>
        
        <div style="display: flex; gap: 8px; width: 100%; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 16px; position: relative; z-index: 10;">
            <?php 
            // Already handled: $linkId is SuratQu ID
            ?>
            
            <!-- Button 'Buka' now points to the new Detail Module -->
            <a href="modules/surat/detail.php?surat_id=<?php echo urlencode($linkId); ?>" style="flex: 1; display: flex; align-items: center; justify-content: center; height: 44px; background: #F7FAFC; color: var(--text-main); border: 1px solid #EDF2F7; border-radius: 50px; font-weight: 700; font-size: 13px; text-decoration: none; cursor: pointer;">
                Buka
            </a>
            
            <?php if ($scanSurat && $scanSurat != '#'): ?>
            <a href="<?php echo e($scanSurat); ?>" target="_blank" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #EDF2F7; color: var(--text-main); border-radius: 50%; font-size: 14px; text-decoration: none; border: 1px solid #E2E8F0; cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
            </a>
            <?php endif; ?>
            
            <!-- Button 'Disposisi' also points to the new Detail Module (scrolled to form) -->
            <a href="modules/surat/detail.php?surat_id=<?php echo urlencode($linkId); ?>#form-disposisi" style="flex: 1; display: flex; align-items: center; justify-content: center; height: 44px; background: var(--primary); color: white; border-radius: 50px; font-weight: 700; font-size: 13px; text-decoration: none; box-shadow: 0 4px 12px rgba(122, 155, 142, 0.4); cursor: pointer;">
                Disposisi
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    
    <!-- Simple pagination -->
    <?php if ($totalData > $limit): ?>
    <div style="display: flex; justify-content: center; gap: 16px; margin-top: 32px;">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>" style="padding: 10px 20px; background: white; border-radius: 50px; text-decoration: none; color: var(--text-main); font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">← Sebelumnya</a>
        <?php endif; ?>
        
        <?php if ($totalData > ($page * $limit)): ?>
        <a href="?page=<?php echo $page + 1; ?>" style="padding: 10px 20px; background: white; border-radius: 50px; text-decoration: none; color: var(--text-main); font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">Selanjutnya →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

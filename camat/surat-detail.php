<?php
/**
 * Surat Detail
 * Tampilan detail surat (read-only)
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAuth();

$suratId = $_GET['id'] ?? null;

if (!$suratId) {
    setFlashMessage('error', 'ID surat tidak valid');
    redirect('surat-masuk.php');
}

// Ambil detail surat dari API
$api = new ApiClient();
$response = $api->get(ENDPOINT_SURAT_DETAIL . '/' . $suratId);


if (!$response['success'] || !isset($response['data'])) {
    setFlashMessage('error', 'Surat tidak ditemukan');
    redirect('surat-masuk.php');
}

$surat = $response['data'];
$pageTitle = 'Detail Lembar Disposisi';

// [STEP C4] Fetch Recipients for Disposisi Modal
$tujuanResponse = $api->get('/pimpinan/daftar-tujuan-disposisi');
$daftarTujuan = [];
if ($tujuanResponse['success'] && !empty($tujuanResponse['data'])) {
    // Filter logic similar to disposisi.php
    $daftarTujuan = array_filter($tujuanResponse['data'], function($u) {
        $jabatanLower = strtolower($u['jabatan'] ?? '');
        // Exclude self/unwanted roles if needed, currently accepting all downstream
        return true; 
    });
} else {
    // Fallback if API fails
    $daftarTujuan = [
        ['id' => 'sekcam', 'nama' => 'Sekretaris Kecamatan', 'jabatan' => 'Sekcam'],
        ['id' => 'kasi_pemerintahan', 'nama' => 'Kasi Pemerintahan', 'jabatan' => 'Kasi'],
        ['id' => 'kasi_pembangunan', 'nama' => 'Kasi Pembangunan', 'jabatan' => 'Kasi'],
        ['id' => 'kasi_kesra', 'nama' => 'Kasi Kesra', 'jabatan' => 'Kasi'],
        ['id' => 'kasubag_umum', 'nama' => 'Kasubag Umum & Kepegawaian', 'jabatan' => 'Kasubag'],
        ['id' => 'kasubag_perencanaan', 'nama' => 'Kasubag Perencanaan & Keuangan', 'jabatan' => 'Kasubag']
    ];
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Detail Lembar</h1>
    <div class="page-actions">
        <a href="surat-masuk.php" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDisposisi">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            Buat Disposisi
        </button>
    </div>
</div>

<?php
$idSurat = !empty($surat['id_surat']) ? $surat['id_surat'] : (!empty($surat['id']) ? $surat['id'] : '<span class="text-missing">ID Surat Tidak Tersedia</span>');
$nomorSurat = !empty($surat['nomor_surat']) ? $surat['nomor_surat'] : '<span class="text-missing">Nomor Surat Tidak Dilampirkan</span>';
$asalSurat = !empty($surat['asal_surat']) ? $surat['asal_surat'] : (!empty($surat['pengirim']) ? $surat['pengirim'] : '<span class="text-missing">Asal Surat Tidak Diketahui</span>');
$perihal = !empty($surat['perihal']) ? $surat['perihal'] : '<span class="text-missing">Perihal Kosong / Tidak Ada Detail</span>';
$tanggalSurat = !empty($surat['tanggal_surat']) ? $surat['tanggal_surat'] : (!empty($surat['tanggal_terima']) ? $surat['tanggal_terima'] : null);

$scanSurat = $surat['scan_surat'] ?? $surat['file_url'] ?? '';
?>

<style>
    .text-missing {
        color: var(--color-critical);
        font-style: italic;
        font-size: 0.9em;
        background: rgba(255, 59, 48, 0.1);
        padding: 2px 6px;
        border-radius: 4px;
    }
</style>

<div class="card">
    <div class="card-header" style="flex-direction: column; align-items: flex-start; gap: 8px;">
        <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span class="badge-suratqu">SuratQu</span>
                <h2 class="card-title">Lembar #<?php echo $idSurat; ?></h2>
            </div>
            <?php echo renderPriorityBadge($surat['sifat'] ?? 'biasa'); ?>
        </div>
        <span style="font-size: 11px; color: var(--medium-gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">DATA DARI SURATQU (READ-ONLY / TIDAK DAPAT DIEDIT)</span>
    </div>
    
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Nomor Surat</label>
                <div><?php echo $nomorSurat; ?></div>
            </div>
            <div class="detail-item">
                <label>Asal Surat</label>
                <div><?php echo $asalSurat; ?></div>
            </div>
            <div class="detail-item">
                <label>Tanggal Surat</label>
                <div>
                    <?php 
                    if ($tanggalSurat) {
                        echo formatTanggal($tanggalSurat); 
                    } else {
                        echo '<span class="text-missing">Tanggal Tidak Tersedia</span>';
                    }
                    ?>
                </div>
            </div>
            <div class="detail-item full-width">
                <label>Perihal</label>
                <div><?php echo $perihal; ?></div>
            </div>
            <?php if (!empty($surat['isi_ringkas'])): ?>
            <div class="detail-item full-width">
                <label>Ringkasan</label>
                <div><?php echo nl2br(e($surat['isi_ringkas'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($scanSurat && filter_var($scanSurat, FILTER_VALIDATE_URL)): ?>
    <div class="card-footer" style="padding: 0;">
        <a href="<?php echo e($scanSurat); ?>" target="_blank" class="btn btn-info btn-full" style="border-radius: 0 0 16px 16px; padding: 16px; display: flex; align-items: center; justify-content: center; width: 100%;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 12px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            LIHAT SCAN SURAT ASLI
        </a>
    </div>
    <?php elseif ($scanSurat): ?>
        <div class="card-footer" style="padding: 16px; text-align: center; color: var(--color-critical); font-size: 13px; background: rgba(255,0,0,0.05);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: text-bottom;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <strong>INFO:</strong> URL Scan Surat tidak valid atau file rusak. Hubungi operator.
        </div>
    <?php else: ?>
        <div class="card-footer" style="padding: 16px; text-align: center; color: var(--color-warning); font-size: 13px; background: rgba(255,165,0,0.05);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: text-bottom;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
            <strong>INFO:</strong> Scan surat asli belum dilampirkan oleh operator SuratQu.
        </div>
    <?php endif; ?>
</div>

<!-- Riwayat Disposisi (jika ada) -->
<?php if (isset($surat['disposisi']) && !empty($surat['disposisi'])): ?>
<div class="section">
    <div class="section-header">
        <h2 class="section-title">Riwayat Disposisi</h2>
    </div>
    
    <?php foreach ($surat['disposisi'] as $disposisi): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--space-md);">
            <div>
                <strong><?php echo e($disposisi['dari'] ?? '-'); ?></strong> → 
                <strong><?php echo e($disposisi['kepada'] ?? '-'); ?></strong>
            </div>
            <div>
                <?php echo formatTanggal($disposisi['tanggal'] ?? '', true); ?>
            </div>
        </div>
        <div style="background: var(--soft-gray); padding: var(--space-md); border-radius: var(--radius-md); font-size: var(--font-base);">
            <?php echo nl2br(e($disposisi['catatan'] ?? '-')); ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'includes/modal_disposisi.php'; ?>

<?php include 'includes/footer.php'; ?>

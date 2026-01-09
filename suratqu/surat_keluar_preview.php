<?php
// surat_keluar_preview.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: surat_keluar.php"); exit; }

// Fetch Surat
$stmt = $db->prepare("SELECT * FROM surat_keluar WHERE id_sk = ?");
$stmt->execute([$id]);
$surat = $stmt->fetch();

// Fetch Kop Surat Aktif
$stmt = $db->query("SELECT * FROM kop_surat WHERE is_active = 1 LIMIT 1");
$kop = $stmt->fetch();

// Fallback if no active kop found
if (!$kop) {
    $kop = [
        'nama_instansi' => 'PEMERINTAH KABUPATEN PROBOLINGGO',
        'nama_instansi_l1' => 'KECAMATAN SUMBERASIH',
        'alamat' => 'Jl. Raya Probolinggo No. 123, Sumberasih, 67251',
        'kontak' => 'Email: kec.sumberasih@probolinggokab.go.id'
    ];
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-2">
    <div>
        <h2 class="fw-bold mb-1">Pratinjau Surat</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="surat_keluar.php">Surat Keluar</a></li>
                <li class="breadcrumb-item active">Pratinjau</li>
            </ol>
        </nav>
    </div>
    <div class="btn-group">
        <?php if ($surat['status'] == 'draft'): ?>
        <a href="surat_keluar_proses.php?action=request_verif&id=<?= $surat['id_sk'] ?>" class="btn btn-warning" onclick="return confirm('Ajukan verifikasi surat ini?')">
            <i class="fa-solid fa-paper-plane me-2"></i> Ajukan Verifikasi
        </a>
        <a href="surat_keluar_edit.php?id=<?= $surat['id_sk'] ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-edit me-2"></i> Edit Draf
        </a>
        <?php endif; ?>

        <?php if ($surat['status'] == 'verifikasi' && ($_SESSION['can_verifikasi'] == 1 || $_SESSION['role'] == 'admin')): ?>
        <form action="surat_keluar_proses.php" method="POST" class="d-flex btn-group p-0 shadow-none border-0">
            <input type="hidden" name="id_sk" value="<?= $surat['id_sk'] ?>">
            <button type="submit" name="approve" class="btn btn-success" onclick="return confirm('Setujui surat ini?')">
                <i class="fa-solid fa-check-double me-2"></i> Setujui Surat
            </button>
        </form>
        <?php endif; ?>

        <?php if ($surat['status'] == 'disetujui' || $surat['status'] == 'terkirim'): ?>
        <button onclick="window.print()" class="btn btn-success">
            <i class="fa-solid fa-print me-2"></i> Cetak Sekarang
        </button>
        <?php endif; ?>

        <a href="surat_keluar.php" class="btn btn-light">Kembali</a>
    </div>
</div>

<div class="card card-custom p-0 border-0 shadow-sm overflow-hidden mb-5 d-flex justify-content-center bg-transparent shadow-none">
    <!-- KERTAS SURAT SIMULATION -->
    <div class="bg-white p-5 mx-auto shadow printable-area" style="width: 21cm; min-height: 29.7cm; font-family: 'Times New Roman', serif; position: relative; color: #000;">
        
        <!-- ... (Kop Surat logic remains same but I'll optimize the container) ... -->
        
        <!-- Pengecekan status: Jika draft/verifikasi, tambahkan Watermark PREVIEW -->
        <?php if ($surat['status'] == 'draft' || $surat['status'] == 'verifikasi'): ?>
        <div style="position: absolute; top: 40%; left: 15%; transform: rotate(-45deg); font-size: 100px; color: rgba(220, 53, 69, 0.1); font-weight: bold; pointer-events: none; z-index: 10;">DUMP / PRATINJAU</div>
        <?php endif; ?>

        <!-- Kop Surat Dinamis -->
        <div class="kop-surat d-flex align-items-center justify-content-between mb-0" style="padding: 10px 0;">
            <div class="logo-kiri" style="width: 80px;">
                <img src="<?= ($kop['logo_path']) ?: 'assets/img/logo_kab.png' ?>" style="max-width: 80px; height: auto;">
            </div>
            
            <div class="kop-teks text-center flex-grow-1 px-3">
                <h4 class="mb-0 fw-bold text-uppercase" style="font-size: 16px; letter-spacing: 0.5px;"><?= htmlspecialchars($kop['nama_instansi'] ?? 'PEMERINTAH KABUPATEN PROBOLINGGO') ?></h4>
                <h4 class="mb-0 fw-bold text-uppercase" style="font-size: 16px; letter-spacing: 0.5px;"><?= htmlspecialchars($kop['nama_instansi_l1'] ?? 'DINAS KESEHATAN') ?></h4>
                <h3 class="mb-1 fw-bold text-uppercase" style="font-size: 19px; letter-spacing: 1px;"><?= htmlspecialchars($kop['nama_instansi_l2'] ?? 'PUSKESMAS BANTARAN') ?></h3>
                <p class="mb-0" style="font-size: 11px; line-height: 1.3;">
                    <?= nl2br(htmlspecialchars($kop['alamat'])) ?> <br>
                    <?= htmlspecialchars($kop['kontak']) ?>
                </p>
            </div>

            <div class="logo-kanan" style="width: 80px; text-align: right;">
                <?php if (!empty($kop['logo_kanan_path'])): ?>
                    <img src="<?= $kop['logo_kanan_path'] ?>" style="max-width: 80px; height: auto;">
                <?php else: ?>
                    <div style="width: 80px;"></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="border-bottom border-dark border-3 mt-1"></div>
        <div class="border-bottom border-dark border-1 mt-1 mb-4"></div>

        <!-- TANGGAL & NOMOR -->
        <div class="row mb-4">
            <div class="col-8">
                <table style="font-size: 14px;">
                    <tr><td width="100">Nomor</td><td>: <?= $surat['no_surat'] ?: '... / ... / ... / 2026' ?></td></tr>
                    <tr><td>Sifat</td><td>: Penting</td></tr>
                    <tr><td>Lampiran</td><td>: -</td></tr>
                    <tr><td>Perihal</td><td>: <strong><?= htmlspecialchars($surat['perihal']) ?></strong></td></tr>
                </table>
            </div>
            <div class="col-4 text-end" style="font-size: 14px;">
                Diterbitkan pada: <?= format_tgl_indo($surat['tgl_surat']) ?>
            </div>
        </div>
        
        <!-- PENERIMA -->
        <div class="mb-4" style="font-size: 14px;">
            Kepada Yth.<br>
            <strong><?= htmlspecialchars($surat['tujuan']) ?></strong><br>
            di -<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tempat
        </div>
        
        <!-- ISI SURAT -->
        <div class="text-justify mb-5" style="line-height: 1.6; text-indent: 50px; font-size: 14px;">
            <?= $surat['isi_surat'] ? nl2br(htmlspecialchars($surat['isi_surat'])) : '<em class="text-muted small">[Isi surat belum diketik]</em>' ?>
        </div>
        
        <!-- TANDA TANGAN -->
        <div class="row" style="font-size: 14px;">
            <div class="col-7"></div>
            <div class="col-5 text-center">
                <?php if ($surat['status'] == 'disetujui' || $surat['status'] == 'terkirim'): ?>
                    <?php 
                        // QR Data: Verifikasi Keaslian Surat
                        $qr_data = "SuratQu-Verified|No:".$surat['no_surat']."|Hal:".$surat['perihal']."|Tgl:".$surat['tgl_surat'];
                        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);
                    ?>
                    <p class="mb-1">Dokumen ini ditandatangani secara elektronik</p>
                    <img src="<?= $qr_url ?>" style="width: 100px; height: 100px;" class="mb-2">
                    <br>
                    <strong>Camat Sumberasih</strong>
                <?php else: ?>
                    <div style="height: 150px; border: 1px dashed #ccc;" class="d-flex align-items-center justify-content-center text-muted">
                        MENUNGGU VERIFIKASI PIMPINAN
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .card-custom, .card-custom * { visibility: visible; }
    .card-custom { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none !important; }
    .navbar, .sidebar, .btn-group, .breadcrumb, h2, .bottom-nav { display: none !important; }
}
</style>

<?php include 'includes/footer.php'; ?>

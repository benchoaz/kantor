<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// Fetch filters
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-t');
$tipe = $_GET['tipe'] ?? 'masuk';

$title = 'Laporan & Buku Agenda';
include 'includes/header.php';

// Build Query based on type
if ($tipe == 'masuk') {
    $stmt = $db->prepare("SELECT * FROM surat_masuk WHERE tgl_diterima BETWEEN ? AND ? ORDER BY tgl_diterima ASC");
    $stmt->execute([$tgl_mulai, $tgl_selesai]);
    $data = $stmt->fetchAll();
    $table_headers = ['No. Agenda', 'Asal Surat', 'No. Surat', 'Tgl. Surat', 'Tgl. Terima', 'Perihal'];
} else {
    $stmt = $db->prepare("SELECT sk.*, u.nama_lengkap as pembuat FROM surat_keluar sk 
                          LEFT JOIN users u ON sk.id_user_pembuat = u.id_user 
                          WHERE tgl_surat BETWEEN ? AND ? ORDER BY tgl_surat ASC");
    $stmt->execute([$tgl_mulai, $tgl_selesai]);
    $data = $stmt->fetchAll();
    $table_headers = ['No. Surat', 'Tujuan', 'Tgl. Surat', 'Klasifikasi', 'Pembuat', 'Perihal'];
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold mb-1">Pusat Laporan & Buku Agenda 📊</h4>
            <p class="text-muted small">Rekapitulasi persuratan dalam format Buku Agenda elektronik.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button onclick="window.print()" class="btn btn-outline-dark">
                <i class="fa-solid fa-print me-2"></i> Cetak Dokumen
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card card-custom border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Jenis Laporan</label>
                    <select name="tipe" class="form-select shadow-none">
                        <option value="masuk" <?= $tipe == 'masuk' ? 'selected' : '' ?>>Surat Masuk (Agenda Masuk)</option>
                        <option value="keluar" <?= $tipe == 'keluar' ? 'selected' : '' ?>>Surat Keluar (Agenda Keluar)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" name="tgl_mulai" class="form-control shadow-none" value="<?= $tgl_mulai ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" name="tgl_selesai" class="form-control shadow-none" value="<?= $tgl_selesai ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter me-2"></i> Tampilkan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card card-custom border-0 shadow-sm overflow-hidden printable-area">
        <div class="bg-primary text-white p-3 text-center d-none d-print-block">
            <h5 class="mb-1 fw-bold">BUKU AGENDA SURAT <?= strtoupper($tipe) ?></h5>
            <small>Periode: <?= format_tgl_indo($tgl_mulai) ?> s/d <?= format_tgl_indo($tgl_selesai) ?></small>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" id="reportTable">
                <thead class="bg-light text-center" style="font-size: 0.85rem;">
                    <tr>
                        <th width="50">No</th>
                        <?php foreach ($table_headers as $h): ?>
                            <th><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data) > 0): ?>
                        <?php $no = 1; foreach ($data as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <?php if ($tipe == 'masuk'): ?>
                                    <td class="fw-bold"><?= htmlspecialchars($row['no_agenda']) ?></td>
                                    <td><?= htmlspecialchars($row['asal_surat']) ?></td>
                                    <td><?= htmlspecialchars($row['no_surat']) ?></td>
                                    <td><?= format_tgl_indo($row['tgl_surat']) ?></td>
                                    <td><?= format_tgl_indo($row['tgl_diterima']) ?></td>
                                    <td><?= htmlspecialchars($row['perihal']) ?></td>
                                <?php else: ?>
                                    <td class="fw-bold"><?= htmlspecialchars($row['no_surat'] ?: '- Draf -') ?></td>
                                    <td><?= htmlspecialchars($row['tujuan']) ?></td>
                                    <td><?= format_tgl_indo($row['tgl_surat']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($row['klasifikasi']) ?></td>
                                    <td><?= htmlspecialchars($row['pembuat']) ?></td>
                                    <td><?= htmlspecialchars($row['perihal']) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= count($table_headers) + 1 ?>" class="text-center py-5 text-muted">
                                Tidak ada data dalam periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .card-body form, .breadcrumb, h2, h4, .sidebar, .navbar, .bottom-nav { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .printable-area { display: block !important; margin: 0; padding: 0; width: 100%; }
    table { width: 100%; border-collapse: collapse; font-size: 10px !important; }
    th, td { padding: 8px !important; border: 1px solid #000 !important; }
    body { padding: 1cm; background: #fff !important; }
}
</style>

<?php include 'includes/footer.php'; ?>

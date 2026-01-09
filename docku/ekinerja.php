<?php
// ekinerja.php - List all e-Kinerja reports
require_once 'config/database.php';
require_once 'includes/auth.php';
require_login();

$page_title = 'Laporan e-Kinerja';
$active_page = 'ekinerja';

// Filters
$bidang_id = $_GET['bidang_id'] ?? '';
$status = $_GET['status'] ?? '';
$bulan = $_GET['bulan'] ?? '';

$params = [];
$sql = "SELECT le.*, k.judul, k.tanggal, b.nama_bidang, ok.nama_output
        FROM laporan_ekinerja le
        JOIN kegiatan k ON le.kegiatan_id = k.id
        JOIN bidang b ON k.bidang_id = b.id
        JOIN output_kinerja ok ON le.output_kinerja_id = ok.id";

$where = [];

if ($bidang_id) {
    $where[] = "k.bidang_id = ?";
    $params[] = $bidang_id;
}
if ($status) {
    $where[] = "le.status = ?";
    $params[] = $status;
}
if ($bulan) {
    $where[] = "MONTH(k.tanggal) = ?";
    $params[] = $bulan;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY k.tanggal DESC, le.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$bidang_list = $pdo->query("SELECT * FROM bidang")->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-up">
    <div>
        <h3 class="title-main mb-0">Laporan e-Kinerja</h3>
        <p class="text-muted small mb-0">Format pelaporan standar BKN untuk dokumentasi kegiatan</p>
    </div>
    <?php if (has_role(['admin', 'operator'])): ?>
        <a href="ekinerja_tambah.php" class="btn btn-modern btn-primary-modern shadow-sm">
            <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline ms-1">Buat Laporan</span>
        </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card-modern border-0 mb-4 animate-up" style="animation-delay: 0.1s;">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="text-label mb-2">Filter Bidang</label>
                <select name="bidang_id" class="form-select border-2 shadow-none">
                    <option value="">Semua Bidang</option>
                    <?php foreach ($bidang_list as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($bidang_id == $b['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['nama_bidang']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="text-label mb-2">Status Laporan</label>
                <select name="status" class="form-select border-2 shadow-none">
                    <option value="">Semua Status</option>
                    <option value="draft" <?= ($status == 'draft') ? 'selected' : '' ?>>Draft (Belum Selesai)</option>
                    <option value="siap" <?= ($status == 'siap') ? 'selected' : '' ?>>Siap e-Kinerja (Fixed)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="text-label mb-2">Bulan Kegiatan</label>
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
                    <i class="bi bi-filter me-2"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Report Table / Cards -->
<div class="card-modern border-0 animate-up" style="animation-delay: 0.2s;">
    <div class="card-body p-0 p-lg-4">
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Tanggal & Kegiatan</th>
                        <th>Output Kinerja</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td class="ps-4" data-label="Kegiatan">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded p-2 me-3 d-none d-lg-block">
                                        <i class="bi bi-file-earmark-check text-muted"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($r['judul']) ?></div>
                                        <div class="small text-muted"><?= date('d F Y', strtotime($r['tanggal'])) ?> &bull; <?= htmlspecialchars($r['nama_bidang']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Output">
                                <div class="small fw-medium text-dark"><?= htmlspecialchars($r['nama_output']) ?></div>
                            </td>
                            <td data-label="Status">
                                <?php if ($r['status'] === 'siap'): ?>
                                    <span class="badge rounded-pill bg-success bg-opacity-10 text-success border-success border-opacity-25 px-3">Siap BKN</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-secondary bg-opacity-10 text-muted border px-3">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4" data-label="Aksi">
                                <div class="btn-group">
                                    <a href="ekinerja_detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-3">
                                        Detail
                                    </a>
                                    <?php if (has_role(['admin', 'operator'])): ?>
                                        <button class="btn btn-sm btn-light border rounded-pill px-2 ms-1 dropdown-toggle no-caret" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            <li><a class="dropdown-item" href="ekinerja_edit.php?id=<?= $r['id'] ?>"><i class="bi bi-pencil me-2"></i> Edit</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="ekinerja_hapus.php?id=<?= $r['id'] ?>" onclick="return confirm('Hapus laporan e-Kinerja ini?')"><i class="bi bi-trash me-2"></i> Hapus</a></li>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <img src="assets/img/empty.svg" alt="Empty" style="width: 120px; opacity: 0.5;" class="mb-3 d-block mx-auto">
                                <p class="text-muted">Belum ada laporan e-Kinerja yang tersimpan.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

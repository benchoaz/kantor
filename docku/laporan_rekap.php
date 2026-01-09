<?php
// laporan_rekap.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role(['admin', 'pimpinan']); // Admin & Pimpinan can see recaps

$page_title = 'Rekap Laporan Kinerja';
$active_page = 'laporan_rekap';

// Fetch users for filter
if (has_role(['admin'])) {
    $users = $pdo->query("SELECT id, nama, jabatan FROM users ORDER BY nama")->fetchAll();
} else {
    // Show only self or same bidang? Usually Pimpinan needs to see staff.
    // Assuming Pimpinan can see all staff in their Bidang.
    $bidang_id = $_SESSION['bidang_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT id, nama, jabatan FROM users WHERE bidang_id = ? OR id = ? ORDER BY nama");
    $stmt->execute([$bidang_id, $_SESSION['user_id']]);
    $users = $stmt->fetchAll();
}

include 'includes/header.php';
?>

<div class="row justify-content-center animate-up">
    <div class="col-lg-6">
        <div class="card-modern border-0 p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3 text-primary">
                    <i class="bi bi-printer-fill fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1">Cetak Rekap Laporan</h4>
                    <p class="text-muted small mb-0">Download laporan kinerja harian, bulanan, atau tahunan.</p>
                </div>
            </div>

            <form action="laporan_rekap_pdf.php" method="GET" target="_blank">
                <div class="mb-4">
                    <label class="text-label mb-2">Jenis Rekap</label>
                    <div class="row g-2">
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="jenis" id="harian" value="harian" checked onclick="toggleFilters('harian')">
                            <label class="btn btn-outline-light text-dark border w-100 py-3 fw-bold" for="harian">Harian</label>
                        </div>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="jenis" id="bulanan" value="bulanan" onclick="toggleFilters('bulanan')">
                            <label class="btn btn-outline-light text-dark border w-100 py-3 fw-bold" for="bulanan">Bulanan</label>
                        </div>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="jenis" id="tahunan" value="tahunan" onclick="toggleFilters('tahunan')">
                            <label class="btn btn-outline-light text-dark border w-100 py-3 fw-bold" for="tahunan">Tahunan</label>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <!-- Date Filter -->
                    <div class="col-md-12" id="box-tanggal">
                        <label class="text-label mb-2">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control border-2 shadow-none" value="<?= date('Y-m-d') ?>">
                    </div>

                    <!-- Month Filter -->
                    <div class="col-md-8 d-none" id="box-bulan">
                        <label class="text-label mb-2">Bulan</label>
                        <select name="bulan" class="form-select border-2 shadow-none">
                            <?php 
                            $bulanIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            foreach($bulanIndo as $i => $b): ?>
                                <option value="<?= $i+1 ?>" <?= (date('n') == $i+1) ? 'selected' : '' ?>><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Year Filter -->
                    <div class="col-md-4 d-none" id="box-tahun">
                        <label class="text-label mb-2">Tahun</label>
                        <select name="tahun" class="form-select border-2 shadow-none">
                            <?php for($y=date('Y'); $y>=2024; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-label mb-2">Personil (Penanda Tangan Laporan)</label>
                    <select name="user_id" class="form-select border-2 shadow-none" required>
                        <option value="">-- Pilih Nama Pemilik Laporan --</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($u['id'] == $_SESSION['user_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nama']) ?> 
                                <?= $u['jabatan'] ? '('.$u['jabatan'].')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="extra-small text-muted mt-2">Laporan akan berisi kegiatan yang dibuat oleh personil ini.</div>
                </div>

                <button type="submit" class="btn btn-primary-modern w-100 py-3 shadow-sm fw-bold">
                    <i class="bi bi-file-earmark-pdf-fill me-2"></i>Download Laporan PDF
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleFilters(jenis) {
    const boxTanggal = document.getElementById('box-tanggal');
    const boxBulan = document.getElementById('box-bulan');
    const boxTahun = document.getElementById('box-tahun');

    if (jenis === 'harian') {
        boxTanggal.classList.remove('d-none');
        boxBulan.classList.add('d-none');
        boxTahun.classList.add('d-none');
    } else if (jenis === 'bulanan') {
        boxTanggal.classList.add('d-none');
        boxBulan.classList.remove('d-none');
        boxTahun.classList.remove('d-none');
        // Ensure year takes full width if needed or adjust classes
        boxTahun.className = 'col-md-4'; 
    } else if (jenis === 'tahunan') {
        boxTanggal.classList.add('d-none');
        boxBulan.classList.add('d-none');
        boxTahun.classList.remove('d-none');
        boxTahun.className = 'col-md-12';
    }
}
</script>

<?php include 'includes/footer.php'; ?>

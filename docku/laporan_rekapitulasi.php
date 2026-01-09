<?php
// laporan_rekapitulasi.php - Admin only: Monthly & Annual Reports
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role(['admin', 'pimpinan', 'staff', 'operator']);

$page_title = 'Laporan Rekapitulasi';
$active_page = 'rekapitulasi';

// Get available years from kegiatan
$years = $pdo->query("SELECT DISTINCT YEAR(tanggal) as tahun FROM kegiatan ORDER BY tahun DESC")->fetchAll();
$bidang_list = $pdo->query("SELECT * FROM bidang ORDER BY nama_bidang")->fetchAll();

// Get users list based on role
$users_sql = "SELECT id, nama FROM users";
$users_params = [];
if (!has_role(['admin', 'pimpinan', 'operator'])) {
    $users_sql .= " WHERE id = ?";
    $users_params = [$_SESSION['user_id']];
}
$users_list = $pdo->prepare($users_sql);
$users_list->execute($users_params);
$users_list = $users_list->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-up">
    <div>
        <h3 class="title-main mb-0">Laporan Rekapitulasi</h3>
        <p class="text-muted small mb-0">Ekspor data kegiatan kolektif untuk pelaporan SKK, SINCAN, dan Laporan Camat</p>
    </div>
</div>

<div class="row g-4 animate-up">
    <!-- Laporan Bulanan -->
    <div class="col-lg-6">
        <div class="card-modern border-0 h-100 p-4 report-card">
            <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-light">
                <div class="icon-box-large bg-primary bg-opacity-10 text-primary me-3">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div>
                    <h5 class="fw-bold title-main mb-0">Rekap Bulanan</h5>
                    <p class="text-muted small mb-0">Laporan kegiatan periodik bulanan</p>
                </div>
            </div>
            
            <form action="laporan_bulanan_pdf.php" method="GET" target="_blank">
                <div class="report-section mb-4 p-3 rounded-4 bg-light bg-opacity-50">
                    <h6 class="small fw-bold text-uppercase text-muted mb-3 d-flex align-items-center">
                        <i class="bi bi-funnel me-2"></i> Parameter Laporan
                    </h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="text-label small mb-1">Bulan</label>
                            <select name="bulan" class="form-select border-0 shadow-sm rounded-pill" required>
                                <?php 
                                $months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", 
                                          "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                                foreach ($months as $idx => $m): ?>
                                    <option value="<?= $idx + 1 ?>" <?= (date('n') == ($idx + 1)) ? 'selected' : '' ?>>
                                        <?= $m ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="text-label small mb-1">Tahun</label>
                            <select name="tahun" class="form-select border-0 shadow-sm rounded-pill" required>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?= $y['tahun'] ?>" <?= (date('Y') == $y['tahun']) ? 'selected' : '' ?>>
                                        <?= $y['tahun'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mt-3">
                            <label class="text-label small mb-1">Bidang (Opsi)</label>
                            <select name="bidang_id" class="form-select border-0 shadow-sm rounded-pill">
                                <option value="">Semua Bidang</option>
                                <?php foreach ($bidang_list as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama_bidang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mt-3">
                            <label class="text-label small mb-1">Personil (Opsi)</label>
                            <select name="p_user_id" class="form-select border-0 shadow-sm rounded-pill">
                                <?php if (has_role(['admin', 'pimpinan', 'operator'])): ?>
                                    <option value="">Seluruh Personil</option>
                                <?php endif; ?>
                                <?php foreach ($users_list as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= (!has_role(['admin', 'pimpinan', 'operator']) && $_SESSION['user_id'] == $u['id'] ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($u['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="report-section mb-4 p-3 rounded-4 bg-light bg-opacity-50">
                    <h6 class="small fw-bold text-uppercase text-muted mb-3 d-flex align-items-center">
                        <i class="bi bi-gear-fill me-2"></i> Pengaturan Kertas
                    </h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="btn-group w-100 shadow-sm rounded-pill overflow-hidden">
                                <input type="radio" class="btn-check" name="orient" id="m_orientP" value="P" checked>
                                <label class="btn btn-outline-primary btn-sm border-0 py-2" for="m_orientP">Portrait</label>
                                <input type="radio" class="btn-check" name="orient" id="m_orientL" value="L">
                                <label class="btn btn-outline-primary btn-sm border-0 py-2" for="m_orientL">Landscape</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="btn-group w-100 shadow-sm rounded-pill overflow-hidden">
                                <input type="radio" class="btn-check" name="size" id="m_sizeF4" value="F4" checked>
                                <label class="btn btn-outline-info btn-sm border-0 py-2" for="m_sizeF4">F4</label>
                                <input type="radio" class="btn-check" name="size" id="m_sizeA4" value="A4">
                                <label class="btn btn-outline-info btn-sm border-0 py-2" for="m_sizeA4">A4</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary-modern w-100 py-3 rounded-pill shadow-lg hover-up">
                    <i class="bi bi-file-earmark-pdf-fill me-2"></i> Generate PDF Bulanan
                </button>
            </form>
        </div>
    </div>
    
    <!-- Laporan Tahunan -->
    <div class="col-lg-6">
        <div class="card-modern border-0 h-100 p-4 report-card color-annual">
            <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-light">
                <div class="icon-box-large bg-success bg-opacity-10 text-success me-3">
                    <i class="bi bi-calendar-range-fill"></i>
                </div>
                <div>
                    <h5 class="fw-bold title-main mb-0">Rekap Tahunan</h5>
                    <p class="text-muted small mb-0">Laporan capaian camat tahunan</p>
                </div>
            </div>
            
            <form action="laporan_tahunan_pdf.php" method="GET" target="_blank">
                <div class="report-section mb-4 p-3 rounded-4 bg-light bg-opacity-50">
                    <h6 class="small fw-bold text-uppercase text-muted mb-3 d-flex align-items-center">
                        <i class="bi bi-funnel me-2"></i> Parameter Laporan
                    </h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="text-label small mb-1">Pilih Tahun</label>
                            <select name="tahun" class="form-select border-0 shadow-sm rounded-pill" required>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?= $y['tahun'] ?>" <?= (date('Y') == $y['tahun']) ? 'selected' : '' ?>>
                                        <?= $y['tahun'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mt-3">
                            <label class="text-label small mb-1">Bidang (Opsi)</label>
                            <select name="bidang_id" class="form-select border-0 shadow-sm rounded-pill">
                                <option value="">Semua Bidang</option>
                                <?php foreach ($bidang_list as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama_bidang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mt-3">
                            <label class="text-label small mb-1">Personil (Opsi)</label>
                            <select name="p_user_id" class="form-select border-0 shadow-sm rounded-pill">
                                <?php if (has_role(['admin', 'pimpinan', 'operator'])): ?>
                                    <option value="">Seluruh Personil</option>
                                <?php endif; ?>
                                <?php foreach ($users_list as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= (!has_role(['admin', 'pimpinan', 'operator']) && $_SESSION['user_id'] == $u['id'] ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($u['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="report-section mb-4 p-3 rounded-4 bg-light bg-opacity-50">
                    <h6 class="small fw-bold text-uppercase text-muted mb-3 d-flex align-items-center">
                        <i class="bi bi-gear-fill me-2"></i> Pengaturan Kertas
                    </h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="btn-group w-100 shadow-sm rounded-pill overflow-hidden">
                                <input type="radio" class="btn-check" name="orient" id="y_orientP" value="P" checked>
                                <label class="btn btn-outline-primary btn-sm border-0 py-2" for="y_orientP">Portrait</label>
                                <input type="radio" class="btn-check" name="orient" id="y_orientL" value="L">
                                <label class="btn btn-outline-primary btn-sm border-0 py-2" for="y_orientL">Landscape</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="btn-group w-100 shadow-sm rounded-pill overflow-hidden">
                                <input type="radio" class="btn-check" name="size" id="y_sizeF4" value="F4" checked>
                                <label class="btn btn-outline-info btn-sm border-0 py-2" for="y_sizeF4">F4</label>
                                <input type="radio" class="btn-check" name="size" id="y_sizeA4" value="A4">
                                <label class="btn btn-outline-info btn-sm border-0 py-2" for="y_sizeA4">A4</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success w-100 py-3 rounded-pill shadow-lg text-white hover-up">
                    <i class="bi bi-file-earmark-pdf-fill me-2"></i> Generate PDF Tahunan
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    .report-card { 
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); 
        box-shadow: 0 10px 30px rgba(0,0,0,0.05) !important;
    }
    .report-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 15px 40px rgba(0,0,0,0.1) !important; 
    }
    .icon-box-large {
        width: 64px; height: 64px; border-radius: 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem;
    }
    .hover-up:hover { transform: translateY(-2px); transition: transform 0.2s; }
    .form-select:focus { ring: none; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    
    @media (max-width: 576px) {
        .report-card { padding: 1.5rem !important; }
        .icon-box-large { width: 48px; height: 48px; border-radius: 14px; font-size: 1.5rem; }
    }
</style>

<!-- Info Section -->
<div class="row mt-4 animate-up" style="animation-delay: 0.2s;">
    <div class="col-12">
        <div class="p-4 rounded-4 bg-light border-0">
            <h6 class="fw-bold title-main mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Mekanisme Pelaporan Otomatis</h6>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="small text-muted">Aplikasi mengompilasi seluruh dokumentasi kegiatan yang diinput secara real-time menjadi format laporan resmi.</div>
                </div>
                <div class="col-md-4 border-start d-none d-md-block">
                    <div class="small text-muted">PDF yang dihasilkan sudah menyertakan Kop Surat resmi dan Tanda Tangan Pejabat sesuai pengaturan profil Admin.</div>
                </div>
                <div class="col-md-4 border-start d-none d-md-block">
                    <div class="small text-muted">Gunakan hasil rekap ini untuk mengisi aplikasi SINCAN (Sistem Informasi Perencanaan & Kinerja) Kabupaten Probolinggo.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

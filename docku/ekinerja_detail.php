<?php
// ekinerja_detail.php - View e-Kinerja report with copy-to-clipboard
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_login();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT le.*, k.judul, k.tanggal, k.lokasi, k.id as kegiatan_id,
                              b.nama_bidang, ok.nama_output
                       FROM laporan_ekinerja le
                       JOIN kegiatan k ON le.kegiatan_id = k.id
                       JOIN bidang b ON k.bidang_id = b.id
                       JOIN output_kinerja ok ON le.output_kinerja_id = ok.id
                       WHERE le.id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    header("Location: ekinerja.php");
    exit;
}

// Fetch photos from kegiatan
$stmt_foto = $pdo->prepare("SELECT * FROM foto_kegiatan WHERE kegiatan_id = ?");
$stmt_foto->execute([$report['kegiatan_id']]);
$fotos = $stmt_foto->fetchAll();

$page_title = 'Detail Laporan e-Kinerja';
$active_page = 'ekinerja';
include 'includes/header.php';
?>

<div class="d-flex align-items-center mb-4 animate-up">
    <a href="ekinerja.php" class="btn btn-light border-0 rounded-circle p-2 me-3 shadow-sm">
        <i class="bi bi-arrow-left fs-5"></i>
    </a>
    <div>
        <h3 class="title-main mb-0">Detail Laporan Kinerja</h3>
        <p class="text-muted small mb-0">Verifikasi capaian dan integrasi sistem BKN</p>
    </div>
    <?php if (has_role(['admin', 'operator'])): ?>
    <div class="ms-auto">
        <a href="ekinerja_edit.php?id=<?= $report['id'] ?>" class="btn btn-modern btn-light border py-2 px-3 shadow-sm">
            <i class="bi bi-pencil-square me-2"></i>Edit Laporan
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="position-fixed bottom-0 end-0 p-4" style="z-index: 1050">
        <div class="toast show animate-up border-0 shadow-lg rounded-4 overflow-hidden" role="alert">
            <div class="d-flex p-3 bg-success text-white">
                <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                <div class="fw-bold">
                    <?php 
                    if($_GET['msg'] == 'success') echo "Laporan berhasil disimpan!";
                    else if($_GET['msg'] == 'updated') echo "Perubahan berhasil diperbarui!";
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <div class="col-lg-8 animate-up">
        <div class="card-modern border-0 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                    <i class="bi bi-journal-check fs-3"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($report['judul']) ?></h4>
                    <div class="d-flex gap-2">
                        <?php if ($report['status'] === 'siap'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 px-2 rounded-pill extra-small">
                                <i class="bi bi-check-circle me-1"></i>Siap e-Kinerja
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-10 px-2 rounded-pill extra-small">
                                <i class="bi bi-clock-history me-1"></i>Drafting
                            </span>
                        <?php endif; ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 px-2 rounded-pill extra-small text-uppercase">
                            <?= htmlspecialchars($report['nama_bidang']) ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-4 h-100 border border-light">
                        <label class="text-label mb-1">📅 Tanggal Pelaksanaan</label>
                        <div class="fw-bold text-dark"><?= format_tanggal_indonesia($report['tanggal']) ?></div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-4 h-100 border border-light">
                        <label class="text-label mb-1">📍 Lokasi Kegiatan</label>
                        <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($report['lokasi'] ?: '-') ?></div>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="text-label mb-2">📜 Output Capaian Kinerja</label>
                <div class="p-3 border-2 border-dashed rounded-4 bg-light bg-opacity-50 fw-bold text-primary">
                    <i class="bi bi-patch-check-fill me-2"></i><?= htmlspecialchars($report['nama_output']) ?>
                </div>
            </div>
            
            <?php if ($report['uraian_singkat']): ?>
            <div class="mb-4">
                <label class="text-label mb-2">📝 Uraian Deskripsi</label>
                <div class="p-4 bg-light rounded-4 text-muted lh-lg shadow-inner" style="white-space: pre-wrap; font-style: italic;"><?= htmlspecialchars($report['uraian_singkat']) ?></div>
            </div>
            <?php endif; ?>
            
            <div class="d-flex align-items-center pt-3 border-top mt-4">
                <div class="text-muted extra-small">
                    <i class="bi bi-info-circle me-1"></i>ID Laporan: #EK-<?= str_pad($report['id'], 5, '0', STR_PAD_LEFT) ?>
                </div>
                <div class="ms-auto text-muted extra-small">
                    Tercatat: <?= date('d M Y, H:i', strtotime($report['created_at'])) ?>
                </div>
            </div>
        </div>
        
        <!-- Photos Section -->
        <?php if (!empty($fotos)): ?>
        <div class="card-modern border-0 p-4">
            <h5 class="title-main mb-4"><i class="bi bi-camera me-2 text-primary"></i>Dokumentasi Lapangan</h5>
            <div class="row g-3">
                <?php foreach ($fotos as $f): ?>
                    <div class="col-md-4 col-6">
                        <div class="gallery-card rounded-4 overflow-hidden shadow-sm aspect-ratio-4x3 position-relative group">
                            <a href="uploads/foto/<?= $f['file'] ?>" target="_blank" class="d-block h-100">
                                <img src="uploads/foto/<?= $f['file'] ?>" class="w-100 h-100 object-fit-cover transition" alt="Foto Capaian">
                                <div class="position-absolute inset-0 bg-dark bg-opacity-20 opacity-0 group-hover-opacity-100 transition d-flex align-items-center justify-content-center">
                                    <i class="bi bi-zoom-in text-white fs-4"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4 animate-up" style="animation-delay: 0.1s">
        <!-- BKN Integration Card -->
        <div class="card-modern border-0 p-4 sticky-top mb-4" style="top: 100px; border-top: 5px solid #198754 !important;">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-success bg-opacity-10 text-success rounded-3 p-2 me-3">
                    <i class="bi bi-cloud-arrow-up fs-4"></i>
                </div>
                <h5 class="fw-bold text-dark mb-0">Integrasi BKN</h5>
            </div>
            
            <p class="text-muted small mb-4">Gunakan narasi di bawah ini untuk input pada aplikasi <strong>e-Kinerja BKN</strong> (SIASN):</p>
            
            <div class="mb-4">
                <div class="p-3 bg-dark text-light rounded-4 shadow-sm font-monospace position-relative overflow-hidden" id="teks-bkn" style="white-space: pre-wrap; font-size: 13px; line-height: 1.6; min-height: 120px;">
                    <div class="position-absolute top-0 end-0 p-2 me-1 mt-1 opacity-25">
                        <i class="bi bi-quote fs-1"></i>
                    </div>
<?= htmlspecialchars($report['teks_bkn']) ?>
                </div>
            </div>
            
            <button class="btn btn-modern btn-success w-100 mb-3 py-3 shadow-sm position-relative overflow-hidden group" onclick="copyTeksBKN()">
                <span class="position-relative z-index-1">
                    <i class="bi bi-clipboard-check me-2"></i>Salin Teks Capaian
                </span>
                <div class="position-absolute inset-0 bg-white opacity-0 group-active-opacity-20 transition"></div>
            </button>

            <!-- Pre-calculate PDF link -->
            <?php $pdf_link = "laporan_pdf.php?id=" . $report['kegiatan_id']; ?>
            <a href="<?= $pdf_link ?>" class="btn btn-modern btn-outline-danger w-100 mb-4 py-2">
                <i class="bi bi-file-earmark-pdf-fill me-2"></i>Unduh Laporan PDF
            </a>
            
            <div id="copy-feedback" class="p-3 bg-success bg-opacity-10 text-success border border-success border-opacity-10 rounded-4 shadow-sm mb-4 text-center animate-up" style="display:none;">
                <i class="bi bi-check-circle-fill me-2"></i>Teks berhasil disalin ke clipboard
            </div>
            
            <hr class="my-4 opacity-10">
            
            <div class="d-grid gap-2">
                <a href="kegiatan_detail.php?id=<?= $report['kegiatan_id'] ?>" class="btn btn-modern btn-light border py-2">
                    <i class="bi bi-link-45deg me-2 text-primary"></i>Sumber Kegiatan
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function copyTeksBKN() {
    const teksBkn = document.getElementById('teks-bkn').innerText;
    const feedback = document.getElementById('copy-feedback');
    
    navigator.clipboard.writeText(teksBkn).then(() => {
        feedback.style.display = 'block';
        window.scrollTo({ top: feedback.offsetTop - 150, behavior: 'smooth' });
        setTimeout(() => {
            feedback.style.display = 'none';
        }, 3000);
    }).catch(err => {
        alert('Gagal menyalin teks. Silakan copy manual.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Toast auto-hide
    var toastEl = document.querySelector('.toast');
    if (toastEl) {
        setTimeout(() => {
            toastEl.classList.remove('show');
            toastEl.classList.add('hide');
        }, 3000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>

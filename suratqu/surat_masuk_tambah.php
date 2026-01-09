<?php
// surat_masuk_tambah.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

include 'includes/header.php';

// Generate No. Agenda Otomatis (Sederhana: Count + 1)
$year = date('Y');
$stmt = $db->query("SELECT COUNT(*) as total FROM surat_masuk WHERE YEAR(tgl_diterima) = '$year'");
$count = $stmt->fetch()['total'];
$next_agenda = "SM/" . str_pad($count + 1, 3, '0', STR_PAD_LEFT) . "/" . $year;
?>

<style>
/* Mobile-First Improvements */
.form-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.form-section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-label {
    font-size: 0.85rem !important;
    margin-bottom: 0.25rem !important;
}

.form-control, .form-select {
    font-size: 0.9rem;
}

.help-text {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* Mobile-optimized buttons */
@media (max-width: 768px) {
    .btn-group-mobile {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-group-mobile .btn {
        width: 100%;
        justify-content: center;
    }
    
    .sidebar-hints {
        display: none;
    }
}

/* Better touch targets */
.btn {
    min-height: 44px;
    padding: 0.75rem 1.5rem;
}

.form-control {
    min-height: 44px;
}

/* Compact card */
.card-compact {
    padding: 1.25rem !important;
}

/* Reduced spacing on mobile */
@media (max-width: 768px) {
    .mb-4 {
        margin-bottom: 1.5rem !important;
    }
    
    .pt-2 {
        padding-top: 0.5rem !important;
    }
    
    .form-section {
        padding: 0.75rem;
    }
}
</style>

<div class="mb-3 pt-2">
    <h2 class="fw-bold mb-1 h4">📝 Tambah Surat Masuk</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="surat_masuk.php">Surat Masuk</a></li>
            <li class="breadcrumb-item active">Tambah</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Main Form -->
    <div class="col-lg-9 col-xl-8">
        <div class="card card-custom card-compact border-0 shadow-sm mb-4">
            <form action="surat_masuk_proses.php" method="POST" enctype="multipart/form-data">
                
                <!-- Section 1: Identitas Surat -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fa-solid fa-id-card text-primary"></i>
                        Identitas Surat
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Agenda</label>
                            <input type="text" name="no_agenda" class="form-control bg-light" value="<?= $next_agenda ?>" readonly>
                            <div class="help-text">Auto-generate saat diagendakan</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Asal Surat <span class="text-danger">*</span></label>
                            <input type="text" name="asal_surat" id="asal_surat" class="form-control" placeholder="Dinas Kesehatan" required>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Detail Surat -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fa-solid fa-file-lines text-success"></i>
                        Detail Surat
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Surat <span class="text-danger">*</span></label>
                            <input type="text" name="no_surat" id="no_surat" class="form-control" placeholder="000/123/45.6/2026" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Surat <span class="text-danger">*</span></label>
                            <input type="date" name="tgl_surat" id="tgl_surat" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Perihal <span class="text-danger">*</span></label>
                            <textarea name="perihal" id="perihal" class="form-control" rows="2" placeholder="Ringkas perihal surat..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tujuan / Alamat Surat</label>
                            <input type="text" name="tujuan" class="form-control" placeholder="Kepada Yth. Bupati (opsional)">
                            <div class="help-text">Sesuai "Kepada" di surat fisik</div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Klasifikasi & Disposisi -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fa-solid fa-folder text-warning"></i>
                        Klasifikasi & Disposisi
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Klasifikasi Arsip <span class="text-danger">*</span></label>
                            <select name="klasifikasi" id="klasifikasi" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="umum">Umum (000)</option>
                                <option value="kepegawaian">Kepegawaian (800)</option>
                                <option value="keuangan">Keuangan (900)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Disposisi Langsung Ke</label>
                            <input type="text" name="tujuan_text" class="form-control" placeholder="Default: Camat">
                            <div class="help-text">Kosongkan untuk auto ke Camat</div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Upload File -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fa-solid fa-file-arrow-up text-danger"></i>
                        File Scan Surat
                    </div>
                    <label class="form-label fw-semibold">File Scan Surat <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="file" name="file_surat" id="file_surat" class="form-control" accept=".pdf">
                        <button type="button" id="btn-scan-ocr" class="btn btn-warning">
                            <i class="fa-solid fa-expand"></i>
                            <span class="d-none d-md-inline ms-1">OCR</span>
                        </button>
                    </div>
                    <div class="help-text">
                        <i class="fa-solid fa-circle-info"></i>
                        Gunakan format PDF (Hasil scan, foto, atau file dari aplikasi SRIKANDI) untuk telaah Pimpinan. Max 10MB.
                    </div>
                </div>

                <!-- Loading OCR -->
                <div id="ocr-loader" class="d-none alert alert-info py-2">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        <small>Memindai dokumen...</small>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-3 pt-3 border-top">
                    <div class="row g-2">
                        <div class="col-md-6 col-lg-auto order-md-2">
                            <button type="submit" name="action" value="agendakan" class="btn btn-success w-100 fw-bold shadow-sm">
                                <i class="fa-solid fa-check-double"></i>
                                <span class="d-none d-sm-inline ms-1">Agendakan & Disposisi</span>
                                <span class="d-inline d-sm-none ms-1">Proses</span>
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-auto order-md-1">
                            <button type="submit" name="action" value="draft" class="btn btn-outline-secondary w-100">
                                <i class="fa-regular fa-floppy-disk"></i>
                                <span class="d-none d-sm-inline ms-1">Simpan Draft</span>
                                <span class="d-inline d-sm-none ms-1">Draft</span>
                            </button>
                        </div>
                        <div class="col-12 col-lg-auto order-md-3">
                            <a href="surat_masuk.php" class="btn btn-light w-100">
                                <i class="fa-solid fa-xmark"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar - Hidden on mobile -->
    <div class="col-lg-3 col-xl-4 sidebar-hints">
        <div class="card card-custom p-3 bg-primary-subtle border-0 shadow-sm mb-3">
            <h6 class="fw-bold mb-2 text-primary">
                <i class="fa-solid fa-lightbulb"></i> Petunjuk
            </h6>
            <ul class="small mb-0 ps-3">
                <li class="mb-2">Nomor agenda otomatis saat diagendakan</li>
                <li class="mb-2">Gunakan OCR untuk auto-fill dari file</li>
                <li class="mb-2">File harus jelas min 300dpi</li>
                <li class="mb-0">Max ukuran file 10MB</li>
            </ul>
        </div>

        <div id="ocr-results-card" class="card card-custom p-3 border-0 shadow-sm d-none">
            <h6 class="fw-bold mb-2 small">
                <i class="fa-solid fa-list-check text-success"></i> Hasil OCR
            </h6>
            <div id="ocr-raw-text" class="small text-muted p-2 border rounded bg-light" style="max-height: 150px; overflow-y: auto; font-size: 10px;"></div>
        </div>
    </div>
</div>

<script>
// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const action = e.submitter ? e.submitter.value : '';
    
    if (action === 'agendakan') {
        const fileInput = document.getElementById('file_surat');
        const required = ['asal_surat', 'no_surat', 'tgl_surat', 'perihal', 'klasifikasi'];
        let errors = [];

        required.forEach(id => {
            const field = document.getElementById(id);
            if (!field || !field.value.trim()) {
                errors.push(field?.previousElementSibling?.textContent?.replace('*', '').trim() || id);
            }
        });

        if (fileInput.files.length === 0) errors.push("File Scan Surat");

        if (errors.length > 0) {
            e.preventDefault();
            alert("Wajib dilengkapi untuk Agendakan:\n\n• " + errors.join("\n• "));
        }
    }
});

// OCR functionality
document.getElementById('btn-scan-ocr').addEventListener('click', function() {
    const fileInput = document.getElementById('file_surat');
    const loader = document.getElementById('ocr-loader');
    const resultsCard = document.getElementById('ocr-results-card');
    const rawTextContainer = document.getElementById('ocr-raw-text');

    if (fileInput.files.length === 0) {
        alert('Pilih file surat terlebih dahulu!');
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    loader.classList.remove('d-none');
    this.disabled = true;

    fetch('api_ocr.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loader.classList.add('d-none');
        this.disabled = false;

        if (data.status === 'success') {
            if (data.no_surat) document.getElementById('no_surat').value = data.no_surat;
            if (data.perihal) document.getElementById('perihal').value = data.perihal;
            if (data.tgl_surat) document.getElementById('tgl_surat').value = data.tgl_surat;
            if (data.asal_surat) document.getElementById('asal_surat').value = data.asal_surat;
            if (data.tujuan_surat) document.getElementsByName('tujuan')[0].value = data.tujuan_surat;
            
            resultsCard.classList.remove('d-none');
            rawTextContainer.innerText = data.raw_text;
            
            alert('✅ Pemindaian selesai! Kolom terisi otomatis.');
        } else {
            alert('❌ Gagal OCR: ' + data.message);
        }
    })
    .catch(error => {
        loader.classList.add('d-none');
        this.disabled = false;
        console.error('Error:', error);
        alert('⚠️ Error menghubungi API OCR.');
    });
});
</script>

<?php include 'includes/footer.php'; ?>

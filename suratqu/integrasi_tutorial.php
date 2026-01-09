<?php
// integrasi_tutorial.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

require_auth();

// Only admin can access
if ($_SESSION['role'] !== 'admin') {
    redirect('index.php', 'Akses ditolak!', 'danger');
}

include 'includes/header.php';
?>

<div class="mb-4 pt-2 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold mb-1">Panduan Integrasi Sistem</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="integrasi_sistem.php">Integrasi</a></li>
                <li class="breadcrumb-item active">Panduan</li>
            </ol>
        </nav>
    </div>
    <a href="integrasi_sistem.php" class="btn btn-outline-primary btn-sm px-4">
        <i class="fa-solid fa-arrow-left me-2"></i> Kembali ke Monitoring
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- 1. Konsep Dasar -->
        <div class="card card-custom border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-wand-magic-sparkles me-2"></i> Bagaimana Ini Bekerja?</h5>
                <p>Bayangkan integrasi ini seperti <strong>kurir otomatis</strong>. Setiap kali Bapak/Ibu membuat disposisi surat di SuratQu, sistem akan langsung "menginfokan" sistem lain (misalnya aplikasi Docku) secara otomatis tanpa perlu input dua kali.</p>
                
                <div class="bg-light p-3 rounded-4 border-start border-4 border-primary small">
                    <p class="mb-1 fw-bold">Keuntungan Integrasi:</p>
                    <ul class="mb-0">
                        <li><strong>Hemat Waktu:</strong> Tidak perlu buka-tutup banyak aplikasi.</li>
                        <li><strong>Pasti Terkirim:</strong> Info disposisi langsung muncul di kotak masuk sistem sebelah.</li>
                        <li><strong>Terpantau:</strong> Kita bisa lihat apakah info tersebut sudah diterima atau gagal di menu Monitoring.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 2. Langkah Mudah -->
        <div class="card card-custom border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-flag-checkered me-2"></i> 3 Langkah Mudah Menghubungkan Sistem</h5>
                <div class="timeline-simple">
                    <div class="mb-4">
                        <div class="fw-bold mb-1">Step 1: Dapatkan "Alamat" & "Kunci"</div>
                        <p class="small text-muted">Minta pihak aplikasi tujuan (misal: Admin Docku) untuk memberikan <strong>URL Endpoint</strong> (alamat kirim) dan <strong>Kunci Integrasi</strong>.</p>
                    </div>
                    <div class="mb-4">
                        <div class="fw-bold mb-1">Step 2: Masukkan ke Pengaturan</div>
                        <p class="small text-muted">Buka menu <strong>"Pengaturan"</strong> di pojok kanan atas halaman Integrasi ini, lalu masukkan Alamat dan Kunci yang sudah didapat.</p>
                    </div>
                    <div class="mb-0">
                        <div class="fw-bold mb-1">Step 3: Tes Selesai!</div>
                        <p class="small text-muted mb-0">Coba buat satu disposisi surat, lalu cek di halaman <strong>Integrasi Sistem</strong>. Jika muncul tanda centang hijau <span class="badge bg-success">Success</span>, berarti sistem sudah "bersalaman" dengan sukses.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Untuk Orang Teknis (Advanced) -->
        <div class="accordion card-custom shadow-sm border-0 mb-4" id="accordionAdvanced">
            <div class="accordion-item border-0 rounded-4 overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold text-muted bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                        <i class="fa-solid fa-gear me-2"></i> Informasi Teknis (Untuk Admin/IT)
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionAdvanced">
                    <div class="accordion-body bg-light small">
                        <p>Data dikirim dalam format JSON via <strong>POST Request</strong>. Berikut adalah contoh data yang dikirimkan:</p>
                        <div class="bg-dark p-3 rounded text-light mb-3">
                            <pre class="mb-0" style="font-size: 0.7rem;">{ "event": "DISPOSISI_CREATED", "disposisi_id": 45, "instruksi": "...", ... }</pre>
                        </div>
                        <p class="mb-0">Pastikan server tujuan mengizinkan IP server SuratQu dan mendukung koneksi HTTPS.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Keamanan -->
        <div class="card card-custom border-0 shadow-sm mb-4 bg-primary-subtle border-start border-5 border-primary">
            <div class="card-body p-4">
                <h6 class="fw-bold"><i class="fa-solid fa-user-shield me-2"></i> Aman & Terlindungi</h6>
                <p class="small mb-0 text-dark">Data yang dikirim hanya informasi disposisi dasar. Kunci integrasi memastikan hanya sistem yang sah yang bisa menerima data ini.</p>
            </div>
        </div>

        <!-- FAQ Sederhana -->
        <div class="card card-custom border-0 shadow-sm p-4">
            <h6 class="fw-bold mb-3">Tanya Jawab</h6>
            <div class="mb-3">
                <div class="fw-bold small mb-1">Bagaimana jika sistem tujuan mati?</div>
                <div class="small text-muted">SuratQu akan mencatat status "Failed". Anda bisa klik tombol <strong>Retry</strong> nanti saat sistem tujuan sudah aktif kembali.</div>
            </div>
            <div class="mb-0">
                <div class="fw-bold small mb-1">Membatalkan Integrasi?</div>
                <div class="small text-muted">Cukup ubah status <code>'enabled' => false</code> di file konfigurasi.</div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-simple {
    border-left: 2px solid #e9ecef;
    padding-left: 20px;
}
.timeline-simple > div {
    position: relative;
}
.timeline-simple > div::before {
    content: '';
    position: absolute;
    left: -27px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #34C759;
    border: 2px solid white;
}
</style>

<?php include 'includes/footer.php'; ?>

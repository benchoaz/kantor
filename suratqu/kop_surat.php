<?php
// kop_surat.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// Only admin can access this module
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['alert'] = ['msg' => 'Akses ditolak!', 'type' => 'danger'];
    header("Location: index.php");
    exit;
}

include 'includes/header.php';

// Fetch Active Kop Surat
$stmt = $db->query("SELECT * FROM kop_surat WHERE is_active = 1 LIMIT 1");
$kop = $stmt->fetch();

// If no active kop, create a default one or fetch the first
if (!$kop) {
    $stmt = $db->query("SELECT * FROM kop_surat LIMIT 1");
    $kop = $stmt->fetch();
}
?>

<div class="mb-4 pt-2">
    <h2 class="fw-bold mb-1">Manajemen Kop Surat Fleksibel</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Kop Surat</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card card-custom p-4 border-0 shadow-sm mb-4">
            <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="fa-solid fa-pen-nib me-2 text-primary"></i> Konfigurasi Header (Kop Surat)</h5>
            
            <form action="kop_surat_proses.php" method="POST" enctype="multipart/form-data">
                <!-- Pastikan ID Kop terkirim -->
                <input type="hidden" name="id_kop" value="<?= htmlspecialchars($kop['id_kop'] ?? '1') ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Teks Baris 1 (Pemerintah Daerah)</label>
                    <input type="text" name="nama_instansi" class="form-control" value="<?= htmlspecialchars($kop['nama_instansi'] ?? 'PEMERINTAH KABUPATEN PROBOLINGGO') ?>" placeholder="Contoh: PEMERINTAH KABUPATEN PROBOLINGGO" required>
                    <div class="form-text small">Biasanya berisi nama Pemerintah Kabupaten/Kota.</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Teks Baris 2 (Induk Organisasi / Dinas)</label>
                    <input type="text" name="nama_instansi_l1" class="form-control" value="<?= htmlspecialchars($kop['nama_instansi_l1'] ?? 'DINAS KESEHATAN') ?>" placeholder="Contoh: DINAS KESEHATAN">
                    <div class="form-text small">Biasanya berisi nama Dinas atau Sekretariat Daerah.</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Teks Baris 3 (Unit Kerja / Nama Kantor Anda)</label>
                    <input type="text" name="nama_instansi_l2" class="form-control fw-bold border-primary" value="<?= htmlspecialchars($kop['nama_instansi_l2'] ?? 'PUSKESMAS BANTARAN') ?>" placeholder="Contoh: PUSKESMAS BANTARAN">
                    <div class="form-text small text-primary">Ini adalah teks yang akan dicetak paling tebal/besar.</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Alamat & Kontak Detail</label>
                    <textarea name="alamat" class="form-control mb-2" rows="2" placeholder="Alamat lengkap kantor..."><?= htmlspecialchars($kop['alamat'] ?? '') ?></textarea>
                    <input type="text" name="kontak" class="form-control" value="<?= htmlspecialchars($kop['kontak'] ?? '') ?>" placeholder="Email: puskesmas@mail.go.id | Telp: (0335) ...">
                </div>
                
                <div class="row g-3 pt-3 border-top mt-2">
                    <div class="col-md-6 border-end">
                        <label class="form-label fw-bold small text-muted text-uppercase">Logo Kiri (Wajib)</label>
                        <div class="d-flex align-items-center mb-2">
                            <img src="<?= ($kop['logo_path']) ?: 'assets/img/logo_kab.png' ?>" class="img-thumbnail me-2" style="height: 50px;">
                            <input type="file" name="logo_kiri" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <small class="text-muted" style="font-size: 0.7rem;">Gunakan Logo Daerah Kabupaten/Kota.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Logo Kanan (Opsional)</label>
                        <div class="d-flex align-items-center mb-2">
                            <img src="<?= ($kop['logo_kanan_path']) ?: 'https://via.placeholder.com/50' ?>" class="img-thumbnail me-2" style="height: 50px;">
                            <input type="file" name="logo_kanan" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <small class="text-muted" style="font-size: 0.7rem;">Contoh: Logo Bakti Husada atau Logo Puskesmas.</small>
                    </div>
                </div>
                
                <div class="pt-4 mt-3 border-top">
                    <button type="submit" name="save_kop" class="btn btn-primary px-5 fw-bold shadow">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i> Simpan & Terapkan Kop Baru
                    </button>
                    <a href="index.php" class="btn btn-light px-4 ms-2">Batal</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-lg-5">
        <!-- Live Preview Simulation Card -->
        <div class="card card-custom p-0 border-0 shadow-sm mb-4 overflow-hidden border border-primary">
            <div class="bg-primary p-3 text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold small"><i class="fa-solid fa-eye me-2"></i> PRATINJAU REAL-TIME</h6>
                <span class="badge bg-white text-primary px-3 py-1 fw-bold">HASIL CETAK</span>
            </div>
            <div class="p-4 bg-white" id="preview-render" style="font-family: 'Times New Roman', serif;">
                <div class="d-flex align-items-center justify-content-between border-bottom border-3 border-dark pb-2" style="min-height: 100px;">
                    <div style="width: 60px;">
                        <img src="<?= ($kop['logo_path']) ?: 'assets/img/logo_kab.png' ?>" style="max-width: 60px;">
                    </div>
                    <div class="text-center flex-grow-1 px-2">
                        <div class="fw-bold" style="font-size: 13px;"><?= strtoupper(htmlspecialchars($kop['nama_instansi'] ?? 'PEMERINTAH KABUPATEN PROBOLINGGO')) ?></div>
                        <div class="fw-bold" style="font-size: 13px;"><?= strtoupper(htmlspecialchars($kop['nama_instansi_l1'] ?? 'DINAS KESEHATAN')) ?></div>
                        <div class="fw-bold" style="font-size: 16px;"><?= strtoupper(htmlspecialchars($kop['nama_instansi_l2'] ?? 'PUSKESMAS BANTARAN')) ?></div>
                        <div style="font-size: 9px; line-height: 1.1;">
                            <?= nl2br(htmlspecialchars($kop['alamat'] ?? '')) ?> <br>
                            <?= htmlspecialchars($kop['kontak'] ?? '') ?>
                        </div>
                    </div>
                    <div style="width: 60px;" class="text-end">
                        <?php if(!empty($kop['logo_kanan_path'])): ?>
                            <img src="<?= $kop['logo_kanan_path'] ?>" style="max-width: 60px;">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="border-top border-1 border-dark mt-1"></div>
            </div>
        </div>
        
        <div class="card card-custom p-4 bg-white border-0 shadow-sm">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-lightbulb me-2 text-warning"></i> Tips Kustomisasi:</h6>
            <ul class="small ps-3 text-muted">
                <li class="mb-2">Gunakan **HURUF KAPITAL** untuk nama instansi agar terlihat lebih formal dan tegas.</li>
                <li class="mb-2">Jika tidak memiliki Logo Kanan, kosongkan saja inputnya untuk layout standar 1 logo.</li>
                <li class="mb-2">Alamat yang terlalu panjang sebaiknya diringkas agar tidak memakan ruang surat terlalu banyak.</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

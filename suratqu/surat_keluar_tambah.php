<?php
// surat_keluar_tambah.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

include 'includes/header.php';

// Mock user_id 1 for demo
$my_id = 1;
?>

<div class="mb-4 pt-2">
    <h2 class="fw-bold mb-1">Buat Surat Keluar</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="surat_keluar.php">Surat Keluar</a></li>
            <li class="breadcrumb-item active">Buat Baru</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom p-4 border-0 shadow-sm mb-4">
            <form action="surat_keluar_proses.php" method="POST">
                <input type="hidden" name="id_user_pembuat" value="<?= $my_id ?>">
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Pilih Jenis Surat (Template)</label>
                        <select name="id_template" id="templateSelector" class="form-select shadow-sm">
                            <option value="">-- Tanpa Template (Manual) --</option>
                            <?php
                            $templates = $db->query("SELECT id_template, nama_template FROM template_surat ORDER BY nama_template ASC")->fetchAll();
                            foreach ($templates as $t) {
                                echo "<option value='{$t['id_template']}'>{$t['nama_template']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Tujuan Surat</label>
                        <input type="text" name="tujuan" class="form-control" placeholder="Contoh: Kepala Dinas Pendidikan Kota..." required>
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Perihal</label>
                        <input type="text" name="perihal" class="form-control" placeholder="Isi ringkas perihal surat..." required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tanggal Surat</label>
                        <input type="date" name="tgl_surat" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Klasifikasi</label>
                        <select name="klasifikasi" class="form-select">
                            <option value="400">Kesejahteraan Rakyat (400)</option>
                            <option value="100">Pemerintahan (100)</option>
                            <option value="500">Perekonomian (500)</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Isi Surat</label>
                        <textarea name="isi_surat" id="isiSurat" class="form-control" rows="10" placeholder="Ketikkan narasi atau isi pesan surat di sini..." required></textarea>
                    </div>
                    
                    <div class="col-12 pt-3">
                        <button type="submit" name="submit" class="btn btn-primary px-4 me-2">
                            <i class="fa-solid fa-save me-2"></i> Simpan Draft
                        </button>
                        <a href="surat_keluar.php" class="btn btn-light px-4">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card card-custom p-4 bg-info text-white border-0 shadow-sm">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-lightbulb me-2"></i> Info Alur:</h5>
            <p class="small mb-2">1. Isi data draft surat keluar di samping.</p>
            <p class="small mb-2">2. Gunakan pratinjau untuk melihat tampilan surat dengan Kop resmi.</p>
            <p class="small mb-2">3. Minta verifikasi pimpinan agar mendapatkan Nomor Surat otomatis.</p>
            <p class="small mb-0">4. Cetak PDF untuk ditandatangani.</p>
        </div>
    </div>
</div>

<script>
document.getElementById('templateSelector').addEventListener('change', function() {
    const templateId = this.value;
    const isiSurat = document.getElementById('isiSurat');
    
    if (templateId) {
        // Show loading state or text
        isiSurat.placeholder = 'Sedang mengambil template...';
        
        fetch('get_template.php?id=' + templateId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    isiSurat.value = data.content;
                } else {
                    alert('Gagal mengambil template: ' + data.message);
                }
                isiSurat.placeholder = 'Ketikkan narasi atau isi pesan surat di sini...';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan koneksi.');
            });
    } else {
        isiSurat.value = '';
    }
});
</script>

<?php include 'includes/footer.php'; ?>

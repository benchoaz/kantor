<?php
// ekinerja_edit.php - Edit e-Kinerja report
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_role(['admin', 'operator']);

$page_title = 'Edit Laporan e-Kinerja';
$active_page = 'ekinerja';

$id = $_GET['id'] ?? 0;
$error = '';

// Fetch existing data
$stmt = $pdo->prepare("SELECT le.*, k.judul, k.tanggal, k.bidang_id, b.nama_bidang
                       FROM laporan_ekinerja le
                       JOIN kegiatan k ON le.kegiatan_id = k.id
                       JOIN bidang b ON k.bidang_id = b.id
                       WHERE le.id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    header("Location: ekinerja.php");
    exit;
}

// Fetch output_kinerja for this bidang
$stmt = $pdo->prepare("SELECT * FROM output_kinerja WHERE bidang_id = ? ORDER BY nama_output");
$stmt->execute([$report['bidang_id']]);
$output_list = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $output_kinerja_id = $_POST['output_kinerja_id'] ?? '';
    $uraian_singkat = $_POST['uraian_singkat'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    
    if ($output_kinerja_id) {
        try {
            // Fetch new output kinerja name
            $stmt = $pdo->prepare("SELECT nama_output FROM output_kinerja WHERE id = ?");
            $stmt->execute([$output_kinerja_id]);
            $ok = $stmt->fetch();
            
            if ($ok) {
                // Regenerate BKN text
                $teks_bkn = generate_teks_bkn($report['judul'], $report['tanggal'], $ok['nama_output']);
                
                // Update report
                $stmt = $pdo->prepare("UPDATE laporan_ekinerja SET output_kinerja_id = ?, uraian_singkat = ?, teks_bkn = ?, status = ? WHERE id = ?");
                $stmt->execute([$output_kinerja_id, $uraian_singkat, $teks_bkn, $status, $id]);
                
                header("Location: ekinerja_detail.php?id=$id&msg=updated");
                exit;
            }
        } catch (Exception $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = "Pilih output kinerja terlebih dahulu.";
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center animate-up">
    <div class="col-lg-8">
        <div class="d-flex align-items-center mb-4">
            <a href="ekinerja_detail.php?id=<?= $id ?>" class="btn btn-light border-0 rounded-circle p-2 me-3 shadow-sm">
                <i class="bi bi-arrow-left fs-5"></i>
            </a>
            <div>
                <h3 class="title-main mb-0">Ubah Laporan Kinerja</h3>
                <p class="text-muted small mb-0">Perbarui dokumentasi capaian untuk validasi akhir</p>
            </div>
        </div>

        <div class="card-modern border-0 p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="p-3 bg-primary bg-opacity-10 border border-primary border-opacity-10 rounded-4 mb-4">
                    <div class="d-flex">
                        <i class="bi bi-info-circle-fill text-primary me-3 fs-4"></i>
                        <div>
                            <label class="text-label text-primary opacity-75 mb-1 d-block">Kegiatan Referensi</label>
                            <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($report['judul']) ?></div>
                            <div class="small text-muted mt-1">
                                <span class="me-3"><i class="bi bi-calendar-event me-1"></i><?= format_tanggal_indonesia($report['tanggal']) ?></span>
                                <span><i class="bi bi-briefcase me-1"></i><?= htmlspecialchars($report['nama_bidang']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-label mb-2">Pilih Output Kinerja <span class="text-danger">*</span></label>
                    <select name="output_kinerja_id" class="form-select border-2 shadow-none" required>
                        <option value="">-- Pilih Output Kinerja --</option>
                        <?php foreach ($output_list as $o): ?>
                            <option value="<?= $o['id'] ?>" <?= ($o['id'] == $report['output_kinerja_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($o['nama_output']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="text-label mb-2">Uraian Singkat / Catatan (Opsional)</label>
                    <textarea name="uraian_singkat" class="form-control border-2 shadow-none" rows="3" placeholder="Tambahkan keterangan kontekstual jika diperlukan..."><?= htmlspecialchars($report['uraian_singkat']) ?></textarea>
                </div>
                
                <div class="p-3 bg-light rounded-4 mb-4">
                    <label class="text-label mb-3 d-block">Finalisasi Laporan</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check card-modern bg-white p-3 border m-0 h-100 cursor-pointer">
                                <input class="form-check-input ms-0 me-2" type="radio" name="status" id="status_draft" value="draft" <?= ($report['status'] === 'draft') ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold d-block" for="status_draft" style="margin-left: 25px;">
                                    Drafting
                                    <small class="text-muted d-block fw-normal extra-small">Laporan masih dalam proses sinkronisasi.</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check card-modern bg-white p-3 border m-0 h-100 cursor-pointer">
                                <input class="form-check-input ms-0 me-2" type="radio" name="status" id="status_siap" value="siap" <?= ($report['status'] === 'siap') ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold d-block" for="status_siap" style="margin-left: 25px;">
                                    Siap e-Kinerja
                                    <small class="text-muted d-block fw-normal extra-small">Siap divalidasi dan di-upload ke sistem BKN.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="ekinerja_detail.php?id=<?= $id ?>" class="btn btn-modern btn-light border px-4">Batal</a>
                    <button type="submit" class="btn btn-modern btn-primary-modern flex-fill py-3 shadow-sm">
                        <i class="bi bi-save me-2"></i> Simpan Perubahan Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

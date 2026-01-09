<?php
// ekinerja_tambah.php - Create new e-Kinerja report
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_once 'includes/ekinerja_helpers.php'; // Compliance system
require_role(['admin', 'operator']);

$page_title = 'Tambah Laporan e-Kinerja';
$active_page = 'ekinerja';

$error = '';
$success = '';

// Get kegiatan_id if coming from confirmation dialog
$kegiatan_id = $_GET['kegiatan_id'] ?? null;
$kegiatan_data = null;
$output_list = [];

// Get user's jabatan level for compliance filtering
$user_jabatan_level = get_user_jabatan_level($pdo, $_SESSION['user_id']);

if ($kegiatan_id) {
    // Auto-fill mode: fetch kegiatan data
    $stmt = $pdo->prepare("SELECT k.*, b.nama_bidang FROM kegiatan k JOIN bidang b ON k.bidang_id = b.id WHERE k.id = ?");
    $stmt->execute([$kegiatan_id]);
    $kegiatan_data = $stmt->fetch();
    
    if (!$kegiatan_data) {
        header("Location: kegiatan.php");
        exit;
    }
    
    // Check if already has e-Kinerja report
    $check = $pdo->prepare("SELECT id FROM laporan_ekinerja WHERE kegiatan_id = ?");
    $check->execute([$kegiatan_id]);
    if ($check->fetch()) {
        header("Location: kegiatan_detail.php?id=$kegiatan_id&msg=already_has_ekinerja");
        exit;
    }
    
    // Fetch output_kinerja FILTERED BY JABATAN (Compliance!)
    // User only sees templates matching their jabatan level
    $output_list = get_output_by_jabatan($pdo, $user_jabatan_level, $kegiatan_data['bidang_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kegiatan_id = $_POST['kegiatan_id'] ?? '';
    $output_kinerja_id = $_POST['output_kinerja_id'] ?? '';
    $uraian_singkat = $_POST['uraian_singkat'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    
    if ($kegiatan_id && $output_kinerja_id) {
        try {
            // Fetch kegiatan and output data
            $stmt = $pdo->prepare("SELECT judul, tanggal FROM kegiatan WHERE id = ?");
            $stmt->execute([$kegiatan_id]);
            $k = $stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT nama_output FROM output_kinerja WHERE id = ?");
            $stmt->execute([$output_kinerja_id]);
            $ok = $stmt->fetch();
            
            if (!$k || !$ok) {
                $error = "Data kegiatan atau output kinerja tidak ditemukan.";
            } else {
                // Generate BKN text
                $teks_bkn = generate_teks_bkn($k['judul'], $k['tanggal'], $ok['nama_output']);
                
                // Insert report
                $stmt = $pdo->prepare("INSERT INTO laporan_ekinerja (kegiatan_id, output_kinerja_id, uraian_singkat, teks_bkn, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$kegiatan_id, $output_kinerja_id, $uraian_singkat, $teks_bkn, $status]);
                
                // Update kegiatan status
                $pdo->prepare("UPDATE kegiatan SET status_ekinerja = 'sudah' WHERE id = ?")->execute([$kegiatan_id]);
                
                $new_id = $pdo->lastInsertId();
                header("Location: ekinerja_detail.php?id=$new_id&msg=success");
                exit;
            }
        } catch (Exception $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = "Pilih kegiatan dan output kinerja terlebih dahulu.";
    }
}

// Fetch all kegiatan without e-Kinerja (for manual mode)
$all_kegiatan = $pdo->query("SELECT k.id, k.judul, k.tanggal, b.nama_bidang 
                             FROM kegiatan k 
                             JOIN bidang b ON k.bidang_id = b.id 
                             WHERE k.status_ekinerja = 'belum' 
                             ORDER BY k.tanggal DESC")->fetchAll();

include 'includes/header.php';
?>

<div class="row justify-content-center animate-up">
    <div class="col-lg-8">
        <div class="d-flex align-items-center mb-4">
            <a href="ekinerja.php" class="btn btn-light border-0 rounded-circle p-2 me-3 shadow-sm">
                <i class="bi bi-arrow-left fs-5"></i>
            </a>
            <div>
                <h3 class="title-main mb-0">Tambah Laporan Kinerja</h3>
                <p class="text-muted small mb-0">Dokumentasi capaian indikator performa staf</p>
            </div>
        </div>

        <div class="card-modern border-0 p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="formEkinerja">
                <?php if ($kegiatan_data): ?>
                    <!-- Auto-fill mode -->
                    <input type="hidden" name="kegiatan_id" value="<?= $kegiatan_data['id'] ?>">
                    
                    <div class="p-3 bg-primary bg-opacity-10 border border-primary border-opacity-10 rounded-4 mb-4">
                        <div class="d-flex">
                            <i class="bi bi-info-circle-fill text-primary me-3 fs-4"></i>
                            <div>
                                <label class="text-label text-primary opacity-75 mb-1 d-block">Kegiatan Referensi</label>
                                <div class="fw-bold text-dark lh-sm"><?= htmlspecialchars($kegiatan_data['judul']) ?></div>
                                <div class="small text-muted mt-1">
                                    <span class="me-3"><i class="bi bi-calendar-event me-1"></i><?= format_tanggal_indonesia($kegiatan_data['tanggal']) ?></span>
                                    <span><i class="bi bi-briefcase me-1"></i><?= htmlspecialchars($kegiatan_data['nama_bidang']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Manual mode -->
                    <div class="mb-4">
                        <label class="text-label mb-2">Pilih Kegiatan <span class="text-danger">*</span></label>
                        <select name="kegiatan_id" id="kegiatan_id" class="form-select border-2 shadow-none" required onchange="loadOutputs()">
                            <option value="">-- Pilih Kegiatan --</option>
                            <?php foreach ($all_kegiatan as $k): ?>
                                <option value="<?= $k['id'] ?>" 
                                        data-bidang="<?= $k['id'] ?>"
                                        data-tanggal="<?= $k['tanggal'] ?>">
                                    <?= date('d/m/Y', strtotime($k['tanggal'])) ?> - <?= htmlspecialchars($k['judul']) ?> (<?= htmlspecialchars($k['nama_bidang']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($all_kegiatan)): ?>
                            <div class="text-warning small mt-2">
                                <i class="bi bi-exclamation-triangle"></i> Tidak ada kegiatan yang belum dibuat laporan e-Kinerja.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <label class="text-label mb-2 d-flex align-items-center">
                        Output Kinerja Berbasis Jabatan <span class="text-danger ms-1">*</span>
                        <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 rounded-pill ms-auto extra-small">
                            <i class="bi bi-shield-check me-1"></i> Level: <?= ucfirst($user_jabatan_level) ?>
                        </div>
                    </label>
                    <select name="output_kinerja_id" id="output_kinerja_id" class="form-select border-2 shadow-none" required>
                        <option value="">-- Pilih Output Kinerja --</option>
                        <?php foreach ($output_list as $o): ?>
                            <option value="<?= $o['id'] ?>" title="<?= htmlspecialchars($o['dasar_hukum'] ?? 'PP 30/2019, PermenPANRB 6/2022') ?>">
                                <?= htmlspecialchars($o['nama_output']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="p-3 bg-light rounded-4 mt-2 small text-muted border border-light">
                        <i class="bi bi-patch-check-fill text-primary me-2"></i>
                        Pilihan di atas disaring berdasarkan level jabatan Anda sesuai standar <strong>PP 30/2019</strong> & <strong>PermenPANRB 6/2022</strong>.
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="text-label mb-2">Uraian Singkat / Catatan (Opsional)</label>
                    <textarea name="uraian_singkat" class="form-control border-2 shadow-none" rows="3" placeholder="Tambahkan keterangan kontekstual jika diperlukan..."></textarea>
                </div>
                
                <div class="p-3 bg-light rounded-4 mb-4">
                    <label class="text-label mb-3 d-block">Finalisasi Laporan</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check card-modern bg-white p-3 border m-0 h-100 cursor-pointer">
                                <input class="form-check-input ms-0 me-2" type="radio" name="status" id="status_draft" value="draft">
                                <label class="form-check-label fw-bold d-block" for="status_draft" style="margin-left: 25px;">
                                    Drafting
                                    <small class="text-muted d-block fw-normal extra-small">Laporan masih dalam proses sinkronisasi.</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check card-modern bg-white p-3 border m-0 h-100 cursor-pointer">
                                <input class="form-check-input ms-0 me-2" type="radio" name="status" id="status_siap" value="siap" checked>
                                <label class="form-check-label fw-bold d-block" for="status_siap" style="margin-left: 25px;">
                                    Siap e-Kinerja
                                    <small class="text-muted d-block fw-normal extra-small">Siap divalidasi dan di-upload ke sistem BKN.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="ekinerja.php" class="btn btn-modern btn-light border px-4">Batal</a>
                    <button type="submit" class="btn btn-modern btn-primary-modern flex-fill py-3 shadow-sm">
                        <i class="bi bi-cloud-arrow-up me-2"></i> Simpan Laporan Kinerja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!$kegiatan_data): ?>
<script>
function loadOutputs() {
    const kegiatanSelect = document.getElementById('kegiatan_id');
    const kegiatanId = kegiatanSelect.value;
    const outputSelect = document.getElementById('output_kinerja_id');
    
    if (!kegiatanId) {
        outputSelect.innerHTML = '<option value="">-- Pilih Kegiatan terlebih dahulu --</option>';
        return;
    }
    
    // Fetch outputs via AJAX (simplified - you can enhance this)
    window.location.href = `ekinerja_tambah.php?kegiatan_id=${kegiatanId}`;
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

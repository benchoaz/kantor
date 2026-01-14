<?php
// disposisi.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$id_sm = $_GET['id_sm'] ?? null;

// Session check - CRITICAL FIX
if (!isset($_SESSION['id_user'])) {
    $_SESSION['alert'] = [
        'msg' => 'Silakan login terlebih dahulu untuk mengakses disposisi',
        'type' => 'error'
    ];
    header("Location: login.php");
    exit;
}

$my_id = $_SESSION['id_user'];

// STRICT RULE: Manual Disposisi di SuratQu NON-AKTIFkan.
// User diarahkan untuk menggunakan aplikasi Camat (SidikSae) atau Auto-Disposisi.
if ($id_sm) {
    // Jika ada yang mencoba akses manual input, redirect ke detail atau tampilkan pesan
    $_SESSION['alert'] = [
        'msg' => 'Fitur Input Disposisi Manual dinonaktifkan. Gunakan Auto-Disposisi (Agendakan) atau Aplikasi Pimpinan.',
        'type' => 'warning'
    ];
    header("Location: surat_masuk_detail.php?id=$id_sm");
    exit;
} else {

    // MODE 2: INBOX DISPOSISI (Daftar Disposisi Masuk)
    
    // Fetch Disposisi Masuk
    $stmt = $db->prepare("SELECT d.*, 
                                 sm.asal_surat, sm.no_surat, sm.perihal, sm.tgl_surat, sm.scan_surat,
                                 u.nama_lengkap as pengirim_nama, j.nama_jabatan as pengirim_jabatan
                          FROM disposisi d
                          JOIN surat_masuk sm ON d.id_sm = sm.id_sm
                          JOIN users u ON d.pengirim_id = u.id_user
                          JOIN jabatan j ON u.id_jabatan = j.id_jabatan
                          WHERE d.penerima_id = ?
                          ORDER BY d.tgl_disposisi DESC");
    $stmt->execute([$my_id]);
    $disposisi = $stmt->fetchAll();
    
    include 'includes/header.php';
    ?>
    <div class="mb-4 pt-2">
        <h2 class="fw-bold mb-1">Disposisi Masuk</h2>
        <p class="text-muted small">Daftar tugas disposisi yang masuk ke akun Anda.</p>
    </div>

    <div class="card card-custom p-0 overflow-hidden border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Dari / Waktu</th>
                        <th>Detail Surat</th>
                        <th>Instruksi</th>
                        <th>Status</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($disposisi)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <img src="images/illustration-empty.svg" alt="" style="width: 100px; opacity: 0.5;" class="mb-3 d-none">
                            <p class="text-muted fw-bold">Belum ada disposisi masuk.</p>
                        </td>
                    </tr>
                    <?php else: foreach ($disposisi as $row): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['pengirim_nama']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($row['pengirim_jabatan']) ?></div>
                            <small class="text-success" style="font-size: 0.7rem;">
                                <i class="fa-regular fa-clock me-1"></i> <?= time_since($row['tgl_disposisi']) ?>
                            </small>
                        </td>
                        <td style="max-width: 300px;">
                            <div class="fw-medium text-dark text-truncate" title="<?= htmlspecialchars($row['asal_surat']) ?>"><?= htmlspecialchars($row['asal_surat']) ?></div>
                            <div class="small text-secondary text-truncate" title="<?= htmlspecialchars($row['perihal']) ?>"><?= htmlspecialchars($row['perihal']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($row['no_surat']) ?></small>
                        </td>
                        <td style="max-width: 250px;">
                            <div class="small fst-italic text-truncate">"<?= htmlspecialchars($row['instruksi']) ?>"</div>
                            <?php if ($row['batas_waktu']): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1" style="font-size: 0.65rem;">
                                    Deadline: <?= date('d/m/Y', strtotime($row['batas_waktu'])) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Status Baca -->
                            <?php if ($row['status_baca'] == 'belum'): ?>
                                <span class="badge bg-danger rounded-pill mb-1">Belum Dibaca</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill mb-1">Sudah Dibaca</span>
                            <?php endif; ?>
                            
                            <!-- Status Pengerjaan -->
                             <br>
                            <?php if ($row['status_pengerjaan'] == 'selesai'): ?>
                                <span class="badge bg-primary text-white rounded-pill">Selesai</span>
                            <?php elseif ($row['status_pengerjaan'] == 'proses'): ?>
                                <span class="badge bg-warning text-dark rounded-pill">Proses</span>
                            <?php else: ?>
                                <span class="badge bg-secondary rounded-pill">Menunggu</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center pe-4">
                            <a href="surat_masuk_detail.php?id=<?= $row['id_sm'] ?>" class="btn btn-sm btn-primary shadow-sm px-3 fw-bold">
                                <i class="fa-solid fa-eye me-1"></i> Lihat
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>

<?php include 'includes/footer.php'; ?>

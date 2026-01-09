<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// Only users with can_verifikasi permission can access
if (!($_SESSION['can_verifikasi'] == 1 || $_SESSION['role'] == 'admin')) {
    redirect('index.php', 'Anda tidak memiliki hak akses verifikasi.', 'danger');
}

$title = 'Verifikasi Surat Keluar';
include 'includes/header.php';

// Fetch letters waiting for verification
$query = "SELECT sk.*, u.nama_lengkap as pembuat, j.nama_jabatan 
          FROM surat_keluar sk 
          LEFT JOIN users u ON sk.id_user_pembuat = u.id_user 
          LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan
          WHERE sk.status = 'verifikasi'
          ORDER BY sk.created_at ASC";
$stmt = $db->query($query);
$waiting_list = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold mb-1">Verifikasi Surat Keluar</h4>
            <p class="text-muted small">Daftar draf surat yang memerlukan persetujuan pimpinan.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['alert'])): ?>
        <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <?= $_SESSION['alert']['msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <div class="card card-custom border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase" style="font-size: 0.75rem;">
                    <tr>
                        <th class="ps-4 py-3">Pengerjaan</th>
                        <th>Perihal & Tujuan</th>
                        <th>Tanggal Pengajuan</th>
                        <th class="text-center">Pratinjau</th>
                        <th class="text-end pe-4">Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($waiting_list) > 0): ?>
                        <?php foreach ($waiting_list as $row): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['pembuat']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($row['nama_jabatan']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($row['perihal']) ?></div>
                                    <div class="small text-muted">Kepada: <?= htmlspecialchars($row['tujuan']) ?></div>
                                </td>
                                <td>
                                    <div class="small"><i class="fa-regular fa-clock me-1 text-primary"></i> <?= time_since($row['created_at']) ?></div>
                                </td>
                                <td class="text-center">
                                    <a href="surat_keluar_preview.php?id=<?= $row['id_sk'] ?>" target="_blank" class="btn btn-sm btn-light">
                                        <i class="fa-solid fa-file-invoice me-1"></i> Lihat Draf
                                    </a>
                                </td>
                                <td class="text-end pe-4">
                                    <form action="surat_keluar_proses.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id_sk" value="<?= $row['id_sk'] ?>">
                                        <button type="submit" name="approve" class="btn btn-sm btn-success px-3 shadow-sm" onclick="return confirm('Setujui surat ini? Nomor surat akan digenerate otomatis.')">
                                            <i class="fa-solid fa-check-circle me-1"></i> Setujui
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $row['id_sk'] ?>">
                                            <i class="fa-solid fa-times-circle me-1"></i> Tolak
                                        </button>
                                    </form>

                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal<?= $row['id_sk'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                                <form action="surat_keluar_proses.php" method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Tolak & Koreksi</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_sk" value="<?= $row['id_sk'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Instruksi Perbaikan / Alasan Penolakan</label>
                                                            <textarea name="catatan" class="form-control" rows="4" placeholder="Contoh: Perbaiki format tanggal atau lampiran kurang lengkap..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="reject" class="btn btn-danger">Kirim Kembali ke Staf</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fa-solid fa-clipboard-check fa-4x text-light mb-3"></i>
                                <p class="text-muted">Tidak ada surat yang menunggu verifikasi saat ini.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

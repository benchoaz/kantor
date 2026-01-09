<?php
// surat_keluar.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

include 'includes/header.php';

// Fetch Surat Keluar
$query = "SELECT sk.*, u.nama_lengkap as pembuat 
          FROM surat_keluar sk 
          LEFT JOIN users u ON sk.id_user_pembuat = u.id_user 
          ORDER BY sk.created_at DESC";
$stmt = $db->query($query);
$surat_keluar = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-2">
    <div>
        <h2 class="fw-bold mb-1">Surat Keluar</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Surat Keluar</li>
            </ol>
        </nav>
    </div>
    <a href="surat_keluar_tambah.php" class="btn btn-primary">
        <i class="fa-solid fa-pen-nib me-2"></i> Buat Surat Baru
    </a>
</div>

<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">No. Surat</th>
                    <th>Tujuan</th>
                    <th>Perihal</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($surat_keluar)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">Belum ada data surat keluar.</td>
                </tr>
                <?php else: foreach ($surat_keluar as $row): ?>
                <tr>
                    <td class="ps-4 fw-bold"><?= $row['no_surat'] ? htmlspecialchars($row['no_surat']) : '<em class="text-muted small">Menunggu No.</em>' ?></td>
                    <td><?= htmlspecialchars($row['tujuan']) ?></td>
                    <td><?= htmlspecialchars($row['perihal']) ?></td>
                    <td><?= format_tgl_indo($row['tgl_surat']) ?></td>
                    <td>
                        <?php
                        $status_class = [
                            'draft' => 'bg-secondary',
                            'verifikasi' => 'bg-warning text-dark',
                            'disetujui' => 'bg-info',
                            'terkirim' => 'bg-success'
                        ];
                        ?>
                        <span class="badge <?= $status_class[$row['status']] ?> rounded-pill px-3">
                            <?= ucfirst($row['status']) ?>
                        </span>
                        <?php if ($row['status'] == 'draft' && $row['catatan_koreksi']): ?>
                            <div class="mt-2">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 10px;" title="<?= htmlspecialchars($row['catatan_koreksi']) ?>">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i> Perlu Perbaikan
                                </span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center pe-4">
                        <div class="btn-group">
                            <a href="surat_keluar_preview.php?id=<?= $row['id_sk'] ?>" class="btn btn-sm btn-outline-secondary" title="Preview">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </a>
                            <?php if ($row['status'] == 'draft'): ?>
                            <a href="surat_keluar_edit.php?id=<?= $row['id_sk'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fa-solid fa-edit"></i>
                            </a>
                            <a href="surat_keluar_proses.php?action=request_verif&id=<?= $row['id_sk'] ?>" 
                               class="btn btn-sm btn-outline-warning" 
                               onclick="return confirm('Ajukan verifikasi ke pimpinan?')" title="Ajukan Verifikasi">
                                <i class="fa-solid fa-paper-plane"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($row['status'] == 'disetujui'): ?>
                            <a href="surat_keluar_preview.php?id=<?= $row['id_sk'] ?>&print=true" class="btn btn-sm btn-outline-success" target="_blank" title="Cetak Surat">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

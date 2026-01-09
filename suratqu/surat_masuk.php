<?php
// surat_masuk.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

include 'includes/header.php';

// Fetch Surat Masuk
$query = "SELECT * FROM surat_masuk ORDER BY tgl_diterima DESC";
$stmt = $db->query($query);
$surat_masuk = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-2">
    <div>
        <h2 class="fw-bold mb-1">Surat Masuk</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Surat Masuk</li>
            </ol>
        </nav>
    </div>
    <a href="surat_masuk_tambah.php" class="btn btn-primary">
        <i class="fa-solid fa-plus me-2"></i> Tambah Surat
    </a>
</div>

<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">No. Agenda</th>
                    <th>Asal & No. Surat</th>
                    <th>Perihal</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($surat_masuk)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">Belum ada data surat masuk.</td>
                </tr>
                <?php else: foreach ($surat_masuk as $row): ?>
                <tr>
                    <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($row['no_agenda']) ?></td>
                    <td>
                        <div class="fw-medium"><?= htmlspecialchars($row['asal_surat']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($row['no_surat']) ?></small>
                    </td>
                    <td>
                        <div class="fw-medium text-dark"><?= htmlspecialchars($row['perihal']) ?></div>
                        <small class="text-muted">Kepada: <?= htmlspecialchars($row['tujuan'] ?: '-') ?></small>
                    </td>
                    <td><?= format_tgl_indo($row['tgl_surat']) ?></td>
                    <td>
                        <?php
                        $status_class = [
                            'draft' => 'bg-secondary',
                            'valid' => 'bg-info',
                            'teragenda' => 'bg-primary',
                            'terdaftar' => 'bg-info',
                            'disposisi_dibuat' => 'bg-success',
                            'baru' => 'bg-secondary', // Legacy
                            'disposisi' => 'bg-warning', // Legacy
                            'proses' => 'bg-primary', // Legacy
                            'selesai' => 'bg-success' // Legacy
                        ];
                        // Fallback color
                        $badge_color = $status_class[$row['status']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $badge_color ?> rounded-pill px-3">
                            <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                        </span>
                    </td>
                    <td class="text-center pe-4">
                        <div class="btn-group">
                            <a href="surat_masuk_detail.php?id=<?= $row['id_sm'] ?>" class="btn btn-sm btn-outline-secondary" title="Detail / Monitoring">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <!-- Manual Disposisi Disabled for Operator (Strict Flow) -->
                            <!-- <a href="disposisi.php?id_sm=<?= $row['id_sm'] ?>" class="btn btn-sm btn-outline-primary" title="Disposisi"><i class="fa-solid fa-share-nodes"></i></a> -->
                            <?php if ($row['file_path']): ?>
                            <a href="<?= htmlspecialchars($row['file_path']) ?>" class="btn btn-sm btn-outline-danger" target="_blank" title="Buka PDF">
                                <i class="fa-solid fa-file-pdf"></i>
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

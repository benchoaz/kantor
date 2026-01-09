<?php
// modules/disposisi/detail.php
$page_title = 'Detail Disposisi';
$active_page = 'disposisi';

require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/notification_helper.php';

$id = $_GET['id'] ?? 0;
$userId = $_SESSION['user_id'];

// Fetch Detail
$id = $_GET['id'] ?? 0;
$userId = $_SESSION['user_id'];

// Fetch Detail
$stmt = $pdo->prepare("
    SELECT d.*, dp.id as penerima_id, dp.status as status_penerima, dp.tgl_dibaca, dp.tgl_dilaksanakan, dp.kegiatan_id
    FROM disposisi d
    JOIN disposisi_penerima dp ON d.id = dp.disposisi_id
    WHERE d.id = ? AND dp.user_id = ?
");
$stmt->execute([$id, $userId]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "<div class='alert alert-danger'>Disposisi tidak ditemukan atau Anda tidak memiliki akses.</div>";
    require_once '../../includes/footer.php';
    exit;
}

// Auto-update status to "dibaca" if currently "baru"
if ($data['status_penerima'] === 'baru') {
    markDispositionAsRead($pdo, $data['penerima_id'], $userId);
    // Refresh data to show updated status
    $data['status_penerima'] = 'dibaca';
    $data['tgl_dibaca'] = date('Y-m-d H:i:s');
}

// status badge helper
$statusBadge = '';
if ($data['status_penerima'] === 'baru') $statusBadge = '<span class="badge bg-danger">Baru</span>';
elseif ($data['status_penerima'] === 'dibaca') $statusBadge = '<span class="badge bg-warning text-dark">Dibaca</span>';
elseif ($data['status_penerima'] === 'dilaksanakan') $statusBadge = '<span class="badge bg-success">Dilaksanakan</span>';

?>

<div class="row fade-in justify-content-center">
    <div class="col-md-8">
        <!-- Back Button -->
        <a href="index.php" class="text-decoration-none text-muted mb-3 d-inline-block">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>

        <div class="card card-modern border-0 shadow-lg">
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="text-muted small fw-bold mb-1">DISPOSISI #<?= htmlspecialchars($data['external_id']) ?></h5>
                        <h2 class="fw-bold text-primary mb-0"><?= htmlspecialchars($data['perihal']) ?></h2>
                    </div>
                    <?php echo $statusBadge; ?>
                </div>
            </div>
            
            <div class="card-body p-4">
                <!-- Metadata -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <small class="text-muted d-block fw-bold">DITERIMA DARI</small>
                        <span class="fs-5">SuratQu (Sistem Eksternal)</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold">TANGGAL DISPOSISI</small>
                        <span class="fs-5"><?= date('d F Y H:i', strtotime($data['tgl_disposisi'])) ?></span>
                    </div>
                </div>

                <!-- Instruction Box -->
                <div class="bg-light p-4 rounded-3 border-start border-4 border-primary mb-4">
                    <small class="text-muted fw-bold mb-2 d-block">INSTRUKSI PIMPINAN:</small>
                    <p class="fs-5 mb-0" style="white-space: pre-line;"><?= htmlspecialchars($data['instruksi']) ?></p>
                </div>

                <!-- Action Section -->
                <?php if ($data['status_penerima'] !== 'dilaksanakan'): ?>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Tindak Lanjut Diperlukan</strong>
                            <div class="small">Silakan laksanakan tugas ini dan dokumentasikan hasilnya melalui BESUK SAE untuk verifikasi.</div>
                        </div>
                    </div>

                    <div class="d-grid gap-3">
                        <a href="../../kegiatan_tambah.php?disposisi_id=<?= $data['id'] ?>&title=<?= urlencode($data['perihal']) ?>" class="btn btn-primary btn-lg shadow-sm">
                            <i class="bi bi-camera-fill me-2"></i> LAKSANAKAN & DOKUMENTASIKAN
                        </a>
                    </div>
                <?php else: ?>
                    <!-- If already executed, show evidence link -->
                    <div class="card bg-success bg-opacity-10 border-success mb-3">
                        <div class="card-body text-center text-success">
                            <i class="bi bi-check-circle-fill fs-1 mb-2"></i>
                            <h4 class="fw-bold">Telah Dilaksanakan</h4>
                            <p class="mb-3">Disposisi ini telah diselesaikan pada <?= date('d M Y H:i', strtotime($data['tgl_dilaksanakan'])) ?>.</p>
                            
                            <?php if ($data['kegiatan_id']): ?>
                                <a href="../../kegiatan_detail.php?id=<?= $data['kegiatan_id'] ?>" class="btn btn-success">
                                    Lihat Bukti Dokumentasi
                                </a>
                            <?php else: ?>
                                <span class="text-muted fw-bold">Bukti terlampir di e-Kinerja</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
            
            <!-- Audit Trail (Simple) -->
            <div class="card-footer bg-light p-3 small text-muted">
                <i class="bi bi-shield-lock me-1"></i> Data Integrity Hash: <code><?= substr($data['payload_hash'], 0, 16) ?>...</code>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

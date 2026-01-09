<?php
// integrasi_sistem.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/integrasi_sistem_handler.php';

require_auth();

// Only admin can access
if ($_SESSION['role'] !== 'admin') {
    redirect('index.php', 'Akses ditolak!', 'danger');
}

$config = require 'config/integration.php';

// Handle Action (Retry)
if (isset($_GET['retry'])) {
    $log_id = (int)$_GET['retry'];
    $stmt = $db->prepare("SELECT disposisi_id FROM integrasi_docku_log WHERE id = ?");
    $stmt->execute([$log_id]);
    $disposisi_id = $stmt->fetchColumn();

    if ($disposisi_id) {
        $result = pushDisposisiToSidikSae($db, $disposisi_id);
        if ($result && $result['success']) {
            redirect('integrasi_sistem.php', 'Disposisi berhasil dikirim ulang ke Sistem Terpusat.', 'success');
        } else {
            $error_msg = $result['error'] ?? $result['message'] ?? 'Unknown error';
            redirect('integrasi_sistem.php', 'Gagal kirim ulang: ' . $error_msg, 'danger');
        }
    }
}

// Fetch Logs
$stmt = $db->query("SELECT l.*, d.instruksi, sm.no_agenda, u.nama_lengkap as penerima 
                    FROM integrasi_docku_log l
                    JOIN disposisi d ON l.disposisi_id = d.id_disposisi
                    JOIN surat_masuk sm ON d.id_sm = sm.id_sm
                    JOIN users u ON d.penerima_id = u.id_user
                    ORDER BY l.created_at DESC");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$total_sent = count($logs);
$success_count = count(array_filter($logs, fn($l) => $l['status'] == 'success'));
$failed_count = count(array_filter($logs, fn($l) => $l['status'] == 'failed'));
$pending_count = count(array_filter($logs, fn($l) => $l['status'] == 'pending'));
$success_rate = $total_sent > 0 ? round(($success_count / $total_sent) * 100) : 0;

include 'includes/header.php';
?>

<div class="mb-4 pt-2 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold mb-1">Monitoring Integrasi Sistem</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Integrasi Sistem</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="integrasi_tutorial.php" class="btn btn-light shadow-sm btn-sm px-3 me-1">
            <i class="fa-solid fa-book me-1"></i> Panduan
        </a>
        <a href="integrasi_pengaturan.php" class="btn btn-light shadow-sm btn-sm px-3 me-2">
            <i class="fa-solid fa-gear me-1"></i> Pengaturan
        </a>
        <span class="badge <?= $config['sidiksae']['enabled'] ? 'bg-success' : 'bg-secondary' ?> p-2 px-3">
            <i class="fa-solid fa-circle-check me-1"></i>
            Status: <?= $config['sidiksae']['enabled'] ? 'Aktif' : 'Non-Aktif' ?>
        </span>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card card-custom border-0 shadow-sm p-3 text-center bg-white">
            <div class="small text-muted mb-1">Total Pengiriman</div>
            <h3 class="fw-bold mb-0"><?= $total_sent ?></h3>
        </div>
    </div>
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card card-custom border-0 shadow-sm p-3 text-center bg-white border-bottom border-4 border-success">
            <div class="small text-muted mb-1">Berhasil</div>
            <h3 class="fw-bold mb-0 text-success"><?= $success_count ?></h3>
        </div>
    </div>
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card card-custom border-0 shadow-sm p-3 text-center bg-white border-bottom border-4 border-danger">
            <div class="small text-muted mb-1">Gagal</div>
            <h3 class="fw-bold mb-0 text-danger"><?= $failed_count ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom border-0 shadow-sm p-3 text-center bg-white border-bottom border-4 border-primary">
            <div class="small text-muted mb-1">Rasio Sukses</div>
            <h3 class="fw-bold mb-0 text-primary"><?= $success_rate ?>%</h3>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card card-custom border-0 shadow-sm p-4 text-white" style="background: linear-gradient(45deg, #34C759, #30D158) !important;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-cloud me-2 text-warning"></i> Integrasi Sistem Terpusat</h5>
                    <p class="mb-0 opacity-75 small italic">Disposisi otomatis dikirim ke SidikSae API untuk sinkronisasi dengan aplikasi lain.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="bg-white bg-opacity-10 border border-white border-opacity-25 text-white p-2 px-3 rounded-pill small d-inline-block">
                        <i class="fa-solid fa-server me-1"></i> <?= htmlspecialchars(parse_url($config['sidiksae']['base_url'], PHP_URL_HOST) ?: 'Belum diatur') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white border-bottom p-4">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i> Log Pengiriman Disposisi</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Waktu</th>
                    <th>No. Agenda / Penerima</th>
                    <th>Status</th>
                    <th>HTTP</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">Belum ada data integrasi yang tercatat.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold small"><?= date('d/m/Y', strtotime($log['created_at'])) ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                    </td>
                    <td>
                        <div class="fw-bold"><?= htmlspecialchars($log['no_agenda']) ?></div>
                        <div class="small text-muted">Ke: <?= htmlspecialchars($log['penerima']) ?></div>
                    </td>
                    <td>
                        <?php if ($log['status'] == 'success'): ?>
                            <span class="badge bg-success-subtle text-success px-2 py-1"><i class="fa-solid fa-check-circle me-1"></i> Success</span>
                        <?php elseif ($log['status'] == 'failed'): ?>
                            <span class="badge bg-danger-subtle text-danger px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i> Failed</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning px-2 py-1"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code class="small fw-bold <?= $log['response_code'] == 200 ? 'text-success' : 'text-danger' ?>">
                            <?= $log['response_code'] ?: '-' ?>
                        </code>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-light border-0 shadow-sm me-1" 
                                onclick="viewPayload('<?= $log['id'] ?>')" 
                                title="Lihat Payload">
                            <i class="fa-solid fa-code text-primary"></i>
                        </button>
                        <?php if ($log['status'] != 'success'): ?>
                        <a href="?retry=<?= $log['id'] ?>" class="btn btn-sm btn-primary shadow-sm" title="Kirim Ulang">
                            <i class="fa-solid fa-rotate"></i> Retry
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <!-- Payload Modal Content (Hidden) -->
                <div id="payload-<?= $log['id'] ?>" class="d-none">
                    <h6>Payload JSON</h6>
                    <pre class="bg-dark text-info p-3 rounded small" style="max-height: 300px; overflow-y: auto;"><?= $log['payload'] ? htmlspecialchars(json_encode(json_decode($log['payload']), JSON_PRETTY_PRINT)) : 'Data tidak tersedia' ?></pre>
                    <h6>Response Body</h6>
                    <pre class="bg-light p-3 rounded small border text-dark" style="max-height: 200px; overflow-y: auto;"><?= htmlspecialchars($log['response_body'] ?: 'Tidak ada respons') ?></pre>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4">
                <h5 class="modal-title fw-bold">Detail Integrasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-0" id="modalContent">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>
</div>

<script>
function viewPayload(id) {
    const content = document.getElementById('payload-' + id).innerHTML;
    document.getElementById('modalContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('modalDetail')).show();
}
</script>

<?php include 'includes/footer.php'; ?>

<?php
// status_integrasi.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// Only admin/pimpinan should see technical logs
if ($_SESSION['role'] == 'operator') {
    redirect('index.php', 'Akses hanya untuk Administrator/Pimpinan.', 'warning');
}

$title = 'Status Integrasi API';
include 'includes/header.php';

// Fetch Integration Logs
$stmt = $db->query("SELECT l.*, 
                    COALESCE(sm.no_surat, JSON_UNQUOTE(JSON_EXTRACT(l.payload, '$.nomor_surat'))) as display_no_surat,
                    COALESCE(sm.perihal, JSON_UNQUOTE(JSON_EXTRACT(l.payload, '$.perihal'))) as display_perihal
                    FROM integrasi_docku_log l
                    LEFT JOIN disposisi d ON l.disposisi_id = d.id_disposisi
                    LEFT JOIN surat_masuk sm ON (d.id_sm = sm.id_sm OR JSON_UNQUOTE(JSON_EXTRACT(l.payload, '$.uuid_surat')) = sm.uuid)
                    ORDER BY l.created_at DESC LIMIT 50");
$logs = $stmt->fetchAll();

// Get API Config
$config = require 'config/integration.php';
$api_enabled = $config['sidiksae']['enabled'] ?? false;
$api_url = $config['sidiksae']['base_url'] ?? '-';
?>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h4 class="fw-bold mb-1">Status Integrasi API 📡</h4>
            <p class="text-muted small mb-0">Monitor pengiriman data ke SidikSae API Center (Camat Apps).</p>
        </div>
        <div class="col-md-4 text-end">
            <?php if($api_enabled): ?>
                <span class="badge bg-success"><i class="fa-solid fa-check-circle me-1"></i> Integrasi Aktif</span>
            <?php else: ?>
                <span class="badge bg-danger"><i class="fa-solid fa-ban me-1"></i> Integrasi Non-Aktif</span>
            <?php endif; ?>
            <div class="small text-muted mt-1"><?= htmlspecialchars($api_url) ?></div>
        </div>
    </div>
    
    <div class="card card-custom border-0 shadow-sm">
        <div class="card-header bg-white py-3">
             <h6 class="mb-0 fw-bold">Riwayat Transmisi Data</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Waktu</th>
                        <th>Surat Terkait</th>
                        <th>Status</th>
                        <th>Response Code</th>
                        <th>Payload / Response</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">Belum ada riwayat integrasi.</td>
                    </tr>
                    <?php else: foreach ($logs as $row): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= date('d/m H:i', strtotime($row['created_at'])) ?></div>
                            <small class="text-muted"><?= time_since($row['created_at']) ?></small>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['display_no_surat'] ?? 'Unknown') ?></div>
                            <div class="text-truncate" style="max-width: 200px;"><?= htmlspecialchars($row['display_perihal'] ?? '-') ?></div>
                            <div class="small text-muted">ID Disposisi: <?= $row['disposisi_id'] ?: 'N/A (Registrasi)' ?></div>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'success'): ?>
                                <span class="badge bg-success rounded-pill">BERHASIL</span>
                            <?php elseif ($row['status'] == 'pending'): ?>
                                <span class="badge bg-warning text-dark rounded-pill">MENUNGGU</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill">GAGAL</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $code = $row['response_code']; 
                                $badge = ($code >= 200 && $code < 300) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                            ?>
                            <span class="badge <?= $badge ?> border"><?= $code ?: '-' ?></span>
                        </td>
                        <td style="max-width: 300px;">
                            <code class="d-block text-truncate text-primary mb-1">REQ: <?= htmlspecialchars(substr($row['payload'], 0, 50)) ?>...</code>
                            <code class="d-block text-truncate text-secondary">RES: <?= htmlspecialchars(substr($row['response_body'], 0, 50)) ?>...</code>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalLog<?= $row['id'] ?>">
                                <i class="fa-solid fa-code"></i> Detail
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Modals placed outside the table structure to prevent flickering and z-index issues -->
<?php if (!empty($logs)): foreach ($logs as $row): ?>
<div class="modal fade" id="modalLog<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom-0">
                <div>
                    <h5 class="modal-title fw-bold text-primary">Detail Log Transmisi #<?= $row['id'] ?></h5>
                    <p class="mb-0 text-muted small">ID Disposisi: <?= $row['disposisi_id'] ?> &bull; <?= date('d M Y H:i', strtotime($row['created_at'])) ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="fw-bold text-dark"><i class="fa-solid fa-upload me-1 text-primary"></i> Payload Dikirim</label>
                        <span class="badge bg-primary bg-opacity-10 text-primary">Request</span>
                    </div>
                    <div class="position-relative">
                        <pre class="bg-dark text-success p-3 rounded-3 small shadow-sm position-relative" style="max-height: 250px; font-family: 'JetBrains Mono', monospace;"><?= htmlspecialchars(json_encode(json_decode($row['payload']), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                </div>
                
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="fw-bold text-dark"><i class="fa-solid fa-download me-1 text-warning"></i> Respon API</label>
                        <span class="badge <?= ($row['response_code'] >= 200 && $row['response_code'] < 300) ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>">Response: <?= $row['response_code'] ?></span>
                    </div>
                    <div class="position-relative">
                        <pre class="bg-dark text-warning p-3 rounded-3 small shadow-sm" style="max-height: 250px; font-family: 'JetBrains Mono', monospace;"><?= htmlspecialchars(json_encode(json_decode($row['response_body']), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; endif; ?>

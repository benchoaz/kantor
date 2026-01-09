<?php
// surat_masuk_detail.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: surat_masuk.php"); exit; }

include 'includes/header.php';
require_once 'includes/integrasi_sistem_handler.php';

// Fetch Surat Detail
$stmt = $db->prepare("SELECT * FROM surat_masuk WHERE id_sm = ?");
$stmt->execute([$id]);
$surat = $stmt->fetch();

if (!$surat) { echo "Surat tidak ditemukan."; exit; }

// AUTO-SYNC LOGIC (V3 Workflow)
// If surat is NOT 'selesai', check API for updates on its dispositions
if ($surat['status'] != 'selesai' && $surat['status'] != 'draft') {
    // Find active dispositions (sent to API)
    $stmt_d = $db->prepare("SELECT id_disposisi FROM disposisi WHERE id_sm = ? AND status_pengerjaan != 'selesai'");
    $stmt_d->execute([$id]);
    $disposisi_list = $stmt_d->fetchAll();
    
    $updated = false;
    foreach ($disposisi_list as $d) {
        if (function_exists('syncDispositionStatus')) {
            $res = syncDispositionStatus($db, $d['id_disposisi']);
            if ($res && isset($res['synced']) && $res['synced']) {
                $updated = true;
            }
        }
    }
    
    // If updated, refresh Surat data
    if ($updated) {
        $stmt->execute([$id]);
        $surat = $stmt->fetch();
        echo "<script>window.location.reload();</script>"; // Force reload to refresh UI fully
        exit;
    }
}

// Fetch Riwayat Disposisi
$stmt = $db->prepare("SELECT d.*, u1.nama_lengkap as pengirim, u2.nama_lengkap as penerima 
                      FROM disposisi d
                      JOIN users u1 ON d.pengirim_id = u1.id_user
                      JOIN users u2 ON d.penerima_id = u2.id_user
                      WHERE d.id_sm = ?
                      ORDER BY d.tgl_disposisi DESC");
$stmt->execute([$id]);
$riwayat = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-2">
    <div>
        <h2 class="fw-bold mb-1">Detail Surat Masuk</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="surat_masuk.php">Surat Masuk</a></li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </nav>
    </div>
    <div class="btn-group">
        <!-- Disposisi Button Removed as per Centralized Workflow -->
        <a href="cetak_disposisi.php?id=<?= $surat['id_sm'] ?>" target="_blank" class="btn btn-outline-dark me-1">
            <i class="fa-solid fa-print"></i> Lembar Disposisi
        </a>
        <a href="surat_masuk_edit.php?id=<?= $surat['id_sm'] ?>" class="btn btn-outline-secondary">
            <i class="fa-solid fa-pen-to-square"></i>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <!-- Info Surat -->
        <div class="card card-custom p-4 border-0 shadow-sm mb-4">
            <h5 class="fw-bold mb-4 border-bottom pb-2 text-primary">Informasi Surat</h5>
            <div class="row g-3">
                <div class="col-sm-4 fw-bold">No. Agenda</div>
                <div class="col-sm-8 text-primary fw-bold">: <?= htmlspecialchars($surat['no_agenda']) ?></div>
                
                <div class="col-sm-4 fw-bold">Asal Surat</div>
                <div class="col-sm-8">: <?= htmlspecialchars($surat['asal_surat']) ?></div>
                
                <div class="col-sm-4 fw-bold">Nomor Surat</div>
                <div class="col-sm-8">: <?= htmlspecialchars($surat['no_surat']) ?></div>
                
                <div class="col-sm-4 fw-bold">Perihal</div>
                <div class="col-sm-8">: <?= htmlspecialchars($surat['perihal']) ?></div>
                
                <div class="col-sm-4 fw-bold">Tujuan Surat</div>
                <div class="col-sm-8 text-primary">: <?= htmlspecialchars($surat['tujuan'] ?: '-') ?></div>
                
                <div class="col-sm-4 fw-bold">Tanggal Surat</div>
                <div class="col-sm-8">: <?= format_tgl_indo($surat['tgl_surat']) ?></div>
                
                <div class="col-sm-4 fw-bold">Status</div>
                <div class="col-sm-8">: 
                    <?php
                    $status_colors = [
                        'draft' => 'bg-secondary',
                        'valid' => 'bg-info',
                        'teragenda' => 'bg-primary',
                        'disposisi_dibuat' => 'bg-success',
                        'baru' => 'bg-secondary',
                        'disposisi' => 'bg-warning', 
                        'proses' => 'bg-primary', 
                        'selesai' => 'bg-success'
                    ];
                    $badge_class = $status_colors[$surat['status']] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?= $badge_class ?> rounded-pill"><?= ucfirst(str_replace('_', ' ', $surat['status'])) ?></span>
                </div>
            </div>
            
            <?php if ($surat['file_path']): ?>
            <div class="mt-4 pt-4 border-top">
                <!-- STATUS CHECK (END-TO-END) -->
                <?php 
                    // Check Status API (Strict Flow)
                    // Domain Production
                    $api_check_url = 'https://api.sidiksae.my.id/api/disposisi/check/' . $surat['uuid'];
                    
                    // Simple CURL to get status
                    $ch = curl_init($api_check_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-KEY: sk_test_123']); // Key
                    $res_json = curl_exec($ch);
                    curl_close($ch);
                    
                    $api_status = json_decode($res_json, true);
                    $final_status = $api_status['status'] ?? 'UNKNOWN';
                ?>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Berkas Digital & Status</h6>
                    
                    <?php if ($final_status === 'SELESAI'): ?>
                         <span class="badge bg-success text-white">
                            <i class="fa-solid fa-circle-check me-1"></i> DISPOSISI SELESAI
                        </span>
                    <?php elseif ($final_status === 'PROSES'): ?>
                         <span class="badge bg-primary text-white">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i> SEDANG DIPROSES
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary text-white">
                            <i class="fa-solid fa-clock me-1"></i> BELUM DISPOSISI
                        </span>
                    <?php endif; ?>
                </div>

                <!-- ALERT LAPORAN -->
                <?php if (!empty($api_status['laporan_gabungan'])): ?>
                <div class="alert alert-success border-success bg-success-subtle mb-3">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-clipboard-check me-1"></i> Laporan Tindak Lanjut:</h6>
                    <ul class="mb-0 ps-3">
                        <?php foreach($api_status['laporan_gabungan'] as $lap): ?>
                        <li>
                            <strong><?= htmlspecialchars($lap['penerima']) ?>:</strong> 
                            <?= htmlspecialchars($lap['laporan']) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="bg-light p-3 rounded-4 d-flex align-items-center justify-content-between border border-dashed border-secondary-subtle">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger-subtle p-2 rounded-3 me-3">
                            <i class="fa-solid fa-file-pdf text-danger fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold small">Berkas Digital Surat</div>
                            <div class="text-muted" style="font-size: 0.7rem;">Format PDF</div>
                        </div>
                    </div>
                    <a href="<?= $surat['file_path'] ?>" target="_blank" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm">
                        <i class="fa-solid fa-eye me-1"></i> Lihat PDF
                    </a>
                </div>
                <p class="small text-muted mt-2 mb-0 italic" style="font-size: 0.7rem;">
                    <i class="fa-solid fa-cloud me-1"></i> Berkas ini otomatis dikirim ke Pimpinan untuk ditelaah.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-5">
        <!-- Riwayat Disposisi -->
        <div class="card card-custom p-4 border-0 shadow-sm bg-white">
            <h5 class="fw-bold mb-4 border-bottom pb-2 text-warning">Riwayat Disposisi</h5>
            <div class="timeline">
                <?php if (empty($riwayat)): ?>
                    <p class="text-muted small text-center italic">Belum ada disposisi untuk surat ini.</p>
                <?php else: foreach ($riwayat as $d): ?>
                    <?php 
                        // Auto-Update Read Status Logic (Jika saya penerima & belum baca)
                        if ($d['penerima_id'] == $_SESSION['id_user'] && $d['status_baca'] == 'belum') {
                            $db->query("UPDATE disposisi SET status_baca='sudah', tanggal_baca=NOW() WHERE id_disposisi={$d['id_disposisi']}");
                            $d['status_baca'] = 'sudah'; // Update local var for display
                        }
                    ?>
                    <div class="mb-4 ps-3 border-start border-2 border-primary position-relative">
                        <div class="position-absolute start-0 top-0 translate-middle-x bg-primary rounded-circle" style="width:10px; height:10px; margin-left:-1px;"></div>
                        
                        <!-- Header & API Status (Integration Status) -->
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="fw-bold small"><?= htmlspecialchars($d['pengirim']) ?> → <?= htmlspecialchars($d['penerima']) ?></div>
                            <div class="text-end">
                                <!-- Status Realtime API -->
                                <?php 
                                    // Fetch current integration status
                                    $stmt_log = $db->prepare("SELECT status, response_code FROM integrasi_docku_log WHERE disposisi_id = ? ORDER BY id DESC LIMIT 1");
                                    $stmt_log->execute([$d['id_disposisi']]);
                                    $log = $stmt_log->fetch();
                                    
                                    if (!$log): ?>
                                        <span class="badge bg-light text-muted border mb-1" style="font-size: 0.6rem;"><i class="fa-solid fa-paper-plane"></i> Dikirim</span>
                                        <a href="retry_push.php?id=<?= $d['id_disposisi'] ?>&sm_id=<?= $surat['id_sm'] ?>" class="btn btn-xs btn-link p-0 ms-1" title="Push Ulang"><i class="fa-solid fa-sync fa-xs"></i></a>
                                    <?php elseif ($log['status'] == 'success'): ?>
                                        <?php if ($d['status_pengerjaan'] == 'selesai'): ?>
                                            <span class="badge bg-success text-white mb-1" style="font-size: 0.6rem;"><i class="fa-solid fa-circle-check"></i> Selesai</span>
                                        <?php elseif ($d['status_pengerjaan'] == 'proses'): ?>
                                            <span class="badge bg-info text-dark mb-1" style="font-size: 0.6rem;"><i class="fa-solid fa-share-nodes"></i> Diteruskan</span>
                                        <?php elseif ($d['status_baca'] == 'sudah'): ?>
                                            <span class="badge bg-primary text-white mb-1" style="font-size: 0.6rem;"><i class="fa-solid fa-envelope-open"></i> Dibaca Camat</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success mb-1" style="font-size: 0.6rem;"><i class="fa-solid fa-cloud-check"></i> Diterima API</span>
                                        <?php endif; ?>
                                    <?php else: // Failed status ?>
                                        <?php
                                            // Fetch error details
                                            $error_detail = null;
                                            $full_log = null;
                                            if (isset($log['response_body'])) {
                                                $error_json = json_decode($log['response_body'], true);
                                                $error_detail = $error_json['error'] ?? $error_json['message'] ?? null;
                                                $full_log = $error_json;
                                            }
                                            
                                            // Check if it's a file-related error
                                            $is_file_error = false;
                                            $error_msg_lower = strtolower($error_detail ?? '');
                                            if (stripos($error_msg_lower, 'file') !== false || 
                                                stripos($error_msg_lower, 'scan') !== false ||
                                                stripos($error_msg_lower, 'upload') !== false ||
                                                $log['status'] == 'validation_failed') {
                                                $is_file_error = true;
                                            }
                                            
                                            // Get specific error message
                                            $badge_text = $is_file_error ? 'Gagal Upload File' : 'Gagal Kirim';
                                            $tooltip_text = htmlspecialchars($error_detail ?? 'Gagal mengirim ke API');
                                        ?>
                                        <span class="badge <?= $is_file_error ? 'bg-warning-subtle text-warning border border-warning' : 'bg-danger-subtle text-danger border border-danger' ?> mb-1" 
                                              style="font-size: 0.6rem;" 
                                              data-bs-toggle="tooltip" 
                                              title="<?= $tooltip_text ?>">
                                            <i class="fa-solid fa-triangle-exclamation"></i> <?= $badge_text ?>
                                        </span>
                                        <a href="retry_push.php?id=<?= $d['id_disposisi'] ?>&sm_id=<?= $surat['id_sm'] ?>" 
                                           class="btn btn-xs btn-link p-0 ms-1 text-danger" 
                                           title="<?= $is_file_error ? 'Coba upload ulang' : 'Coba kirim ulang' ?>">
                                            <i class="fa-solid fa-rotate-right fa-xs"></i>
                                        </a>
                                        <?php if ($error_detail): ?>
                                            <button type="button" 
                                                    class="btn btn-xs btn-link p-0 ms-1 text-muted" 
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-html="true"
                                                    title="<strong>Detail Error:</strong><br><?= $tooltip_text ?>">
                                                <i class="fa-solid fa-circle-info fa-xs"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                            </div>
                        </div>

                        <!-- Instruksi -->
                        <div class="bg-light p-2 rounded mt-1 small">
                            <strong>Instruksi:</strong> <?= htmlspecialchars($d['instruksi']) ?>
                        </div>

                        <!-- Result Section (Jika Selesai) -->
                        <?php if ($d['status_pengerjaan'] == 'selesai'): ?>
                            <div class="mt-2 p-2 border rounded bg-success-subtle border-success small">
                                <div class="fw-bold text-success mb-1"><i class="fa-solid fa-check-circle me-1"></i> Laporan Selesai (E-Disposisi)</div>
                                <div class="mb-1 text-muted"><?= htmlspecialchars($d['catatan_hasil'] ?? '-') ?></div>
                                <?php if (!empty($d['file_hasil'])): ?>
                                    <a href="<?= $d['file_hasil'] ?>" target="_blank" class="btn btn-xs btn-outline-success mt-1">
                                        <i class="fa-solid fa-paperclip me-1"></i> Lihat Lampiran Laporan
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($d['status_pengerjaan'] == 'proses'): ?>
                             <div class="mt-2 p-2 border rounded bg-info-subtle border-info small text-info">
                                <i class="fa-solid fa-share-nodes me-1"></i> Disposisi telah diteruskan ke Pejabat Terkait oleh Pimpinan.
                             </div>
                        <?php elseif ($d['status_baca'] == 'sudah'): ?>
                             <div class="mt-2 text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary">
                                    <i class="fa-solid fa-envelope-open me-1"></i> Sedang dalam telaah Pimpinan
                                </span>
                             </div>
                        <?php else: // Dikirim/Diterima API ?>
                             <div class="mt-2 text-center">
                                <span class="badge bg-light text-secondary border">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Menunggu Pimpinan membuka surat
                                </span>
                             </div>
                        <?php endif; ?>
                        <small class="text-muted mt-1 d-block" style="font-size: 10px;">
                            <i class="fa-regular fa-clock me-1"></i> <?= date('d/m/Y H:i', strtotime($d['tgl_disposisi'])) ?>
                        </small>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

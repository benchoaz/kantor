<?php
// kegiatan_detail.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_login();

// Handle Status Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_status'])) {
    $act = $_POST['action_status'];
    $k_id = $_POST['kegiatan_id'];
    $note = $_POST['catatan_revisi'] ?? null;
    $curr_status = $_POST['current_status'];

    // Security check: ensure user has right role
    $is_admin = has_role(['admin', 'pimpinan', 'operator']);
    $is_owner = ($_SESSION['user_id'] == $_POST['created_by']);

    try {
        $new_status = null;
        $msg_notify = "";

        // 1. Send (Staff)
        if ($act === 'send' && ($is_owner || $is_admin) && in_array($curr_status, ['draft', 'revision'])) {
            $new_status = 'pending';
            $msg_notify = "📝 <b>Laporan Dikirim:</b> ";
        }
        // 2. Verify (Admin)
        elseif ($act === 'verify' && $is_admin && $curr_status === 'pending') {
            $new_status = 'verified';
            $msg_notify = "✅ <b>Laporan Diverifikasi:</b> ";
        }
        // 3. Revise (Admin)
        elseif ($act === 'revise' && $is_admin && $curr_status === 'pending') {
            $new_status = 'revision';
            $msg_notify = "⚠️ <b>Revisi Diminta:</b> ";
        }

        if ($new_status) {
            $sql = "UPDATE kegiatan SET status = ?, catatan_revisi = ?, verified_by = ?, verified_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $verifier = ($new_status === 'verified') ? $_SESSION['user_id'] : null;
            $stmt->execute([$new_status, $note, $verifier, $k_id]);

            // Fetch info for notification
            $stmt = $pdo->prepare("SELECT judul FROM kegiatan WHERE id = ?");
            $stmt->execute([$k_id]);
            $curr = $stmt->fetch();

            // Send Telegram Notification
            if ($msg_notify) {
                require_once 'includes/notification_helper.php';
                $msg_notify .= htmlspecialchars($curr['judul']);
                $msg_notify .= "\n<b>Oleh:</b> " . $_SESSION['nama'];
                if ($note) $msg_notify .= "\n<b>Catatan:</b> " . htmlspecialchars($note);
                $msg_notify .= "\n👉 <a href='" . base_url("kegiatan_detail.php?id=$k_id") . "'>Lihat Detail</a>";
                sendTelegramNotification($pdo, $msg_notify);
            }

            header("Location: kegiatan_detail.php?id=$k_id&msg=custom&text=" . urlencode("Status berhasil diperbarui menjadi " . ucfirst($new_status)));
            exit;
        }
    } catch (Exception $e) {
        $error = "Gagal memproses aksi: " . $e->getMessage();
    }
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT k.*, b.nama_bidang, u.nama as pembuat
                       FROM kegiatan k
                       JOIN bidang b ON k.bidang_id = b.id
                       JOIN users u ON k.created_by = u.id
                       WHERE k.id = ?");
$stmt->execute([$id]);
$k = $stmt->fetch();

if (!$k) {
    header("Location: kegiatan.php");
    exit;
}

// Fetch photos with user attribution
$stmt_fotos = $pdo->prepare("SELECT fk.*, u.nama as contributor
                            FROM foto_kegiatan fk
                            LEFT JOIN users u ON fk.user_id = u.id
                            WHERE fk.kegiatan_id = ?
                            ORDER BY fk.uploaded_at ASC");
$stmt_fotos->execute([$id]);
$fotos = $stmt_fotos->fetchAll();

// Get unique contributors list
$contributors = array_unique(array_filter(array_column($fotos, 'contributor')));

$page_title = 'Detail Kegiatan';
$active_page = 'kegiatan';
include 'includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-4 animate-up">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="kegiatan.php" class="text-decoration-none">Kegiatan</a></li>
        <li class="breadcrumb-item active">Detail Dokumentasi</li>
    </ol>
</nav>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
    <div class="alert alert-success border-0 shadow-sm animate-up" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> Kegiatan berhasil disimpan!
    </div>
<?php endif; ?>

<?php if (isset($_GET['skipped'])): ?>
    <div class="alert alert-warning border-0 shadow-sm animate-up" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Peringatan Duplicate:</strong> Beberapa foto identik sudah ada sistem.
    </div>
<?php endif; ?>

<div class="row g-4 animate-up" style="animation-delay: 0.1s;">
    <div class="col-lg-8">
        <div class="card-modern p-4 mb-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <?php
                    $tipe_labels = [
                        'biasa' => 'Dokumentasi Umum',
                        'rapat' => 'Notulen Rapat',
                        'pengaduan' => 'Laporan Pengaduan',
                        'monev' => 'Monitoring & Evaluasi'
                    ];
                    $label_tipe = $tipe_labels[$k['tipe_kegiatan']] ?? strtoupper($k['tipe_kegiatan']);
                    ?>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 px-3"><?= $label_tipe ?></span>
                        <?php if ($k['kategori']): ?>
                            <span class="badge bg-info bg-opacity-10 text-info border-info border-opacity-25 px-3"><?= htmlspecialchars($k['kategori']) ?></span>
                        <?php endif; ?>
                        <?php 
                        // Auto-generate Join Code if missing for Owner/Admin
                        if (empty($k['join_code']) && (has_role(['admin', 'operator']) || $_SESSION['user_id'] == $k['created_by'])) {
                            $new_code = strtoupper(substr(uniqid(), -6));
                            $stmt_up = $pdo->prepare("UPDATE kegiatan SET join_code = ? WHERE id = ?");
                            $stmt_up->execute([$new_code, $k['id']]);
                            $k['join_code'] = $new_code;
                        }
                        ?>
                        
                        <?php if ($k['join_code']): ?>
                            <div class="d-inline-flex align-items-center bg-white border border-secondary border-opacity-25 rounded-pill ps-3 pe-2 py-1 shadow-sm clickable-code" onclick="copyToClipboard('<?= $k['join_code'] ?>')" style="cursor: pointer;" title="Klik untuk Salin Kode Join">
                                <small class="text-muted fw-bold me-2">KODE TIM:</small>
                                <span class="fw-bold text-primary me-2 f-mono"><?= $k['join_code'] ?></span>
                                <span class="badge bg-primary rounded-circle p-1"><i class="bi bi-files"></i></span>
                            </div>
                            <script>
                            function copyToClipboard(text) {
                                navigator.clipboard.writeText(text).then(function() {
                                    alert('Kode Join disalin: ' + text);
                                }, function(err) {
                                    console.error('Async: Could not copy text: ', err);
                                });
                            }
                            </script>
                        <?php endif; ?>
                    </div>
                    <h2 class="fw-bold title-main mb-1"><?= htmlspecialchars($k['judul']) ?></h2>
                    <p class="text-muted small mb-0"><i class="bi bi-person-fill me-1"></i> Diinput oleh <?= htmlspecialchars($k['pembuat']) ?> pada <?= format_tanggal_indonesia($k['created_at']) ?></p>
                </div>
                <div class="d-flex gap-2">
                    <?php
                    $pdf_label = "Cetak PDF";
                    $pdf_url = "laporan_pdf.php?id=" . $id;
                    
                    if ($k['tipe_kegiatan'] === 'rapat') {
                        $pdf_label = "Notulen";
                        $pdf_url = "laporan_rapat.php?id=" . $id;
                    } elseif ($k['tipe_kegiatan'] === 'pengaduan') {
                        $pdf_label = "Lap. Pengaduan";
                        $pdf_url = "laporan_pengaduan.php?id=" . $id;
                    } elseif ($k['tipe_kegiatan'] === 'monev') {
                        $pdf_label = "Lap. Monev";
                        $pdf_url = "laporan_pdf.php?id=" . $id;
                    }
                    ?>
                    <a href="<?= $pdf_url ?>" class="btn btn-primary-modern btn-sm shadow-sm d-flex align-items-center">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> <span class="d-none d-md-inline"><?= $pdf_label ?></span><span class="d-inline d-md-none">PDF</span>
                    </a>
                    <?php if (has_role('pimpinan')): ?>
                        <a href="laporan_pdf.php?id=<?= $id ?>&mode=formal" class="btn btn-dark btn-sm shadow-sm d-flex align-items-center">
                            <i class="bi bi-person-workspace me-1"></i> <span class="d-none d-md-inline">Laporan Bupati</span><span class="d-inline d-md-none">Bupati</span>
                        </a>
                    <?php endif; ?>
                    <?php if (has_role(['admin', 'operator'])): ?>
                        <a href="kegiatan_edit.php?id=<?= $k['id'] ?>" class="btn btn-modern btn-light border btn-sm d-flex align-items-center">
                            <i class="bi bi-pencil me-1"></i> <span class="d-none d-md-inline">Edit</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Bar for Status -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 border">
                    <div class="d-flex align-items-center gap-3">
                        <?php
                        $st = $k['status'] ?? 'draft';
                        $badges = [
                            'draft' => 'bg-secondary',
                            'pending' => 'bg-warning text-dark',
                            'verified' => 'bg-success',
                            'rejected' => 'bg-danger',
                            'revision' => 'bg-danger'
                        ];
                        $status_label = [
                            'draft' => 'Draft',
                            'pending' => 'Menunggu Verifikasi',
                            'verified' => 'Terverifikasi',
                            'rejected' => 'Ditolak',
                            'revision' => 'Perlu Revisi'
                        ];
                        ?>
                        <span class="badge rounded-pill <?= $badges[$st] ?> fs-6 px-3 py-2">
                            <?= $status_label[$st] ?>
                        </span>
                        <?php if ($st === 'revision' && $k['catatan_revisi']): ?>
                            <small class="text-danger fw-bold"><i class="bi bi-info-circle me-1"></i> Note: <?= htmlspecialchars($k['catatan_revisi']) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <!-- Button Send (for Owner) -->
                        <?php if (($_SESSION['user_id'] == $k['created_by']) && in_array($st, ['draft', 'revision'])): ?>
                            <form method="POST" onsubmit="return confirm('Kirim laporan ini untuk diverifikasi?');">
                                <input type="hidden" name="action_status" value="send">
                                <input type="hidden" name="kegiatan_id" value="<?= $k['id'] ?>">
                                <input type="hidden" name="current_status" value="<?= $st ?>">
                                <input type="hidden" name="created_by" value="<?= $k['created_by'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-send-fill me-1"></i> Kirim
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Buttons Verify/Reject (for Admin) -->
                        <?php if (has_role(['admin', 'pimpinan', 'operator']) && $st === 'pending'): ?>
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                                <i class="bi bi-x-circle me-1"></i> Revisi
                            </button>
                            <form method="POST" onsubmit="return confirm('Verifikasi laporan ini?');">
                                <input type="hidden" name="action_status" value="verify">
                                <input type="hidden" name="kegiatan_id" value="<?= $k['id'] ?>">
                                <input type="hidden" name="current_status" value="<?= $st ?>">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-lg me-1"></i> Terima
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="p-3 rounded-3 bg-light border-0 h-100">
                        <label class="text-muted small fw-bold text-uppercase d-block mb-1">Waktu Pelaksanaan</label>
                        <?php
                            $tgl = format_tanggal_indonesia($k['tanggal']);
                            $jam = "";
                            if ($k['jam_mulai']) {
                                $jam = date('H:i', strtotime($k['jam_mulai'])) . ' WIB';
                                if ($k['jam_selesai']) {
                                    $jam = date('H:i', strtotime($k['jam_mulai'])) . ' - ' . date('H:i', strtotime($k['jam_selesai'])) . ' WIB';
                                }
                            }
                        ?>
                        <div class="fw-bold"><i class="bi bi-calendar3 text-primary me-2"></i> <?= $tgl ?></div>
                        <?php if($jam): ?>
                            <div class="small text-muted mt-1"><i class="bi bi-clock me-2"></i> <?= $jam ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3 bg-light border-0 h-100">
                        <label class="text-muted small fw-bold text-uppercase d-block mb-1">Lokasi / Tempat</label>
                        <div class="fw-bold text-truncate"><i class="bi bi-geo-alt text-danger me-2"></i> <?= htmlspecialchars($k['lokasi'] ?: '-') ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3 bg-light border-0 h-100">
                        <label class="text-muted small fw-bold text-uppercase d-block mb-1">Bidang Kerja</label>
                        <div class="fw-bold"><i class="bi bi-building text-info me-2"></i> <?= htmlspecialchars($k['nama_bidang']) ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3 bg-light border-0 h-100">
                        <label class="text-muted small fw-bold text-uppercase d-block mb-1">Penanggung Jawab</label>
                        <div class="fw-bold"><i class="bi bi-person-check text-success me-2"></i> <?= htmlspecialchars($k['penanggung_jawab'] ?: '-') ?></div>
                    </div>
                </div>
            </div>

            <!-- Detil Khusus RAPAT -->
            <?php if ($k['tipe_kegiatan'] === 'rapat'): ?>
                <div class="bg-primary bg-opacity-10 p-4 rounded-4 mb-4 border-2 border-primary border-opacity-10">
                    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-journal-text me-2"></i>Notulen Rapat</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block">Pimpinan Rapat</label>
                            <span class="fw-bold"><?= htmlspecialchars($k['pimpinan_rapat'] ?: '-') ?></span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block">Notulis</label>
                            <span class="fw-bold"><?= htmlspecialchars($k['notulis'] ?: '-') ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-bold d-block">Agenda</label>
                        <div><?= nl2br(htmlspecialchars($k['agenda'] ?: '-')) ?></div>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small fw-bold d-block">Hasil Kesimpulan</label>
                        <div class="fw-bold"><?= nl2br(htmlspecialchars($k['kesimpulan'] ?: '-')) ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Detil Khusus PENGADUAN -->
            <?php if ($k['tipe_kegiatan'] === 'pengaduan'): ?>
                <div class="bg-warning bg-opacity-10 p-4 rounded-4 mb-4 border-2 border-warning border-opacity-10">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-warning mb-0"><i class="bi bi-chat-dots-fill me-2"></i>Detail Pengaduan</h5>
                        <?php
                        $status_class = 'bg-secondary';
                        if ($k['status_pengaduan'] === 'proses') $status_class = 'bg-info';
                        elseif ($k['status_pengaduan'] === 'selesai') $status_class = 'bg-success';
                        ?>
                        <span class="badge <?= $status_class ?> px-3"><?= strtoupper($k['status_pengaduan']) ?></span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-bold d-block">Nama Pelapor</label>
                        <span class="fw-bold"><?= htmlspecialchars($k['nama_pelapor'] ?: '-') ?></span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-bold d-block">Permasalahan</label>
                        <div><?= nl2br(htmlspecialchars($k['masalah'] ?: '-')) ?></div>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small fw-bold d-block">Tindak Lanjut</label>
                        <div class="fw-bold text-dark"><?= nl2br(htmlspecialchars($k['tindak_lanjut'] ?: '-')) ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Detil Khusus MONEV -->
            <?php if ($k['tipe_kegiatan'] === 'monev'): ?>
                <div class="bg-info bg-opacity-10 p-4 rounded-4 mb-4 border-2 border-info border-opacity-10">
                    <h5 class="fw-bold text-info mb-3"><i class="bi bi-clipboard-check-fill me-2"></i>Hasil Monitoring & Evaluasi</h5>
                    <div class="row g-4 mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Temuan di Lapangan</label>
                            <div class="fw-bold text-dark"><?= nl2br(htmlspecialchars($k['temuan'] ?: '-')) ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Saran & Rekomendasi</label>
                            <div class="fw-bold text-dark"><?= nl2br(htmlspecialchars($k['saran_rekomendasi'] ?: '-')) ?></div>
                        </div>
                    </div>
                    <div class="pt-3 border-top border-info border-opacity-10">
                        <label class="text-muted small fw-bold d-block mb-2 text-uppercase">Tingkat Capaian / Progres</label>
                        <div class="progress" style="height: 25px; border-radius: 10px;">
                            <div class="progress-bar bg-info progress-bar-striped progress-bar-animated fst-italic shadow-sm" role="progressbar" style="width: <?= $k['capaian'] ?>%;" aria-valuenow="<?= $k['capaian'] ?>" aria-valuemin="0" aria-valuemax="100"><?= $k['capaian'] ?>% Telah Dicapai</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label class="text-label mb-2">Deskripsi Kegiatan</label>
                <div class="p-4 rounded-4 bg-light border-0" style="white-space: pre-wrap; line-height: 1.6;"><?= htmlspecialchars($k['deskripsi'] ?: 'Tidak ada deskripsi tambahan untuk kegiatan ini.') ?></div>
            </div>

            <!-- Dokumentasi Foto (Collaborative Grid) -->
            <h5 class="fw-bold title-main mb-3 mt-4"><i class="bi bi-camera me-2"></i>Dokumentasi Foto Lapangan</h5>
            
            <?php if (empty($fotos)): ?>
                <div class="col-12 text-center py-5 border rounded-4 border-dashed bg-light">
                    <i class="bi bi-image-fill fs-1 text-muted opacity-25 d-block mb-3"></i>
                    <p class="text-muted mb-0">Belum ada foto yang diunggah.</p>
                </div>
            <?php else: ?>
                <?php
                // Group by contributor
                $grouped_fotos = [];
                foreach ($fotos as $f) {
                    $c = $f['contributor'] ?: 'Unggah Manual / Sistem';
                    $grouped_fotos[$c][] = $f;
                }
                ?>
                
                <?php foreach ($grouped_fotos as $contributor => $c_fotos): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2 px-1">
                            <i class="bi bi-person-circle text-primary me-2"></i>
                            <span class="fw-bold small text-muted text-uppercase" style="letter-spacing: 0.5px;">Kontributor: <?= htmlspecialchars($contributor) ?></span>
                        </div>
                        <div class="row g-2">
                            <?php foreach ($c_fotos as $f): ?>
                                <div class="col-6 col-md-4">
                                    <div class="gallery-item rounded-4 overflow-hidden shadow-sm h-100 position-relative border">
                                        <a href="uploads/foto/<?= $f['file'] ?>" target="_blank">
                                            <img src="uploads/foto/<?= $f['file'] ?>" class="w-100 h-100 object-fit-cover" alt="Foto" style="min-height: 150px;">
                                        </a>
                                        <?php if ($f['keterangan']): ?>
                                            <div class="p-2 bg-white border-top small text-muted text-center italic" style="font-size: 11px;">
                                                "<?= htmlspecialchars($f['keterangan']) ?>"
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-modern p-4 mb-4 sticky-lg-top" style="top: 100px;">
            <h5 class="fw-bold title-main mb-4">Laporan & Progress</h5>
            
            <?php
            $stmt_ek = $pdo->prepare("SELECT id, status FROM laporan_ekinerja WHERE kegiatan_id = ?");
            $stmt_ek->execute([$k['id']]);
            $ekinerja = $stmt_ek->fetch();
            ?>
            
            <div class="mb-4">
                <?php if ($ekinerja): ?>
                    <div class="p-3 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-10 mb-3">
                        <div class="text-success small fw-bold mb-2"><i class="bi bi-check-circle-fill me-1"></i> STATUS E-KINERJA</div>
                        <p class="small text-muted mb-3">Laporan e-Kinerja sudah dibuat dan siap untuk di-upload.</p>
                        <a href="ekinerja_detail.php?id=<?= $ekinerja['id'] ?>" class="btn btn-modern btn-success w-100">
                            <i class="bi bi-eye me-2"></i> Lihat e-Kinerja
                        </a>
                    </div>
                <?php else: ?>
                    <div class="p-3 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-10 mb-3">
                        <div class="text-warning small fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> STATUS E-KINERJA</div>
                        <p class="small text-muted mb-3">Kegiatan ini belum dilaporkan ke format e-Kinerja.</p>
                        <?php if (has_role(['admin', 'operator'])): ?>
                            <a href="ekinerja_tambah.php?kegiatan_id=<?= $k['id'] ?>" class="btn btn-modern btn-outline-warning w-100">
                                <i class="bi bi-file-earmark-plus me-2"></i> Buat Laporan
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <hr class="my-4 opacity-50">
            
            <div class="d-grid gap-2">
                    <?php
                    $pdf_label = "Cetak Laporan PDF";
                    $pdf_url = "laporan_pdf.php?id=" . $id;
                    
                    if ($k['tipe_kegiatan'] === 'rapat') {
                        $pdf_label = "Unduh Notulen Rapat";
                        $pdf_url = "laporan_rapat.php?id=" . $id;
                    } elseif ($k['tipe_kegiatan'] === 'pengaduan') {
                        $pdf_label = "Unduh Laporan Pengaduan";
                        $pdf_url = "laporan_pengaduan.php?id=" . $id;
                    } elseif ($k['tipe_kegiatan'] === 'monev') {
                        $pdf_label = "Unduh Laporan Monev";
                        $pdf_url = "laporan_pdf.php?id=" . $id;
                    } else {
                        // Use category for label if exists
                        if ($k['kategori']) {
                            $pdf_label = "Cetak Lap. " . $k['kategori'];
                            // Compact long labels
                            if (strlen($pdf_label) > 25) $pdf_label = "Cetak Laporan Tugas";
                        }
                    }
                    ?>
                    <a href="<?= $pdf_url ?>" class="btn btn-modern btn-primary-modern shadow-sm">
                        <i class="bi bi-download me-2"></i> <?= $pdf_label ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- e-Kinerja Confirmation Modal -->
<?php if (isset($_GET['show_ekinerja_dialog']) && $_GET['show_ekinerja_dialog'] == '1'): ?>
<div class="modal fade" id="ekinjerjaConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-4 text-center">
                <div class="bg-success bg-opacity-10 rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                    <i class="bi bi-check-lg text-success fs-1"></i>
                </div>
                <h4 class="fw-bold mb-2">Simpan Berhasil!</h4>
                <p class="text-muted mb-4">Lanjutkan untuk membuat format laporan e-Kinerja (BKN) untuk kegiatan ini?</p>
                <div class="d-grid gap-2">
                    <a href="ekinerja_tambah.php?kegiatan_id=<?= $k['id'] ?>" class="btn btn-modern btn-success py-2">
                        <i class="bi bi-file-earmark-text me-2"></i> Ya, Buat Laporan Sekarang
                    </a>
                    <button type="button" class="btn btn-modern btn-light py-2" data-bs-dismiss="modal">Nanti Saja</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('ekinjerjaConfirmModal'));
        myModal.show();
        myModal._element.addEventListener('hidden.bs.modal', function() {
            const url = new URL(window.location);
            url.searchParams.delete('show_ekinerja_dialog');
            window.history.replaceState({}, '', url);
        });
    });
</script>
<?php endif; ?>

<!-- Revision Modal -->
<div class="modal fade" id="modalRevisi" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Minta Revisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action_status" value="revise">
                <input type="hidden" name="kegiatan_id" value="<?= $k['id'] ?>">
                <input type="hidden" name="current_status" value="<?= $k['status'] ?>">
                <div class="mb-3">
                    <label class="form-label">Catatan Revisi</label>
                    <textarea name="catatan_revisi" class="form-control" rows="3" required placeholder="Jelaskan apa yang perlu diperbaiki..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Kirim Permintaan Revisi</button>
            </div>
        </form>
    </div>
</div>
<div class="mb-5 py-4"></div> <!-- Structural spacer for mobile nav -->
<?php include 'includes/footer.php'; ?>

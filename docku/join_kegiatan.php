<?php
// join_kegiatan.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_role(['admin', 'operator']);

$page_title = 'Gabung Kegiatan';
$active_page = 'kegiatan';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $join_code = strtoupper(trim($_POST['join_code'] ?? ''));

    if ($join_code) {
        $stmt = $pdo->prepare("SELECT id, judul FROM kegiatan WHERE join_code = ?");
        $stmt->execute([$join_code]);
        $kegiatan = $stmt->fetch();

        if ($kegiatan) {
            $kegiatan_id = $kegiatan['id'];
            $user_id = $_SESSION['user_id'];
            $skipped_files = [];

            // Handle Camera Photos
            if (!empty($_POST['camera_photos'])) {
                foreach ($_POST['camera_photos'] as $idx => $filename) {
                    $full_path = 'uploads/foto/' . $filename;
                    if (file_exists($full_path)) {
                        $file_hash = md5_file($full_path);
                        $keterangan = $_POST['camera_captions'][$idx] ?? null;
                        
                        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM foto_kegiatan WHERE file_hash = ?");
                        $stmt_check->execute([$file_hash]);
                        if ($stmt_check->fetchColumn() > 0) {
                            $skipped_files[] = "Kamera " . $filename . " (Duplicate)";
                            continue;
                        }

                        $stmt_foto = $pdo->prepare("INSERT INTO foto_kegiatan (kegiatan_id, user_id, file, file_hash, keterangan) VALUES (?, ?, ?, ?, ?)");
                        $stmt_foto->execute([$kegiatan_id, $user_id, $filename, $file_hash, $keterangan]);
                    }
                }

                // Integration: Check if this activity belongs to a disposisi
                $stmt_dispo = $pdo->prepare("SELECT id FROM kegiatan WHERE id = ? AND created_by IS NOT NULL");
                $stmt_dispo->execute([$kegiatan_id]);
                // We need to check if this activity WAS created from a disposisi
                // Actually, let's just check if there's a disposisi_penerima record for this user and this activity's linked disposisi
                $stmt_link = $pdo->prepare("SELECT d.id FROM disposisi d JOIN disposisi_penerima dp ON d.id = dp.disposisi_id WHERE dp.kegiatan_id = ? OR d.id = (SELECT IFNULL(MAX(id),0) FROM disposisi WHERE perihal = (SELECT judul FROM kegiatan WHERE id = ?))");
                // Smarter way: check if this user has a pending disposisi with the same title as the activity they are joining
                $stmt_sync = $pdo->prepare("UPDATE disposisi_penerima dp 
                                          JOIN disposisi d ON dp.disposisi_id = d.id 
                                          JOIN kegiatan k ON k.judul = d.perihal
                                          SET dp.status = 'dilaksanakan', dp.kegiatan_id = k.id, dp.tgl_dilaksanakan = NOW()
                                          WHERE k.id = ? AND dp.user_id = ? AND dp.status != 'dilaksanakan'");
                $stmt_sync->execute([$kegiatan_id, $user_id]);

                // Notification: Send Telegram to Leadership
                if ($stmt_sync->rowCount() > 0) {
                    $userName = $_SESSION['nama'] ?? 'Tim Lapangan';
                    $msg = "🤝 <b>KONTRIBUSI TIM DISALURKAN</b>\n\n";
                    $msg .= "<b>Perihal:</b> " . htmlspecialchars($kegiatan['judul']) . "\n";
                    $msg .= "<b>Kontributor:</b> " . htmlspecialchars($userName) . "\n";
                    $msg .= "<b>Status:</b> Tugas Terverifikasi Selesai\n\n";
                    
                    // Get base URL for link
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $link = $protocol . "://" . $_SERVER['HTTP_HOST'] . str_replace('join_kegiatan.php', '', $_SERVER['SCRIPT_NAME']) . "kegiatan_detail.php?id=" . $kegiatan_id;
                    
                    $msg .= "👉 <a href='{$link}'>Lihat Dokumentasi Tim</a>";
                    
                    sendTelegramNotification($pdo, $msg);
                }

                $success = "Foto berhasil ditambahkan ke kegiatan: <strong>" . htmlspecialchars($kegiatan['judul']) . "</strong>";
            } else {
                $error = "Belum ada foto yang diambil untuk dikirim.";
            }
        } else {
            $error = "Kode Join tidak ditemukan. Silakan hubungi pembuat kegiatan untuk kodenya.";
        }
    } else {
        $error = "Harap masukkan Kode Join.";
    }
}

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card-modern border-0 p-4 animate-up">
                <div class="text-center mb-4">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-people-fill fs-1"></i>
                    </div>
                    <h3 class="fw-bold title-main mb-1">Gabung Tim Lapangan</h3>
                    <p class="text-muted small">Kirim dokumentasi Anda ke laporan tim yang sudah ada</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4">
                        <i class="bi bi-check-circle me-2"></i><?= $success ?>
                        <div class="mt-2">
                            <a href="kegiatan_detail.php?id=<?= $kegiatan_id ?>" class="btn btn-sm btn-success text-white px-3">Lihat Laporan</a>
                            <a href="join_kegiatan.php" class="btn btn-sm btn-outline-success border-0 px-3">Tambah Lagi</a>
                        </div>
                    </div>
                <?php else: ?>
                    <form action="" method="POST" id="joinForm">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-uppercase small text-muted">Kode Join (Dari Koordinator/Pembuat Laporan)</label>
                            <input type="text" name="join_code" class="form-control form-control-lg text-center fw-bold fs-3 text-primary border-2 shadow-none" maxlength="6" placeholder="XXXXXX" required autocomplete="off">
                            <div class="form-text text-center mt-2">Masukkan 6 digit kode yang dibagikan oleh pembuat laporan.</div>
                        </div>

                        <!-- Camera Section Integrated -->
                        <div class="mb-4 text-center">
                            <label class="form-label d-block fw-bold text-uppercase small text-muted mb-3">Dokumentasi Foto Anda</label>
                            <div id="captured-preview" class="row g-2 mb-3">
                                <!-- Preview captured photos here -->
                                <div class="col-12 text-center text-muted py-4 border-2 border-dashed rounded-4 bg-light">
                                    <i class="bi bi-image fs-1 opacity-25 d-block mb-1"></i>
                                    Format: Foto Watermark GPS
                                </div>
                            </div>
                            
                            <a href="camera.php" class="btn btn-modern btn-outline-primary w-100 py-3 mb-2">
                                <i class="bi bi-camera me-2"></i> Ambil Foto Baru
                            </a>
                        </div>

                        <div id="camera-hidden-inputs"></div>

                        <button type="submit" class="btn btn-modern btn-primary-modern w-100 py-3 mt-2 shadow">
                            <i class="bi bi-send-fill me-2"></i> Kirim Dokumentasi
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="text-center mt-4 text-muted small">
                <a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle Camera Redirect logic
    const capturedPreview = document.getElementById('captured-preview');
    const cameraHiddenInputs = document.getElementById('camera-hidden-inputs');

    function loadCapturedPhotos() {
        const photos = JSON.parse(sessionStorage.getItem('captured_photos') || '[]');
        if (photos.length > 0) {
            capturedPreview.innerHTML = '';
            photos.forEach((filename, idx) => {
                const col = document.createElement('div');
                col.className = 'col-6 col-md-4 mb-3';
                col.innerHTML = `
                    <div class="ratio ratio-1x1 rounded-4 overflow-hidden shadow-sm border mb-2">
                        <img src="uploads/foto/${filename}" class="object-fit-cover w-100 h-100">
                    </div>
                    <input type="text" name="camera_captions[]" class="form-control form-control-sm border-0 bg-light rounded-pill px-3" placeholder="Ket. foto..." style="font-size: 11px;">
                `;
                capturedPreview.appendChild(col);

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'camera_photos[]';
                input.value = filename;
                cameraHiddenInputs.appendChild(input);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', loadCapturedPhotos);
    
    // Clear session when success is shown
    <?php if ($success): ?>
        sessionStorage.removeItem('captured_photos');
    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>

<?php
// kegiatan_tambah.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_once 'includes/integration_helper.php'; // Outbound Webhook
require_role(['admin', 'operator', 'pimpinan', 'staff']);

$page_title = 'Tambah Kegiatan';
$active_page = 'kegiatan';

$error = '';
$success = '';

$bidang_list = $pdo->query("SELECT * FROM bidang")->fetchAll();

$skipped_files = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = $_POST['judul'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $lokasi = $_POST['lokasi'] ?? '';
    $bidang_id = $_POST['bidang_id'] ?? '';
    $tipe_kegiatan = $_POST['tipe_kegiatan'] ?? 'biasa';
    $kategori = (!empty($_POST['kategori'])) ? $_POST['kategori'] : null;
    $penanggung_jawab = $_POST['penanggung_jawab'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    
    // Rapat Fields
    $pimpinan_rapat = $_POST['pimpinan_rapat'] ?? null;
    $notulis = $_POST['notulis'] ?? null;
    $agenda = $_POST['agenda'] ?? null;
    $kesimpulan = $_POST['kesimpulan'] ?? null;
    
    // Pengaduan Fields
    $nama_pelapor = $_POST['nama_pelapor'] ?? null;
    $masalah = $_POST['masalah'] ?? null;
    $tindak_lanjut = $_POST['tindak_lanjut'] ?? null;
    $status_pengaduan = $_POST['status_pengaduan'] ?? null;
    
    // Monev Fields
    $temuan = $_POST['temuan'] ?? null;
    $saran_rekomendasi = $_POST['saran_rekomendasi'] ?? null;
    $capaian = intval($_POST['capaian'] ?? 0);
    
    $created_by = $_SESSION['user_id'];
    
    // Integration: Check for disposisi linkage
    $disposisi_id = intval($_POST['disposisi_id'] ?? 0);

    // Generate Join Code (Collab Feature)
    $join_code = strtoupper(substr(uniqid(), -6));

    if ($judul && $tanggal && $bidang_id) {
        try {
            $pdo->beginTransaction();

            $jam_mulai = $_POST['jam_mulai'] ?? null;
            $jam_selesai = $_POST['jam_selesai'] ?? null;
            $action = $_POST['action'] ?? 'draft';
            $status = ($action === 'send') ? 'pending' : 'draft';

            $sql = "INSERT INTO kegiatan (
                judul, tanggal, jam_mulai, jam_selesai, lokasi, bidang_id, tipe_kegiatan, kategori, penanggung_jawab, deskripsi, 
                pimpinan_rapat, notulis, agenda, kesimpulan, 
                nama_pelapor, masalah, tindak_lanjut, status_pengaduan, 
                temuan, saran_rekomendasi, capaian,
                created_by, join_code, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $judul, $tanggal, $jam_mulai, $jam_selesai, $lokasi, $bidang_id, $tipe_kegiatan, $kategori, $penanggung_jawab, $deskripsi,
                $pimpinan_rapat, $notulis, $agenda, $kesimpulan,
                $nama_pelapor, $masalah, $tindak_lanjut, $status_pengaduan,
                $temuan, $saran_rekomendasi, $capaian,
                $created_by, $join_code, $status
            ]);
            $kegiatan_id = $pdo->lastInsertId();
            
            // Integration: Link Disposisi if exists
            if ($disposisi_id > 0) {
                // Update disposisi_penerima status
                $stmt_dp = $pdo->prepare("UPDATE disposisi_penerima SET status = 'dilaksanakan', tgl_dilaksanakan = NOW(), kegiatan_id = ? WHERE disposisi_id = ? AND user_id = ?");
                $stmt_dp->execute([$kegiatan_id, $disposisi_id, $_SESSION['user_id']]);
                
                // Trigger Outbound Webhook
                triggerOutboundWebhook($pdo, $disposisi_id);
            }

// ... [rest of file upload logic remains same]
            // Handle Photo Uploads
            if (!empty($_FILES['fotos']['name'][0])) {
                $foto_captions = $_POST['foto_captions'] ?? [];
                handle_uploads($_FILES['fotos'], $pdo, $kegiatan_id, $skipped_files, $created_by, $foto_captions);
            }

            // Handle Camera Photos
            if (!empty($_POST['camera_photos'])) {
                $camera_captions = $_POST['camera_captions'] ?? [];
                foreach ($_POST['camera_photos'] as $idx => $filename) {
                    $full_path = 'uploads/foto/' . $filename;
                    if (file_exists($full_path)) {
                        $file_hash = md5_file($full_path);
                        $keterangan = $camera_captions[$idx] ?? null;

                        // Check for duplicate hash
                        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM foto_kegiatan WHERE file_hash = ?");
                        $stmt_check->execute([$file_hash]);
                        if ($stmt_check->fetchColumn() > 0) {
                            $skipped_files[] = "Kamera " . $filename . " (Duplicate)";
                            continue;
                        }

                        $stmt_foto = $pdo->prepare("INSERT INTO foto_kegiatan (kegiatan_id, user_id, file, file_hash, keterangan) VALUES (?, ?, ?, ?, ?)");
                        $stmt_foto->execute([$kegiatan_id, $created_by, $filename, $file_hash, $keterangan]);
                    }
                }
            }

            // Integration: Mark Disposisi as Done
            if ($disposisi_id > 0) {
                $stmt_dp = $pdo->prepare("UPDATE disposisi_penerima SET status = 'dilaksanakan', kegiatan_id = ?, tgl_dilaksanakan = NOW() WHERE disposisi_id = ? AND user_id = ?");
                $stmt_dp->execute([$kegiatan_id, $disposisi_id, $created_by]);

                // Notification: Send Telegram to Leadership
                $userName = $_SESSION['nama'] ?? 'Staf';
                $msg = "✅ <b>TUGAS SELESAI DILAKSANAKAN</b>\n\n";
                $msg .= "<b>Perihal:</b> " . htmlspecialchars($judul) . "\n";
                $msg .= "<b>Pelaksana:</b> " . htmlspecialchars($userName) . "\n";
                $msg .= "<b>Waktu:</b> " . date('d/m/Y H:i') . "\n\n";
                
                // Get base URL for link
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $link = $protocol . "://" . $_SERVER['HTTP_HOST'] . str_replace('kegiatan_tambah.php', '', $_SERVER['SCRIPT_NAME']) . "kegiatan_detail.php?id=" . $kegiatan_id;
                
                $msg .= "👉 <a href='{$link}'>Lihat Dokumentasi</a>";
                
                sendTelegramNotification($pdo, $msg);
            }

            $pdo->commit();

             // Notification logic (only if sent)
             if ($status === 'pending') {
                require_once 'includes/notification_helper.php';
                $msg = "📝 <b>Laporan Baru:</b> " . htmlspecialchars($judul);
                $msg .= "\n<b>Oleh:</b> " . $_SESSION['nama'];
                $msg .= "\n👉 <a href='" . base_url("kegiatan_detail.php?id=$kegiatan_id") . "'>Lihat Detail</a>";
                sendTelegramNotification($pdo, $msg);
            }
            
            $skipped_params = !empty($skipped_files) ? "&skipped=" . urlencode(implode(', ', $skipped_files)) : "";
            // Redirect to detail page with confirmation modal trigger
            header("Location: kegiatan_detail.php?id=$kegiatan_id&msg=success&show_ekinerja_dialog=1" . $skipped_params);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $error = "Harap isi judul, tanggal, dan bidang.";
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-up">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
                <li class="breadcrumb-item"><a href="kegiatan.php" class="text-decoration-none">Kegiatan</a></li>
                <li class="breadcrumb-item active">Tambah</li>
            </ol>
        </nav>
        <h3 class="title-main mb-0">Tambah Kegiatan</h3>
    </div>
</div>

<div class="row justify-content-center animate-up" style="animation-delay: 0.1s;">
    <div class="col-lg-10">
        <div class="card-modern border-0">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0">Detail Formulir</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Internal Linkage -->
                    <input type="hidden" name="disposisi_id" value="<?= htmlspecialchars($_GET['disposisi_id'] ?? '') ?>">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="text-label mb-2">Judul Kegiatan</label>
                            <input type="text" name="judul" class="form-control" required placeholder="Contoh: Rapat Koordinasi Desa" value="<?= htmlspecialchars($_GET['title'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tipe Kegiatan</label>
                            <select name="tipe_kegiatan" id="tipe_kegiatan" class="form-select bg-light fw-bold text-primary" onchange="toggleTipeFields()">
                                <option value="biasa">Dokumentasi Biasa</option>
                                <option value="rapat">Rapat (Notulen)</option>
                                <option value="pengaduan">Laporan Pengaduan</option>
                                <option value="monev">Monitoring & Evaluasi (Monev)</option>
                            </select>
                        </div>
                    </div>

                        <div class="col-md-6 mb-3">
                            <label class="text-label mb-2">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="text-label mb-2">Waktu Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="text-label mb-2">Waktu Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="text-label mb-2">Bidang</label>
                            <select name="bidang_id" class="form-select" required <?= (!has_role(['admin']) && !empty($_SESSION['bidang_id'])) ? 'disabled' : '' ?>>
                                <option value="">Pilih Bidang</option>
                                <?php foreach ($bidang_list as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= ((!has_role(['admin']) && $_SESSION['bidang_id'] == $b['id'])) ? 'selected' : '' ?>><?= htmlspecialchars($b['nama_bidang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(!has_role(['admin']) && !empty($_SESSION['bidang_id'])): ?>
                                <input type="hidden" name="bidang_id" value="<?= $_SESSION['bidang_id'] ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-label mb-2">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control" placeholder="Tempat kegiatan">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Penanggung Jawab</label>
                            <input type="text" name="penanggung_jawab" class="form-control" placeholder="Nama petugas">
                        </div>
                    </div>

                    <!-- Fields Khusus RAPAT -->
                    <div id="fields-rapat" style="display:none;" class="bg-light p-3 rounded mb-3 border-start border-4 border-primary">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-people-fill me-2"></i>Detail Notulen Rapat</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Pimpinan Rapat</label>
                                <input type="text" name="pimpinan_rapat" class="form-control" placeholder="Nama pimpinan">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Notulis</label>
                                <input type="text" name="notulis" class="form-control" placeholder="Nama pencatat">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Agenda Rapat</label>
                            <textarea name="agenda" class="form-control form-control-sm" rows="2" placeholder="Apa saja yang dibahas?"></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Kesimpulan / Keputusan</label>
                            <textarea name="kesimpulan" class="form-control form-control-sm" rows="3" placeholder="Hasil akhir rapat..."></textarea>
                        </div>
                    </div>

                    <!-- Fields Khusus PENGADUAN -->
                    <div id="fields-pengaduan" style="display:none;" class="bg-light p-3 rounded mb-3 border-start border-4 border-warning">
                        <h6 class="fw-bold text-warning mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>Detail Pengaduan Masyarakat</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Nama Pelapor</label>
                                <input type="text" name="nama_pelapor" class="form-control" placeholder="Nama warga/pelapor">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Status Awal</label>
                                <select name="status_pengaduan" class="form-select">
                                    <option value="proses">Sedang Diproses</option>
                                    <option value="selesai">Selesai / Teratasi</option>
                                    <option value="arsip">Arsip Saja</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Permasalahan yang Diadukan</label>
                            <textarea name="masalah" class="form-control form-control-sm" rows="3" placeholder="Detail kronologi / masalah..."></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Tindak Lanjut</label>
                            <textarea name="tindak_lanjut" class="form-control form-control-sm" rows="3" placeholder="Langkah yang sudah/akan diambil..."></textarea>
                        </div>
                    </div>

                    <!-- Detil Khusus MONEV -->
                    <div id="fields-monev" style="display:none;" class="bg-light p-3 rounded mb-3 border-start border-4 border-info">
                        <h6 class="fw-bold text-info mb-3"><i class="bi bi-clipboard-check-fill me-2"></i>Detail Monitoring & Evaluasi (Monev)</h6>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Temuan di Lapangan / Masalah</label>
                            <textarea name="temuan" class="form-control form-control-sm" rows="3" placeholder="Tuliskan fakta atau kendala yang ditemukan saat monitoring..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Saran & Rekomendasi</label>
                            <textarea name="saran_rekomendasi" class="form-control form-control-sm" rows="3" placeholder="Saran perbaikan atau tindak lanjut yang direkomendasikan..."></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Persentase Capaian / Progres</label>
                            <div class="input-group">
                                <input type="number" name="capaian" class="form-control" min="0" max="100" value="0">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Estimasi progres pelaksanaan kegiatan (0-100%).</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori Kegiatan (Sesuai Regulasi)</label>
                        <select name="kategori" class="form-select">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Pelayanan Administrasi dan Publik">A. Pelayanan Administrasi dan Publik</option>
                            <option value="Pengaduan dan Aspirasi Masyarakat">B. Pengaduan dan Aspirasi Masyarakat</option>
                            <option value="Pemerintahan Desa">C. Pemerintahan Desa</option>
                            <option value="Perencanaan dan Pembangunan">D. Perencanaan dan Pembangunan</option>
                            <option value="Ketentraman dan Ketertiban Umum">E. Ketentraman dan Ketertiban Umum (Trantibum)</option>
                            <option value="Sosial, Kemasyarakatan, dan Pemberdayaan">F. Sosial, Kemasyarakatan, dan Pemberdayaan</option>
                            <option value="Koordinasi Lintas Sektor">G. Koordinasi Lintas Sektor</option>
                            <option value="Manajemen Internal Kecamatan">H. Manajemen Internal Kecamatan</option>
                            <option value="Kearsipan dan Persuratan">I. Kearsipan dan Persuratan</option>
                            <option value="Kegiatan Khusus dan Insidental">J. Kegiatan Khusus dan Insidental</option>
                            <option value="Melaksanakan tugas kedinasan lain yang diberikan oleh pimpinan">K. Melaksanakan tugas kedinasan lain yang diberikan oleh pimpinan</option>
                        </select>
                        <div class="form-text">Pilih kategori untuk menentukan judul laporan PDF otomatis.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi Umum / Ringkasan</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Isi jika diperlukan..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Upload atau Ambil Foto</label>
                        <div class="d-flex mb-2 gap-2">
                            <input type="file" name="fotos[]" class="form-control" multiple id="fileInput">
                            <button type="button" class="btn btn-outline-primary d-flex align-items-center" onclick="openCamera()">
                                <i class="bi bi-camera-fill me-2"></i> Kamera
                            </button>
                        </div>
                        <div class="form-text">Ambil foto langsung dengan GPS & Timestamp atau pilih dari galeri.</div>
                        <div id="captured-preview" class="row g-2 mt-2"></div>
                        <div id="camera-hidden-inputs"></div>
                    </div>
                    <hr class="my-4 opacity-25">
                        <a href="kegiatan.php" class="btn btn-modern btn-light border w-100 w-md-auto">Batal</a>
                        <div class="d-flex gap-2 w-100 w-md-auto justify-content-end">
                            <button type="submit" name="action" value="draft" class="btn btn-modern btn-secondary px-4">
                                <i class="bi bi-save me-2"></i> Simpan Draft
                            </button>
                            <button type="submit" name="action" value="send" class="btn btn-modern btn-primary-modern shadow-sm px-4">
                                <i class="bi bi-send-fill me-2"></i> Kirim Laporan
                            </button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleTipeFields() {
        const tipe = document.getElementById('tipe_kegiatan').value;
        const rapat = document.getElementById('fields-rapat');
        const pengaduan = document.getElementById('fields-pengaduan');
        const monev = document.getElementById('fields-monev'); // Get the new monev fields

        // Hide all specific fields first
        rapat.style.display = 'none';
        pengaduan.style.display = 'none';
        monev.style.display = 'none'; // Hide monev fields

        // Show specific fields based on type
        if (tipe === 'rapat') {
            rapat.style.display = 'block';
        } else if (tipe === 'pengaduan') {
            pengaduan.style.display = 'block';
        } else if (tipe === 'monev') { // Handle monev type
            monev.style.display = 'block';
        }
    }

    const capturedPreview = document.getElementById('captured-preview');
    const cameraHiddenInputs = document.getElementById('camera-hidden-inputs');

    function openCamera() {
        // Smart Logic: Auto-fill Start Time if empty
        const timeInput = document.getElementsByName('jam_mulai')[0];
        if (timeInput && !timeInput.value) {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            timeInput.value = `${hours}:${minutes}`;
        }

        const width = 1200;
        const height = 800;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;
        window.open('camera.php', 'BesukSaeCamera', `width=${width},height=${height},top=${top},left=${left},menubar=no,status=no`);
    }

    // Function to load captured photos from session storage and display them
    function loadCapturedPhotos() {
        const photos = JSON.parse(sessionStorage.getItem('captured_photos') || '[]');
        if (photos.length > 0) {
            capturedPreview.innerHTML = ''; // Clear existing previews
            cameraHiddenInputs.innerHTML = ''; // Clear existing hidden inputs

            photos.forEach((filename, idx) => {
                const col = document.createElement('div');
                col.className = 'col-6 col-md-4 mb-3';
                col.setAttribute('data-filename', filename); // Add data attribute for easy removal
                col.innerHTML = `
                    <div class="ratio ratio-1x1 rounded-4 overflow-hidden shadow-sm border mb-2 position-relative">
                        <img src="uploads/foto/${filename}" class="object-fit-cover w-100 h-100">
                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle" onclick="removePhoto(this, '${filename}')" style="width:24px; height:24px; padding:0; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <input type="text" name="camera_captions[]" class="form-control form-control-sm border-0 bg-light rounded-pill px-3" placeholder="Ket. foto..." style="font-size: 11px;">
                `;
                capturedPreview.appendChild(col);

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'camera_photos[]';
                input.value = filename;
                input.id = `input-${filename}`; // Assign an ID for easy removal
                cameraHiddenInputs.appendChild(input);
            });
        }
    }

    // Call loadCapturedPhotos on page load
    document.addEventListener('DOMContentLoaded', loadCapturedPhotos);

    // Modified removePhoto to also remove from session storage and hidden inputs
    function removePhoto(btn, filename) {
        // Remove from DOM
        btn.closest('.col-6').remove();

        // Remove hidden input
        const hiddenInput = document.getElementById(`input-${filename}`);
        if (hiddenInput) {
            hiddenInput.remove();
        }

        // Remove from session storage
        let photos = JSON.parse(sessionStorage.getItem('captured_photos') || '[]');
        photos = photos.filter(name => name !== filename);
        sessionStorage.setItem('captured_photos', JSON.stringify(photos));
    }

    // This function is no longer used directly, replaced by loadCapturedPhotos
    // window.addCapturedPhoto = function(filename) {
    //     // This logic is now handled by loadCapturedPhotos which reads from sessionStorage
    //     // and rebuilds the list. The camera.php should just add to sessionStorage.
    // };
</script>

<?php include 'includes/footer.php'; ?>

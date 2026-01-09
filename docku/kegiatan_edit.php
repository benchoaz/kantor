<?php
// kegiatan_edit.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_role(['admin', 'operator', 'pimpinan', 'staff']);

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM kegiatan WHERE id = ?");
$stmt->execute([$id]);
$k = $stmt->fetch();

if (!$k) {
    header("Location: kegiatan.php");
    exit;
}

$page_title = 'Edit Kegiatan';
$active_page = 'kegiatan';
$error = '';
$success = '';

$bidang_list = $pdo->query("SELECT * FROM bidang")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = $_POST['judul'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $lokasi = $_POST['lokasi'] ?? '';
    $bidang_id = $_POST['bidang_id'] ?? '';
    $tipe_kegiatan = $_POST['tipe_kegiatan'] ?? 'biasa';
    $kategori = $_POST['kategori'] ?? null;
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

    $skipped_files = [];

    if ($judul && $tanggal && $bidang_id) {
        try {
            $pdo->beginTransaction();

            $jam_mulai = $_POST['jam_mulai'] ?? null;
            $jam_selesai = $_POST['jam_selesai'] ?? null;
            
            // Logic: If 'send' action, set status pending. If 'draft', keep as is or set to draft.
            // However, for edit, we might want to allow saving without sending if it's already a draft.
            // If it is already verified, we might not want to reset it? 
            // Simplified: 
            // If action is 'send', force status 'pending'.
            // If action is 'draft', force status 'draft'.
            // If action is 'save' (standard update for verified items), keep status or set to specific logic.
            // Implementation Plan says: "Simpan Draft" or "Kirim Laporan" or "Simpan Perubahan" (for verified)
            
            $action = $_POST['action'] ?? '';
            $status_update_sql = "";
            $params = [
                $judul, $tanggal, $jam_mulai, $jam_selesai, $lokasi, $bidang_id, $tipe_kegiatan, $kategori, $penanggung_jawab, $deskripsi,
                $pimpinan_rapat, $notulis, $agenda, $kesimpulan,
                $nama_pelapor, $masalah, $tindak_lanjut, $status_pengaduan,
                $temuan, $saran_rekomendasi, $capaian
            ];

            if ($action === 'send') {
                $status_update_sql = ", status = 'pending'";
                // Trigger notification later
            } elseif ($action === 'draft') {
                $status_update_sql = ", status = 'draft'";
            }

            $sql = "UPDATE kegiatan SET 
                judul = ?, tanggal = ?, jam_mulai = ?, jam_selesai = ?, lokasi = ?, bidang_id = ?, tipe_kegiatan = ?, kategori = ?, penanggung_jawab = ?, deskripsi = ?, 
                pimpinan_rapat = ?, notulis = ?, agenda = ?, kesimpulan = ?, 
                nama_pelapor = ?, masalah = ?, tindak_lanjut = ?, status_pengaduan = ?,
                temuan = ?, saran_rekomendasi = ?, capaian = ?
                $status_update_sql
                WHERE id = ?";
            
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Handle Photo Uploads
            if (!empty($_FILES['fotos']['name'][0])) {
                $foto_captions = $_POST['foto_captions'] ?? [];
                handle_uploads($_FILES['fotos'], $pdo, $id, $skipped_files, $_SESSION['user_id'], $foto_captions);
            }
            
            // Update Existing Photo Captions
            if (!empty($_POST['existing_captions'])) {
                foreach ($_POST['existing_captions'] as $foto_id => $caption) {
                    $stmt_upd = $pdo->prepare("UPDATE foto_kegiatan SET keterangan = ? WHERE id = ? AND kegiatan_id = ?");
                    $stmt_upd->execute([$caption, $foto_id, $id]);
                }
            }

            // Handle Camera Photos
            if (!empty($_POST['camera_photos'])) {
                foreach ($_POST['camera_photos'] as $filename) {
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
                        $stmt_foto->execute([$id, $_SESSION['user_id'], $filename, $file_hash, $keterangan]);
                    }
                }
            }

            $pdo->commit();

            // Notification if sent
            if ($action === 'send') {
                require_once 'includes/notification_helper.php';
                $msg = "📝 <b>Laporan Dikirim (Edit):</b> " . htmlspecialchars($judul);
                $msg .= "\n<b>Oleh:</b> " . $_SESSION['nama'];
                $msg .= "\n👉 <a href='" . base_url("kegiatan_detail.php?id=$id") . "'>Lihat Detail</a>";
                sendTelegramNotification($pdo, $msg);
            }
            $skipped_params = !empty($skipped_files) ? "&skipped=" . urlencode(implode(', ', $skipped_files)) : "";
            header("Location: kegiatan_detail.php?id=$id&msg=updated" . $skipped_params);
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

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0">Edit Kegiatan</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Judul Kegiatan</label>
                            <input type="text" name="judul" class="form-control" required value="<?= htmlspecialchars($k['judul']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tipe Kegiatan</label>
                            <select name="tipe_kegiatan" id="tipe_kegiatan" class="form-select bg-light fw-bold text-primary" onchange="toggleTipeFields()">
                                <option value="biasa" <?= $k['tipe_kegiatan'] == 'biasa' ? 'selected' : '' ?>>Dokumentasi Biasa</option>
                                <option value="rapat" <?= $k['tipe_kegiatan'] == 'rapat' ? 'selected' : '' ?>>Rapat (Notulen)</option>
                                <option value="pengaduan" <?= $k['tipe_kegiatan'] == 'pengaduan' ? 'selected' : '' ?>>Laporan Pengaduan</option>
                                <option value="monev" <?= $k['tipe_kegiatan'] == 'monev' ? 'selected' : '' ?>>Monitoring & Evaluasi (Monev)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required value="<?= $k['tanggal'] ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Waktu Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control" value="<?= $k['jam_mulai'] ?? '' ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Waktu Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control" value="<?= $k['jam_selesai'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Bidang</label>
                            <select name="bidang_id" class="form-select" required>
                                <?php foreach ($bidang_list as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= $k['bidang_id'] == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nama_bidang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control" value="<?= htmlspecialchars($k['lokasi']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Penanggung Jawab</label>
                            <input type="text" name="penanggung_jawab" class="form-control" value="<?= htmlspecialchars($k['penanggung_jawab']) ?>">
                        </div>
                    </div>

                    <!-- Fields Khusus RAPAT -->
                    <div id="fields-rapat" style="<?= $k['tipe_kegiatan'] == 'rapat' ? 'display:block;' : 'display:none;' ?>" class="bg-light p-3 rounded mb-3 border-start border-4 border-primary">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-people-fill me-2"></i>Detail Notulen Rapat</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Pimpinan Rapat</label>
                                <input type="text" name="pimpinan_rapat" class="form-control" value="<?= htmlspecialchars($k['pimpinan_rapat']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Notulis</label>
                                <input type="text" name="notulis" class="form-control" value="<?= htmlspecialchars($k['notulis'] ?: '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Agenda Rapat</label>
                            <textarea name="agenda" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($k['agenda'] ?: '') ?></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Kesimpulan / Keputusan</label>
                            <textarea name="kesimpulan" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($k['kesimpulan'] ?: '') ?></textarea>
                        </div>
                    </div>

                    <!-- Detil Khusus PENGADUAN -->
                    <div id="fields_pengaduan" class="bg-warning bg-opacity-10 p-4 rounded-4 mb-4 border-2 border-warning border-opacity-10 <?= $k['tipe_kegiatan'] !== 'pengaduan' ? 'd-none' : '' ?>">
                        <h5 class="fw-bold text-warning mb-3"><i class="bi bi-chat-dots-fill me-2"></i>Detail Pengaduan</h5>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Nama Pelapor</label>
                            <input type="text" name="nama_pelapor" class="form-control" value="<?= htmlspecialchars($k['nama_pelapor'] ?: '') ?>" placeholder="Nama lengkap warga/lembaga">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Permasalahan</label>
                            <textarea name="masalah" class="form-control" rows="3" placeholder="Deskripsikan masalah yang diadukan..."><?= htmlspecialchars($k['masalah'] ?: '') ?></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-uppercase">Tindak Lanjut</label>
                                <input type="text" name="tindak_lanjut" class="form-control" value="<?= htmlspecialchars($k['tindak_lanjut'] ?: '') ?>" placeholder="Langkah yang telah/akan diambil">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Status</label>
                                <select name="status_pengaduan" class="form-select">
                                    <option value="proses" <?= $k['status_pengaduan'] == 'proses' ? 'selected' : '' ?>>Proses</option>
                                    <option value="selesai" <?= $k['status_pengaduan'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="arsip" <?= $k['status_pengaduan'] == 'arsip' ? 'selected' : '' ?>>Arsip</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Detil Khusus MONEV -->
                    <div id="fields_monev" class="bg-info bg-opacity-10 p-4 rounded-4 mb-4 border-2 border-info border-opacity-10 <?= $k['tipe_kegiatan'] !== 'monev' ? 'd-none' : '' ?>">
                        <h5 class="fw-bold text-info mb-3"><i class="bi bi-clipboard-check-fill me-2"></i>Detail Monitoring & Evaluasi (Monev)</h5>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Temuan di Lapangan / Masalah</label>
                            <textarea name="temuan" class="form-control" rows="3" placeholder="Tuliskan fakta atau kendala yang ditemukan saat monitoring..."><?= htmlspecialchars($k['temuan'] ?: '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Saran & Rekomendasi</label>
                            <textarea name="saran_rekomendasi" class="form-control" rows="3" placeholder="Saran perbaikan atau tindak lanjut yang direkomendasikan..."><?= htmlspecialchars($k['saran_rekomendasi'] ?: '') ?></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold text-uppercase">Persentase Capaian / Progres</label>
                            <div class="input-group">
                                <input type="number" name="capaian" class="form-control" min="0" max="100" value="<?= intval($k['capaian']) ?>">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Estimasi progres pelaksanaan kegiatan (0-100%).</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Kategori Kegiatan</label>
                        <select name="kategori" class="form-select">
                            <option value="">-- Pilih Kategori --</option>
                            <?php
                            $cats = [
                                "Pelayanan Administrasi dan Publik" => "A. Pelayanan Administrasi dan Publik",
                                "Pengaduan dan Aspirasi Masyarakat" => "B. Pengaduan dan Aspirasi Masyarakat",
                                "Pemerintahan Desa" => "C. Pemerintahan Desa",
                                "Perencanaan dan Pembangunan" => "D. Perencanaan dan Pembangunan",
                                "Ketentraman dan Ketertiban Umum" => "E. Ketentraman dan Ketertiban Umum (Trantibum)",
                                "Sosial, Kemasyarakatan, dan Pemberdayaan" => "F. Sosial, Kemasyarakatan, dan Pemberdayaan",
                                "Koordinasi Lintas Sektor" => "G. Koordinasi Lintas Sektor",
                                "Manajemen Internal Kecamatan" => "H. Manajemen Internal Kecamatan",
                                "Kearsipan dan Persuratan" => "I. Kearsipan dan Persuratan",
                                "Kegiatan Khusus dan Insidental" => "J. Kegiatan Khusus dan Insidental"
                            ];
                            foreach ($cats as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $k['kategori'] == $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi Umum</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($k['deskripsi']) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Tambah Foto Baru</label>
                        <div class="d-flex mb-2 gap-2">
                            <input type="file" name="fotos[]" class="form-control" multiple id="fileInput">
                            <button type="button" class="btn btn-outline-primary d-flex align-items-center" onclick="openCamera()">
                                <i class="bi bi-camera-fill me-2"></i> Kamera
                            </button>
                        </div>
                        <div id="captured-preview" class="row g-2 mt-2"></div>
                        <div id="camera-hidden-inputs"></div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="kegiatan_detail.php?id=<?= $id ?>" class="btn btn-light border">Batal</a>
                        <div class="d-flex gap-2">
                            <?php if (in_array($k['status'], ['draft', 'revision'])): ?>
                                <button type="submit" name="action" value="draft" class="btn btn-secondary px-4">
                                    <i class="bi bi-save me-2"></i> Simpan Draft
                                </button>
                                <button type="submit" name="action" value="send" class="btn btn-primary px-4">
                                    <i class="bi bi-send-fill me-2"></i> Kirim Laporan
                                </button>
                            <?php else: ?>
                                <button type="submit" name="action" value="save" class="btn btn-primary px-4">
                                    <i class="bi bi-check-circle me-2"></i> Simpan Perubahan
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleTipeFields() {
        const tipe = document.getElementById('tipe_kegiatan').value;
        const fieldsRapat = document.getElementById('fields_rapat');
        const fieldsPengaduan = document.getElementById('fields_pengaduan');
        const fieldsMonev = document.getElementById('fields_monev');
        
        // Hide all
        fieldsRapat.classList.add('d-none');
        fieldsPengaduan.classList.add('d-none');
        fieldsMonev.classList.add('d-none');
        
        // Show specific
        if (tipe === 'rapat') {
            fieldsRapat.classList.remove('d-none');
        } else if (tipe === 'pengaduan') {
            fieldsPengaduan.classList.remove('d-none');
        } else if (tipe === 'monev') {
            fieldsMonev.classList.remove('d-none');
        }
    }

    // Initial call to set correct visibility on page load
    document.addEventListener('DOMContentLoaded', toggleTipeFields);

    const capturedPreview = document.getElementById('captured-preview');
    const cameraHiddenInputs = document.getElementById('camera-hidden-inputs');

    function openCamera() {
        const width = 1200;
        const height = 800;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;
        window.open('camera.php', 'BesukSaeCamera', `width=${width},height=${height},top=${top},left=${left},menubar=no,status=no`);
    }

    function loadCapturedPhotos() {
        const photos = JSON.parse(sessionStorage.getItem('captured_photos') || '[]');
        if (photos.length > 0) {
            capturedPreview.innerHTML = ''; // Clear existing previews if any
            photos.forEach((filename) => {
                addCapturedPhotoToDOM(filename);
            });
        }
    }

    window.addCapturedPhoto = function(filename) {
        addCapturedPhotoToDOM(filename);
        // Add to session storage
        const photos = JSON.parse(sessionStorage.getItem('captured_photos') || '[]');
        photos.push(filename);
        sessionStorage.setItem('captured_photos', JSON.stringify(photos));
    };

    function addCapturedPhotoToDOM(filename) {
        const col = document.createElement('div');
        col.className = 'col-4 col-md-3 mb-2'; // Reverted to original column class
        col.innerHTML = `
            <div class="card h-100 border-0 shadow-sm position-relative">
                <img src="uploads/foto/${filename}" class="card-img-top rounded shadow-sm" style="height: 100px; object-fit: cover;">
                <div class="p-1">
                    <input type="text" name="camera_captions[]" class="form-control form-control-sm border-0 bg-light rounded-pill px-2" placeholder="Ket..." style="font-size: 10px;">
                </div>
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle" onclick="removePhoto(this, '${filename}')" style="width:24px; height:24px; padding:0; display:flex; align-items:center; justify-content:center;">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;
        capturedPreview.appendChild(col);

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'camera_photos[]';
        input.value = filename;
        input.id = `input-${filename}`;
        cameraHiddenInputs.appendChild(input);
    }

    function removePhoto(btn, filename) {
        btn.closest('.col-4').remove();
        document.getElementById(`input-${filename}`).remove();

        // Remove from session storage
        let photos = JSON.parse(sessionStorage.getItem('captured_photos') || '[]');
        photos = photos.filter(name => name !== filename);
        sessionStorage.setItem('captured_photos', JSON.stringify(photos));
    }
</script>

<?php include 'includes/footer.php'; ?>

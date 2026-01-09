<?php
// modules/disposisi/submit-report.php
$page_title = 'Kirim Laporan Disposisi';
$active_page = 'disposisi';

require_once '../../config/database.php';
require_once '../../includes/header.php';

$currentUserId = $_SESSION['user_id'] ?? null;
$followupId = $_GET['id'] ?? null;

if (!$currentUserId || !$followupId) {
    header('Location: inbox.php');
    exit;
}

// Get disposition details
$sql = "SELECT d.*, dp.status_followup, dp.id as followup_id
        FROM disposisi d 
        JOIN disposisi_penerima dp ON d.id = dp.disposisi_id 
        WHERE dp.id = ? AND dp.user_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$followupId, $currentUserId]);
$disposisi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$disposisi) {
    header('Location: inbox.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catatan = trim($_POST['catatan'] ?? '');
    $fileUrl = '';
    
    // Handle file upload
    if (isset($_FILES['laporan_file']) && $_FILES['laporan_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/laporan/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['laporan_file']['name']);
        $uploadPath = $uploadDir . $fileName;
        
        // Validate PDF only
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['laporan_file']['tmp_name']);
        finfo_close($finfo);
        
        if ($mimeType !== 'application/pdf' && !str_ends_with(strtolower($_FILES['laporan_file']['name']), '.pdf')) {
            $error = 'Hanya file PDF yang diperbolehkan';
        } else {
            if (move_uploaded_file($_FILES['laporan_file']['tmp_name'], $uploadPath)) {
                $fileUrl = 'uploads/laporan/' . $fileName;
            } else {
                $error = 'Gagal mengupload file';
            }
        }
    }
    
    // Validate
    if (!isset($error)) {
        if (empty($catatan) && empty($fileUrl)) {
            $error = 'Catatan atau file laporan wajib diisi';
        } else {
            // Update disposition with report
            $updateSql = "UPDATE disposisi_penerima 
                         SET laporan_catatan = ?, 
                             laporan_file_url = ?, 
                             status_followup = 'completed',
                             tgl_selesai = NOW()
                         WHERE id = ?";
            
            $updateStmt = $pdo->prepare($updateSql);
            if ($updateStmt->execute([$catatan, $fileUrl, $followupId])) {
                
                // 🚀 PUSH TO API: Update Central Status
                // "Docku -> API -> Camat"
                if (!empty($disposisi['uuid'])) {
                    $api_url = 'https://api.sidiksae.my.id/api/disposisi/status';
                    $api_data = [
                        'uuid_disposisi' => $disposisi['uuid'],
                        'status'         => 'SELESAI', // Or dynamic based on logic
                        'laporan'        => $catatan . ($fileUrl ? " [File: $fileUrl]" : "")
                    ];
                    
                    $ch = curl_init($api_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($api_data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'X-API-KEY: sk_test_123' // Use proper key in prod
                    ]);
                    curl_exec($ch);
                    curl_close($ch);
                }

                header('Location: inbox.php?success=1');
                exit;
            } else {
                $error = 'Gagal mengirim laporan';
            }
        }
    }
}
?>

<div class="row fade-in">
    <div class="col-md-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1">📝 Kirim Laporan</h4>
                <p class="text-muted small mb-0">Laporan penyelesaian disposisi untuk Camat</p>
            </div>
            <a href="inbox.php" class="btn btn-sm btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Info Disposisi -->
    <div class="col-md-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted fw-bold mb-2">DISPOSISI #<?= htmlspecialchars($disposisi['external_id']) ?></div>
                
                <h5 class="fw-bold text-primary mb-2"><?= htmlspecialchars($disposisi['perihal']) ?></h5>
                
                <div class="bg-light p-3 rounded-3">
                    <div class="small text-muted fw-bold mb-1">INSTRUKSI DARI CAMAT</div>
                    <p class="small mb-0"><?= nl2br(htmlspecialchars($disposisi['instruksi'])) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Laporan -->
    <div class="col-md-12">
        <form method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold text-primary mb-4">Laporan Penyelesaian</h5>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="catatan" class="form-label fw-bold">Catatan Laporan *</label>
                    <textarea 
                        name="catatan" 
                        id="catatan" 
                        class="form-control" 
                        placeholder="Tuliskan hasil tindak lanjut disposisi..."
                        required
                        rows="5"
                    ><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                    <small class="form-text text-muted">
                        Jelaskan apa yang sudah dikerjakan dan hasil yang dicapai
                    </small>
                </div>
                
                <div class="mb-4">
                    <label for="laporan_file" class="form-label fw-bold">Upload File Laporan (PDF)</label>
                    <input 
                        type="file" 
                        name="laporan_file" 
                        id="laporan_file" 
                        class="form-control"
                        accept=".pdf,application/pdf"
                    >
                    <small class="form-text text-muted">
                        Format: PDF, Ukuran maksimal: 5MB (Opsional jika catatan sudah lengkap)
                    </small>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="inbox.php" class="btn btn-outline-secondary rounded-pill">
                        <i class="bi bi-x-circle me-1"></i> Batal
                    </a>
                    
                    <button type="submit" class="btn btn-primary rounded-pill flex-grow-1">
                        <i class="bi bi-send me-1"></i> Kirim Laporan ke Camat
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

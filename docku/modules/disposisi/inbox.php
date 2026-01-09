<?php
// modules/disposisi/inbox.php
$page_title = 'Inbox Disposisi';
$active_page = 'disposisi';

require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/notification_helper.php';

// Get current user ID from session
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId) {
    header('Location: ../../login.php');
    exit;
}

// Fetch dispositions from Camat for this user (status_followup pending or in_progress)
$sql = "SELECT d.*, dp.status_followup, dp.laporan_catatan, dp.laporan_file_url, dp.id as followup_id
        FROM disposisi d 
        JOIN disposisi_penerima dp ON d.id = dp.disposisi_id 
        WHERE dp.user_id = ? 
        AND dp.status_followup IN ('pending', 'in_progress')
        ORDER BY d.tgl_disposisi DESC, d.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$currentUserId]);
$dispositions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="row fade-in">
    <div class="col-md-12 mb-4">
        <div class="d-flex justify-content-between align-items-md-center flex-column flex-md-row">
            <div class="mb-3 mb-md-0">
                <h4 class="fw-bold mb-1">📥 Inbox Disposisi</h4>
                <p class="text-muted small mb-0">Instruksi dari Camat yang perlu ditindaklanjuti</p>
            </div>
        </div>
    </div>

<?php if (empty($dispositions)): ?>
    <div class="col-12">
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 16px; color: #6c757d; opacity: 0.5;">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
            </svg>
            <h5 class="mt-3 fw-bold text-muted">Inbox Kosong</h5>
            <p class="text-muted">Tidak ada disposisi yang perlu ditindaklanjuti saat ini</p>
        </div>
    </div>
<?php else: ?>
    <div class="col-12">
        <div class="row g-3">
        <?php foreach ($dispositions as $dispo): 
            $id = $dispo['id'] ?? '-';
            $followupId = $dispo['followup_id'] ?? '-';
            $externalId = $dispo['external_id'] ?? '-';
            $perihal = $dispo['perihal'] ?? '-';
            $instruksi = $dispo['instruksi'] ?? '';
            $tglDisposisi = $dispo['tgl_disposisi'] ?? null;
            $sifat = $dispo['sifat'] ?? 'Biasa';
            $status = $dispo['status_followup'] ?? 'pending';
            
            // Status badge
            $statusBadge = '';
            if ($status === 'pending') {
                $statusBadge = '<span class="badge bg-warning">Menunggu</span>';
            } elseif ($status === 'in_progress') {
                $statusBadge = '<span class="badge bg-info">Sedang Dikerjakan</span>';
            }
            
            // Sifat badge
            $sifatClass = 'badge bg-secondary';
            if ($sifat === 'Segera') $sifatClass = 'badge bg-danger';
            elseif ($sifat === 'Penting') $sifatClass = 'badge bg-warning';
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 card-modern border-0 shadow-sm <?= $status == 'pending' ? 'border-top border-4 border-danger' : '' ?>">
                <div class="card-body">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge rounded-pill bg-light text-dark border">
                            SuratQu: #<?= htmlspecialchars($externalId) ?>
                        </span>
                        <?php echo $statusBadge; ?>
                    </div>
                    
                    <h5 class="fw-bold text-primary mb-2"><?= htmlspecialchars($perihal) ?></h5>
                    
                    <div class="mb-2">
                        <span class="<?= $sifatClass ?>"><?= htmlspecialchars($sifat) ?></span>
                    </div>
                    
                    <!-- Instruksi -->
                    <div class="bg-light p-3 rounded-3 mb-3">
                        <div class="small text-muted fw-bold mb-1">INSTRUKSI DARI CAMAT</div>
                        <p class="small mb-0 line-clamp-3"><?= nl2br(htmlspecialchars($instruksi)) ?></p>
                    </div>
                    
                    <!-- Actions -->
                    <div class="d-flex gap-2 flex-column">
                        <a href="submit-report.php?id=<?= e($followupId) ?>" class="btn btn-sm btn-primary rounded-pill">
                            <i class="bi bi-check-circle me-1"></i> Kirim Laporan
                        </a>
                        
                        <small class="text-muted text-center">
                            <i class="bi bi-clock me-1"></i> <?= date('d M Y H:i', strtotime($tglDisposisi)) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
</div>

<style>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php require_once '../../includes/footer.php'; ?>


<?php
// modules/disposisi/index.php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$page_title = 'Disposisi Masuk';
$active_page = 'disposisi';

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// require_once '../../includes/auth.php'; // Already included in header.php potentially, but safer here

require_login();
require_once '../../includes/notification_helper.php';
include '../../includes/header.php';

try {
    // Get user info for role-based filtering
    $user_id = $_SESSION['user_id'];
    $user_uuid = $_SESSION['user']['uuid'] ?? null; //  UUID from Identity
    $user_role = $_SESSION['user']['role'] ?? 'staff'; // Role from role_sync

    // Prepare Filters (Keep for potential future use or other parts of the page)
    $statusFilter = $_GET['status'] ?? 'all';
    $filterUser = $_GET['user_id'] ?? null;
    $isAdmin = ($_SESSION['role'] === 'admin'); // Still needed for admin-specific UI elements

    // Count Disposisi (Role-based + User match)
    // TEMPORARY FIX: Remove to_role filter if it causes issues, rely on dp.user_id
    $countQuery = "
        SELECT COUNT(DISTINCT d.id) as total
        FROM disposisi d
        JOIN disposisi_penerima dp ON d.uuid = dp.disposisi_uuid
        WHERE dp.user_id = :user_id
    ";
    $stmtCount = $pdo->prepare($countQuery);
    $stmtCount->execute([
        ':user_id' => $user_id
    ]);
    $totalDisposisi = $stmtCount->fetchColumn();

    // Fetch Disposisi List
    $query = "
        SELECT d.*,
               dp.status as status_penerima,
               dp.updated_at as status_updated_at,
               s.nomor_surat,
               s.perihal,
               s.asal_surat,
               s.tanggal_surat,
               u.nama as nama_penerima
        FROM disposisi d
        JOIN disposisi_penerima dp ON d.uuid = dp.disposisi_uuid
        LEFT JOIN surat s ON d.uuid_surat = s.uuid
        LEFT JOIN users u ON dp.user_id = u.id
        WHERE dp.user_id = :user_id
        ORDER BY d.created_at DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $disposisiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Users for Filter (Admin Only)
    $userList = [];
    if ($isAdmin) {
        $userList = $pdo->query("SELECT id, nama FROM users ORDER BY nama ASC")->fetchAll();
    }

    // Calculate Stats for Dashboard Cards
    $stats = ['total' => 0, 'baru' => 0, 'selesai' => 0];

    $statsQuery = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN dp.status IN ('baru', 'dibaca') THEN 1 ELSE 0 END) as baru,
            SUM(CASE WHEN dp.status = 'completed' OR dp.status = 'selesai' THEN 1 ELSE 0 END) as selesai
        FROM disposisi d
        JOIN disposisi_penerima dp ON d.uuid = dp.disposisi_uuid
        WHERE dp.user_id = :user_id
    ";
    $stmtStats = $pdo->prepare($statsQuery);
    $stmtStats->execute([
        ':user_id' => $user_id
    ]);
    $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    if ($resStats) $stats = $resStats;

} catch (PDOException $e) {
    echo "<div style='background:#fdeaea; color:#b02a37; padding:20px; border:1px solid #f5c2c7; border-radius:8px; margin:20px;'>";
    echo "<h4 style='margin-top:0;'>🛑 Database Error (Debug Mode)</h4>";
    echo "<b>Message:</b> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<b>Query:</b> Pelajari query di index.php line 25-90.<br>";
    echo "</div>";
    die();
}
?>

<div class="row fade-in">
    <div class="col-md-12 mb-4">
        <div class="d-flex justify-content-between align-items-md-center flex-column flex-md-row">
            <div class="mb-3 mb-md-0">
                <h4 class="fw-bold mb-1">📥 Kotak Masuk Disposisi</h4>
                <p class="text-muted small mb-0">Daftar perintah dan disposisi dari sistem eksternal.</p>
            </div>
            <?php if ($isAdmin): ?>
                <div class="d-flex gap-2">
                    <form action="" method="GET" class="d-flex gap-2">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                        <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Pegawai --</option>
                            <?php foreach ($userList as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Summary Section -->
    <div class="col-12 mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card-modern border-0 p-3 bg-white shadow-sm h-100">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-stack text-primary fs-4"></i>
                        </div>
                        <div>
                            <div class="text-label small">Total Disposisi</div>
                            <h4 class="fw-bold mb-0"><?= $stats['total'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-modern border-0 p-3 bg-white shadow-sm h-100 border-start border-4 border-danger">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-envelope-exclamation text-danger fs-4"></i>
                        </div>
                        <div>
                            <div class="text-label small">Belum Selesai</div>
                            <h4 class="fw-bold mb-0"><?= $stats['baru'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-modern border-0 p-3 bg-white shadow-sm h-100 border-start border-4 border-success">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        </div>
                        <div>
                            <div class="text-label small">Telah Dilaksanakan</div>
                            <h4 class="fw-bold mb-0"><?= $stats['selesai'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 mb-3">
        <!-- Filter Tabs -->
        <ul class="nav nav-pills mb-3 bg-white p-2 rounded-3 shadow-sm d-inline-flex">
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter == 'all' ? 'active' : '' ?>" href="?status=all&user_id=<?= $filterUser ?>">Semua</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter == 'baru' ? 'active' : '' ?>" href="?status=baru&user_id=<?= $filterUser ?>">
                    Baru <span class="badge bg-danger ms-1"><?= $isAdmin ? $stats['baru'] : getUnreadDispositionCount($pdo, $user_id) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter == 'dilaksanakan' ? 'active' : '' ?>" href="?status=dilaksanakan&user_id=<?= $filterUser ?>">Selesai</a>
            </li>
        </ul>
    </div>

    <div class="col-md-12">
        <?php if (count($disposisiList) > 0): ?>
            <div class="row g-3">
                <?php foreach ($disposisiList as $row): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 card-modern border-0 shadow-sm <?= $row['status_penerima'] == 'baru' ? 'border-top border-4 border-danger' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge rounded-pill bg-light text-dark border">
                                        #<?= substr($row['uuid'], 0, 8) ?>
                                    </span>
                                    <?php if($row['status_penerima'] == 'baru'): ?>
                                        <span class="badge bg-danger animate-pulse">BARU</span>
                                    <?php elseif($row['status_penerima'] == 'dibaca'): ?>
                                        <span class="badge bg-warning text-dark">DIBACA</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">SELESAI</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($row['perihal']) ?></h5>
                                <?php if ($isAdmin): ?>
                                    <div class="small text-muted mb-2 card-modern p-2 py-1 bg-light d-inline-block rounded-pill">
                                        <i class="bi bi-person-fill me-1"></i>Untuk: <strong><?= htmlspecialchars($row['nama_penerima']) ?></strong>
                                    </div>
                                <?php endif; ?>
                                <p class="text-muted small mb-3 line-clamp-2"><?= htmlspecialchars($row['instruksi'] ?? $row['catatan'] ?? '') ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i><?= date('d M H:i', strtotime($row['tgl_disposisi'] ?? $row['created_at'])) ?>
                                    </small>
                                    <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Buka Detail <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                <img src="../../assets/img/empty.svg" alt="Empty" style="width: 150px; opacity: 0.6;">
                <h5 class="mt-3 fw-bold text-muted">Tidak ada disposisi</h5>
                <p class="text-muted">Data disposisi tidak ditemukan untuk filter ini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// Only admin can access audit logs
if ($_SESSION['role'] !== 'admin') {
    redirect('index.php', 'Akses ditolak!', 'danger');
}

$title = 'Log Aktivitas Sistem';
include 'includes/header.php';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Fetch Logs with User Info
$stmt = $db->prepare("SELECT l.*, u.nama_lengkap, u.username 
                      FROM log_aktivitas l 
                      LEFT JOIN users u ON l.id_user = u.id_user 
                      ORDER BY l.waktu DESC 
                      LIMIT ?, ?");
$stmt->bindValue(1, $start, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Total for pagination
$total = $db->query("SELECT COUNT(*) FROM log_aktivitas")->fetchColumn();
$pages = ceil($total / $limit);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold mb-1">Audit Trail & Log Aktivitas 📜</h4>
            <p class="text-muted small">Rekam jejak seluruh aktivitas pengguna dalam sistem untuk keamanan data.</p>
        </div>
    </div>

    <div class="card card-custom border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase" style="font-size: 0.75rem;">
                    <tr>
                        <th class="ps-4">Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi / Kegiatan</th>
                        <th>Modul</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= date('d M Y', strtotime($log['waktu'])) ?></div>
                                    <div class="small text-muted" style="font-size: 0.75rem;"><?= date('H:i:s', strtotime($log['waktu'])) ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 10px;">
                                            <?= strtoupper(substr($log['nama_lengkap'] ?: 'SY', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold small"><?= htmlspecialchars($log['nama_lengkap'] ?: 'Sistem') ?></div>
                                            <div class="text-muted" style="font-size: 9px;">@<?= htmlspecialchars($log['username'] ?: 'system') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-medium text-primary small"><?= htmlspecialchars($log['aksi']) ?></span>
                                    <?php if ($log['id_data_terkait']): ?>
                                        <span class="badge bg-light text-dark border ms-1" style="font-size: 9px;">ID: <?= $log['id_data_terkait'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-soft-info text-info text-uppercase" style="font-size: 9px;"><?= htmlspecialchars($log['tabel_terkait'] ?: 'Umum') ?></span>
                                </td>
                                <td>
                                    <code class="small text-muted" style="font-size: 10px;"><?= htmlspecialchars($log['ip_address']) ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">Belum ada aktivitas tercatat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="card-footer bg-white border-top-0 py-3">
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm justify-content-end mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link shadow-none" href="?page=<?= $page - 1 ?>">Sebelumnya</a>
                    </li>
                    <?php for($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link shadow-none" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $pages) ? 'disabled' : '' ?>">
                        <a class="page-link shadow-none" href="?page=<?= $page + 1 ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.bg-soft-info { background-color: rgba(13, 202, 240, 0.1); }
</style>

<?php include 'includes/footer.php'; ?>

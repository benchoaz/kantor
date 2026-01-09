<?php
// users.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role(['admin']);

$active_page = 'users';
$page_title = 'Manajemen User';
include 'includes/header.php';

$stmt = $pdo->query("SELECT id, username, nama, role, jabatan, nip, created_at FROM users ORDER BY role, nama");
$users = $stmt->fetchAll();
?>

<?php
// Display sync status messages
if (isset($_GET['msg'])):
    $msg_type = $_GET['msg'];
    $msg_text = isset($_GET['txt']) ? urldecode($_GET['txt']) : '';
    
    if ($msg_type === 'synced'):
        echo '<div class="alert alert-success alert-dismissible fade show animate-up" role="alert">';
        echo '<i class="bi bi-check-circle-fill me-2"></i>';
        echo '<strong>Sinkronisasi Berhasil!</strong> ' . htmlspecialchars($msg_text);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    elseif ($msg_type === 'sync_error'):
        echo '<div class="alert alert-danger alert-dismissible fade show animate-up" role="alert">';
        echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
        echo '<strong>Sinkronisasi Gagal!</strong><br><small class="text-break">' . htmlspecialchars($msg_text) . '</small>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    endif;
endif;

// Get last sync info from log
$last_sync_info = null;
$log_file = __DIR__ . '/logs/integration.log';
if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $sync_lines = array_filter($lines, function($line) {
        return strpos($line, '[SYNC_USERS]') !== false;
    });
    if (!empty($sync_lines)) {
        $last_line = end($sync_lines);
        if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}).*Code: (\d+).*Count: (\d+)/', $last_line, $matches)) {
            $last_sync_info = [
                'time' => $matches[1],
                'code' => $matches[2],
                'count' => $matches[3],
                'success' => ($matches[2] == '200')
            ];
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-up">
    <div>
        <h3 class="title-main mb-0">Manajemen Pengguna</h3>
        <p class="text-muted small mb-0">Kelola akses dan privilese personil dalam aplikasi</p>
    </div>
    <div class="d-flex gap-2">
        <a href="sync_manual.php" class="btn btn-modern btn-outline-success shadow-sm" onclick="return confirm('Apakah Anda yakin ingin melakukan sinkronisasi ulang data user ke aplikasi Pimpinan?')">
            <i class="bi bi-arrow-repeat"></i> <span class="d-none d-md-inline ms-1">Sinkron Ke Pimpinan</span>
        </a>
        <a href="https://api.sidiksae.my.id/admin/users.php" target="_blank" class="btn btn-modern btn-secondary shadow-sm">
            <i class="bi bi-box-arrow-up-right"></i> <span class="d-none d-md-inline ms-1">Kelola User (Pusat)</span>
        </a>
    </div>
</div>

<?php if ($last_sync_info): ?>
<div class="card border-0 shadow-sm mb-3 animate-up" style="animation-delay: 0.05s; background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%)">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="d-flex align-items-center">
                <?php if ($last_sync_info['success']): ?>
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <small class="text-muted">
                        <strong class="text-success">Terakhir sync berhasil:</strong> 
                        <?= date('d M Y H:i', strtotime($last_sync_info['time'])) ?> WIB
                        · <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><?= $last_sync_info['count'] ?> user</span>
                    </small>
                <?php else: ?>
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    <small class="text-muted">
                        <strong class="text-warning">Terakhir sync gagal:</strong> 
                        <?= date('d M Y H:i', strtotime($last_sync_info['time'])) ?> WIB
                        · <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">HTTP <?= $last_sync_info['code'] ?></span>
                    </small>
                <?php endif; ?>
            </div>
            <a href="logs/integration.log" target="_blank" class="btn btn-sm btn-light border rounded-pill px-3 ms-2">
                <i class="bi bi-file-text me-1"></i> Lihat Log
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card-modern border-0 animate-up" style="animation-delay: 0.1s;">
    <div class="card-body p-0 p-lg-4">
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Informasi User</th>
                        <th>Jabatan & NIP</th>
                        <th>Role Akses</th>
                        <th class="d-none d-lg-table-cell">Terdaftar</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="ps-4" data-label="User">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-none d-lg-block">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['nama']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-at me-1"></i><?= htmlspecialchars($u['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Jabatan">
                            <div class="small fw-medium"><?= $u['jabatan'] ? htmlspecialchars($u['jabatan']) : '<em>Staff</em>' ?></div>
                            <div class="extra-small text-muted"><?= $u['nip'] ? htmlspecialchars($u['nip']) : 'NIP: -' ?></div>
                        </td>
                        <td data-label="Role">
                            <?php
                            $role_config = [
                                'admin' => ['bg-danger', 'bi-shield-lock'],
                                'operator' => ['bg-primary', 'bi-person-gear'],
                                'pimpinan' => ['bg-success', 'bi-person-check']
                            ];
                            $cfg = $role_config[$u['role']] ?? ['bg-secondary', 'bi-person'];
                            ?>
                            <span class="badge rounded-pill <?= $cfg[0] ?> bg-opacity-10 <?= str_replace('bg-', 'text-', $cfg[0]) ?> border <?= str_replace('bg-', 'border-', $cfg[0]) ?> border-opacity-25 px-3">
                                <i class="bi <?= $cfg[1] ?> me-1"></i> <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td class="d-none d-lg-table-cell">
                             <div class="small text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></div>
                        </td>
                        <td class="text-end pe-4" data-label="Aksi">
                            <div class="btn-group">
                                <a href="user_edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-3">
                                    Edit
                                </a>
                                <?php if ($u['username'] !== 'admin' && $u['id'] != $_SESSION['user_id']): ?>
                                <a href="user_hapus.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-2 ms-1 text-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

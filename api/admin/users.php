<?php
require_once __DIR__ . '/../controllers/AdminUserController.php';

$controller = new AdminUserController();
$users = $controller->getAllUsers();

// Handle Delete
if (isset($_GET['delete'])) {
    $res = $controller->deleteUser($_GET['delete']);
    $msg = $res['message'];
    $type = $res['success'] ? 'success' : 'danger';
    echo "<script>alert('$msg'); window.location='users.php';</script>";
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manajemen User (Pusat)</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="user_form.php" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Tambah User Baru
        </a>
    </div>
</div>

<div class="alert alert-info">
    <i class="fa-solid fa-info-circle me-2"></i>
    User yang dibuat di sini akan tersinkron ke aplikasi <strong>Docku</strong> dan <strong>SuratQu</strong>.
</div>

<div class="table-responsive bg-white shadow-sm p-3 rounded">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Nama Lengkap</th>
                <th>Jabatan</th>
                <th>Role</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['nama']) ?></td>
                <td><?= htmlspecialchars($u['jabatan']) ?></td>
                <td><span class="badge bg-secondary"><?= $u['role'] ?></span></td>
                <td>
                    <?php if($u['is_active']): ?>
                        <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Suspend</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="user_form.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-edit"></i>
                    </a>
                    <?php if($u['id'] != $_SESSION['admin_id']): ?>
                    <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin hapus user ini? Data di aplikasi lain mungkin terganggu.')">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>

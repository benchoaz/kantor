<?php
require_once __DIR__ . '/../controllers/AdminUserController.php';

$controller = new AdminUserController();
$controller->checkAuth();

$id = $_GET['id'] ?? null;
$user = $id ? $controller->getUser($id) : null;
$is_edit = (bool)$user;

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($is_edit) {
        $res = $controller->updateUser($id, $_POST);
    } else {
        $res = $controller->createUser($_POST);
    }
    
    $msg = $res['message'];
    $msg_type = $res['success'] ? 'success' : 'danger';
    
    if ($res['success']) {
        // Redirect after success
        echo "<script>alert('$msg'); window.location='users.php';</script>";
        exit;
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= $is_edit ? 'Edit User' : 'Tambah User Baru' ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="users.php" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<?php if($msg): ?>
<div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
<?php endif; ?>

<div class="card shadow-sm col-md-8 mx-auto">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= $user['username'] ?? '' ?>" <?= $is_edit ? 'readonly' : 'required' ?>>
                <?php if($is_edit): ?>
                    <small class="text-muted">Username tidak bisa diubah.</small>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" <?= $is_edit ? '' : 'required' ?>>
                <?php if($is_edit): ?>
                    <small class="text-muted">Kosongkan jika tidak ingin mengganti password.</small>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="<?= $user['nama'] ?? '' ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Jabatan</label>
                    <input type="text" name="jabatan" class="form-control" value="<?= $user['jabatan'] ?? '' ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="staff" <?= ($user['role'] ?? '') == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="camat" <?= ($user['role'] ?? '') == 'camat' ? 'selected' : '' ?>>Camat</option>
                        <option value="sekcam" <?= ($user['role'] ?? '') == 'sekcam' ? 'selected' : '' ?>>Sekcam</option>
                        <option value="kasubag" <?= ($user['role'] ?? '') == 'kasubag' ? 'selected' : '' ?>>Kasubag</option>
                        <option value="kasi" <?= ($user['role'] ?? '') == 'kasi' ? 'selected' : '' ?>>Kasi</option>
                        <option value="admin" <?= ($user['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin Sistem</option>
                    </select>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="activeStatus" 
                       <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="activeStatus">Akun Aktif</label>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save me-2"></i> Simpan User
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

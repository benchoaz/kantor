<?php
require_once __DIR__ . '/../controllers/AdminUserController.php';

$controller = new AdminUserController();
$controller->checkAuth();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Total User</div>
            <div class="card-body">
                <h5 class="card-title"><?= count($controller->getAllUsers()) ?> User</h5>
                <p class="card-text">Pengguna terdaftar di database.</p>
                <a href="users.php" class="btn btn-light btn-sm">Kelola User</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
             <div class="card-header">Status API</div>
             <div class="card-body">
                 <h5 class="card-title">Online</h5>
                 <p class="card-text">Sistem berjalan normal.</p>
             </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

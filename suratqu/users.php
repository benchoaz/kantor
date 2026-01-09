<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// Only admin can access this page
if ($_SESSION['role'] !== 'admin') {
    redirect('index.php', 'Anda tidak memiliki hak akses ke halaman ini.', 'danger');
}

$title = 'Manajemen Pengguna';
include 'includes/header.php';

// Fetch users with their positions
$query = "SELECT u.*, j.nama_jabatan 
          FROM users u 
          LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan 
          ORDER BY u.created_at DESC";
$stmt = $db->query($query);
$users = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-8">
            <h4 class="fw-bold mb-0">Manajemen Pengguna</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Pengguna</li>
                </ol>
            </nav>
        </div>
        <div class="col-4 text-end">
             <a href="https://api.sidiksae.my.id/admin/users.php" target="_blank" class="btn btn-secondary shadow-sm">
                <i class="fa-solid fa-users-gear me-2"></i> Kelola di Admin Pusat
            </a>
        </div>
    </div>

    <!-- Alert for Feedback -->
    <?php if (isset($_SESSION['alert'])): ?>
        <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
            <?= $_SESSION['alert']['msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <div class="card card-custom border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3">PENGGUNA</th>
                        <th>JABATAN</th>
                        <th>ROLE</th>
                        <th>STATUS</th>
                        <th class="text-end pe-4">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $row): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3">
                                            <?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= $row['nama_lengkap'] ?></div>
                                            <div class="small text-muted">@<?= $row['username'] ?> • <?= $row['nip'] ?: 'Tanpa NIP' ?> <?= $row['golongan'] ? '('.$row['golongan'].')' : '' ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-soft-primary text-primary px-3">
                                        <?= $row['nama_jabatan'] ?: 'Tidak Ada Jabatan' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small fw-bold text-uppercase"><?= $row['role'] ?></div>
                                </td>
                                <td>
                                    <?php if ($row['is_active']): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success small px-3">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger small px-3">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-light text-muted border">Managed by Pusat</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-state-3391216-2838385.png" alt="Empty" style="width: 150px;">
                                <p class="text-muted mt-3">Belum ada data pengguna.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    background: #e9ecef;
    color: #495057;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
.bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
</style>

<?php include 'includes/footer.php'; ?>

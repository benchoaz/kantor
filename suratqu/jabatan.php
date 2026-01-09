<?php
// jabatan.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// Only admin can access this module
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['alert'] = ['msg' => 'Akses ditolak!', 'type' => 'danger'];
    header("Location: index.php");
    exit;
}

include 'includes/header.php';

// Fetch Positions with Parent Name
$query = "SELECT j1.*, j2.nama_jabatan as nama_atasan 
          FROM jabatan j1 
          LEFT JOIN jabatan j2 ON j1.parent_id = j2.id_jabatan 
          ORDER BY j1.level_hierarki ASC, j1.nama_jabatan ASC";
$stmt = $db->query($query);
$positions = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-2">
    <div>
        <h2 class="fw-bold mb-1">Struktur Organisasi</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Jabatan</li>
            </ol>
        </nav>
    </div>
    <a href="jabatan_tambah.php" class="btn btn-primary">
        <i class="fa-solid fa-plus me-2"></i> Tambah Jabatan
    </a>
</div>

<div class="card card-custom p-0 overflow-hidden shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Level</th>
                    <th>Nama Jabatan</th>
                    <th>Atasan Langsung</th>
                    <th class="text-center">Buat Surat</th>
                    <th class="text-center">Disposisi</th>
                    <th class="text-center">Verifikasi</th>
                    <th class="text-center">Tanda Tangan</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($positions)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">Belum ada data jabatan.</td>
                </tr>
                <?php else: foreach ($positions as $row): ?>
                <tr>
                    <td class="ps-4">
                        <span class="badge bg-secondary rounded-pill">Level <?= $row['level_hierarki'] ?></span>
                    </td>
                    <td><div class="fw-bold"><?= htmlspecialchars($row['nama_jabatan']) ?></div></td>
                    <td><?= $row['nama_atasan'] ? htmlspecialchars($row['nama_atasan']) : '<em class="text-muted small">- Puncak Struktur -</em>' ?></td>
                    <td class="text-center">
                        <i class="fa-solid <?= $row['can_buat_surat'] ? 'fa-circle-check text-success' : 'fa-circle-xmark text-light' ?>"></i>
                    </td>
                    <td class="text-center">
                        <i class="fa-solid <?= $row['can_disposisi'] ? 'fa-circle-check text-success' : 'fa-circle-xmark text-light' ?>"></i>
                    </td>
                    <td class="text-center">
                        <i class="fa-solid <?= $row['can_verifikasi'] ? 'fa-circle-check text-success' : 'fa-circle-xmark text-light' ?>"></i>
                    </td>
                    <td class="text-center">
                        <i class="fa-solid <?= $row['can_tanda_tangan'] ? 'fa-circle-check text-success' : 'fa-circle-xmark text-light' ?>"></i>
                    </td>
                    <td class="text-center pe-4">
                        <div class="btn-group">
                            <a href="jabatan_tambah.php?id=<?= $row['id_jabatan'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button onclick="confirmDelete(<?= $row['id_jabatan'] ?>)" class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus jabatan ini? Menghapus jabatan dapat mempengaruhi data user terkait.')) {
        window.location.href = 'jabatan_proses.php?aksi=hapus&id=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>

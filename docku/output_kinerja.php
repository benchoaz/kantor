<?php
// output_kinerja.php - Admin only: Manage Output Kinerja Templates
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role(['admin']);

$page_title = 'Kelola Output Kinerja';
$active_page = 'output_kinerja';

$error = '';
$success = '';

// Handle DELETE
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM output_kinerja WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Output kinerja berhasil dihapus.";
    } else {
        $error = "Gagal menghapus output kinerja.";
    }
}

// Handle CREATE/UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $bidang_id = $_POST['bidang_id'] ?? '';
    $nama_output = $_POST['nama_output'] ?? '';
    $level_jabatan = $_POST['level_jabatan'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';

    if ($bidang_id && $nama_output && $level_jabatan) {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE output_kinerja SET bidang_id = ?, nama_output = ?, level_jabatan = ?, deskripsi = ? WHERE id = ?");
            if ($stmt->execute([$bidang_id, $nama_output, $level_jabatan, $deskripsi, $id])) {
                $success = "Output kinerja berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui output kinerja.";
            }
        } else {
            // Create
            $stmt = $pdo->prepare("INSERT INTO output_kinerja (bidang_id, nama_output, level_jabatan, deskripsi) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$bidang_id, $nama_output, $level_jabatan, $deskripsi])) {
                $success = "Output kinerja berhasil ditambahkan.";
            } else {
                $error = "Gagal menambahkan output kinerja.";
            }
        }
    } else {
        $error = "Bidang, level jabatan, dan nama output wajib diisi.";
    }
}

// Get filters
$bidang_filter = $_GET['bidang_id'] ?? '';
$jabatan_filter = $_GET['level_jabatan'] ?? '';

// Fetch data with LEFT JOIN (because bidang_id can be NULL for compliance templates)
$sql = "SELECT ok.*, b.nama_bidang 
        FROM output_kinerja ok 
        LEFT JOIN bidang b ON ok.bidang_id = b.id";
$params = [];
$where = [];

if ($bidang_filter) {
    $where[] = "ok.bidang_id = ?";
    $params[] = $bidang_filter;
}

if ($jabatan_filter) {
    $where[] = "ok.level_jabatan = ?";
    $params[] = $jabatan_filter;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// Order by level jabatan first, then by name
$sql .= " ORDER BY FIELD(ok.level_jabatan, 'staf', 'kasi', 'sekcam', 'camat'), ok.nama_output";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$outputs = $stmt->fetchAll();

// Get bidang list
$bidang_list = $pdo->query("SELECT * FROM bidang ORDER BY nama_bidang")->fetchAll();

// Get edit data if editing
$edit_data = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM output_kinerja WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-up">
    <div>
        <h3 class="fw-extrabold mb-0 text-primary" style="letter-spacing: -1px;">Kelola Output Kinerja</h3>
        <p class="text-muted small mb-0">Definisikan template output untuk laporan e-Kinerja ASN</p>
    </div>
    <button class="btn btn-modern btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#formModal" style="border-radius: 12px; padding: 10px 20px;">
        <i class="bi bi-plus-lg me-1"></i> <span class="d-none d-md-inline">Tambah Output</span>
    </button>
</div>

<?php if ($error || $success): ?>
    <div class="animate-up" style="animation-delay: 0.1s;">
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card-modern border-0 mb-4 animate-up" style="animation-delay: 0.1s;">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="text-label mb-2">Filter Bidang</label>
                <select name="bidang_id" class="form-select border-2 shadow-none">
                    <option value="">Semua Bidang</option>
                    <?php foreach ($bidang_list as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($bidang_filter == $b['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['nama_bidang']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="text-label mb-2">Filter Level Jabatan</label>
                <select name="level_jabatan" class="form-select border-2 shadow-none">
                    <option value="">Semua Level</option>
                    <option value="staf" <?= ($jabatan_filter == 'staf') ? 'selected' : '' ?>>Staf</option>
                    <option value="kasi" <?= ($jabatan_filter == 'kasi') ? 'selected' : '' ?>>Kasi / Kasubag</option>
                    <option value="sekcam" <?= ($jabatan_filter == 'sekcam') ? 'selected' : '' ?>>Sekretaris Camat</option>
                    <option value="camat" <?= ($jabatan_filter == 'camat') ? 'selected' : '' ?>>Camat</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-modern btn-light border flex-grow-1">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="output_kinerja.php" class="btn btn-modern btn-light border px-3" title="Reset">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Custom Styles for Blue Theme (Removing Sage) -->
<style>
    :root {
        --active-blue: #0d6efd;
        --active-indigo: #6610f2;
        --active-purple: #6f42c1;
        --active-cyan: #0dcaf0;
    }
    
    .table-clean {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }
    
    .table-clean thead {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        color: white;
    }
    
    .table-clean th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.05em;
        padding: 1.25rem 1rem;
        border: none;
    }
    
    .table-clean td {
        padding: 1rem;
        border-bottom: 1px solid #f1f3f5;
        background-color: white;
    }
    
    .table-clean tr:last-child td {
        border-bottom: none;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s;
        border: none;
    }
    
    .btn-edit { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
    .btn-edit:hover { background: #0d6efd; color: white; }
    
    .btn-delete { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .btn-delete:hover { background: #dc3545; color: white; }
    
    /* Override global sage primary for this page */
    .btn-primary-modern {
        background: #0d6efd !important;
        border-color: #0d6efd !important;
    }
    
    .title-blue {
        color: #0d6efd;
        font-weight: 800;
    }
</style>

<!-- Data Table / Cards -->
<div class="animate-up" style="animation-delay: 0.2s;">
    <div class="table-responsive table-clean shadow-sm">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Jabatan & Bidang</th>
                    <th>Output Kinerja</th>
                    <th class="d-none d-lg-table-cell">Deskripsi</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($outputs as $o): ?>
                    <tr>
                        <td class="ps-4">
                            <?php
                            $badge_classes = [
                                'staf' => 'bg-primary',
                                'kasi' => 'background-color: var(--active-cyan) !important;',
                                'sekcam' => 'background-color: var(--active-indigo) !important;',
                                'camat' => 'background-color: var(--active-purple) !important;'
                            ];
                            $style = $badge_classes[$o['level_jabatan'] ?? ''] ?? 'bg-secondary';
                            $class = (strpos($style, 'background-color') === false) ? $style : '';
                            $inline_style = ($class === '') ? $style : '';
                            ?>
                            <span class="badge rounded-pill <?= $class ?> px-3 mb-1 text-uppercase" style="font-size: 0.7rem; <?= $inline_style ?>">
                                <?= htmlspecialchars($o['level_jabatan'] ?: '-') ?>
                            </span>
                            <div class="fw-bold text-primary small"><?= htmlspecialchars($o['nama_bidang'] ?: 'Umum') ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($o['nama_output']) ?></div>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <div class="text-muted small" style="max-width: 300px;"><?= htmlspecialchars($o['deskripsi'] ?: '-') ?></div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="?edit=<?= $o['id'] ?>" class="action-btn btn-edit" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="?action=delete&id=<?= $o['id'] ?>" 
                                   class="action-btn btn-delete" 
                                   title="Hapus"
                                   onclick="return confirm('Hapus output kinerja ini?')">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($outputs)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 bg-white">
                            <div class="py-4">
                                <i class="bi bi-folder2-open display-4 text-light d-block mb-3"></i>
                                <p class="text-muted">Belum ada output kinerja yang terdaftar.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Form Modal -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-0 p-4 pb-0 bg-primary bg-opacity-10 rounded-top-4">
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-plus-circle-fill me-2"></i><?= $edit_data ? 'Edit' : 'Tambah' ?> Output Kinerja
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="text-label mb-2">Bidang Terkait</label>
                        <select name="bidang_id" class="form-select border-2 shadow-none" required>
                            <option value="">Pilih Bidang</option>
                            <?php foreach ($bidang_list as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($edit_data && $edit_data['bidang_id'] == $b['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['nama_bidang']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="text-label mb-2">Level Jabatan</label>
                        <select name="level_jabatan" class="form-select border-2 shadow-none" required>
                            <option value="">Pilih Level Jabatan</option>
                            <option value="staf" <?= ($edit_data && $edit_data['level_jabatan'] == 'staf') ? 'selected' : '' ?>>Staf</option>
                            <option value="kasi" <?= ($edit_data && $edit_data['level_jabatan'] == 'kasi') ? 'selected' : '' ?>>Kasi / Kasubag</option>
                            <option value="sekcam" <?= ($edit_data && $edit_data['level_jabatan'] == 'sekcam') ? 'selected' : '' ?>>Sekretaris Camat</option>
                            <option value="camat" <?= ($edit_data && $edit_data['level_jabatan'] == 'camat') ? 'selected' : '' ?>>Camat</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="text-label mb-2">Nama Output (Prefix: "terlaksananya...")</label>
                        <input type="text" name="nama_output" class="form-control border-2 shadow-none" 
                               placeholder="Contoh: terlaksananya koordinasi administrasi"
                               value="<?= $edit_data ? htmlspecialchars($edit_data['nama_output']) : '' ?>" required>
                    </div>

                    <div class="mb-0">
                        <label class="text-label mb-2">Deskripsi Tambahan</label>
                        <textarea name="deskripsi" class="form-control border-2 shadow-none" rows="3"
                                  placeholder="Keterangan opsional"><?= $edit_data ? htmlspecialchars($edit_data['deskripsi']) : '' ?></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border px-4 rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3">
                        <i class="bi bi-check-lg me-1"></i> <?= $edit_data ? 'Update' : 'Simpan' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($edit_data): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('formModal'));
        myModal.show();
    });
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// Basic Check
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

// Tambah User
if (isset($_POST['add'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $nama_lengkap = $_POST['nama_lengkap'];
    $nip = $_POST['nip'];
    $golongan = $_POST['golongan'];
    $id_jabatan = $_POST['id_jabatan'];
    $role = $_POST['role'];
    $telegram_id = $_POST['telegram_id'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($_SESSION['role'] !== 'admin') {
        redirect('index.php', 'Akses ditolak!', 'danger');
    }

    try {
        $stmt = $db->prepare("INSERT INTO users (username, password, nama_lengkap, nip, golongan, telegram_id, id_jabatan, role, is_active) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $nama_lengkap, $nip, $golongan, $telegram_id, $id_jabatan, $role, $is_active]);
        
        logActivity("Menambah pengguna baru: $username", "users", $db->lastInsertId());
        redirect('users.php', 'Pengguna berhasil ditambahkan!');
    } catch (Exception $e) {
        redirect('users_tambah.php', 'Gagal menambah pengguna: ' . $e->getMessage(), 'danger');
    }
}

// Update User
if (isset($_POST['update'])) {
    $id = $_POST['id_user'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $nip = $_POST['nip'];
    $golongan = $_POST['golongan'];
    $id_jabatan = $_POST['id_jabatan'];
    $role = $_POST['role'];
    $telegram_id = $_POST['telegram_id'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Security: Only admin can update other people or update roles/status
    if ($_SESSION['role'] !== 'admin' && $id != $_SESSION['id_user']) {
        redirect('index.php', 'Akses ditolak!', 'danger');
    }
    
    $params = [$nama_lengkap, $nip, $golongan, $telegram_id, $id_jabatan, $role, $is_active];
    $sql = "UPDATE users SET nama_lengkap = ?, nip = ?, golongan = ?, telegram_id = ?, id_jabatan = ?, role = ?, is_active = ?";
    
    // Update password if provided
    if (!empty($_POST['password'])) {
        $sql .= ", password = ?";
        $params[] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }
    
    $sql .= " WHERE id_user = ?";
    $params[] = $id;

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        logActivity("Memperbarui data pengguna ID: $id", "users", $id);
        
        if (isset($_POST['from_profile'])) {
            redirect('profil.php', 'Profil Anda berhasil diperbarui!');
        } else {
            redirect('users.php', 'Data pengguna berhasil diperbarui!');
        }
    } catch (Exception $e) {
        if (isset($_POST['from_profile'])) {
            redirect('profil.php', 'Gagal memperbarui profil: ' . $e->getMessage(), 'danger');
        } else {
            redirect('users_tambah.php?id='.$id, 'Gagal memperbarui pengguna: ' . $e->getMessage(), 'danger');
        }
    }
}

// Delete User
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Prevent self-deletion
    if ($id == $_SESSION['id_user']) {
        redirect('users.php', 'Anda tidak dapat menghapus akun Anda sendiri!', 'danger');
    }

    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id_user = ?");
        $stmt->execute([$id]);
        
        logActivity("Menghapus pengguna ID: $id", "users", $id);
        redirect('users.php', 'Pengguna berhasil dihapus!');
    } catch (Exception $e) {
        redirect('users.php', 'Gagal menghapus pengguna: ' . $e->getMessage(), 'danger');
    }
}
?>

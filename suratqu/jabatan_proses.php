<?php
// jabatan_proses.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak!");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_jabatan = $_POST['nama_jabatan'];
    $level_hierarki = $_POST['level_hierarki'];
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
    $can_buat_surat = isset($_POST['can_buat_surat']) ? 1 : 0;
    $can_disposisi = isset($_POST['can_disposisi']) ? 1 : 0;
    $can_verifikasi = isset($_POST['can_verifikasi']) ? 1 : 0;
    $can_tanda_tangan = isset($_POST['can_tanda_tangan']) ? 1 : 0;

    if (isset($_POST['simpan'])) {
        try {
            $stmt = $db->prepare("INSERT INTO jabatan (nama_jabatan, level_hierarki, parent_id, can_buat_surat, can_disposisi, can_verifikasi, can_tanda_tangan) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama_jabatan, $level_hierarki, $parent_id, $can_buat_surat, $can_disposisi, $can_verifikasi, $can_tanda_tangan]);
            
            logActivity("Menambah jabatan baru: $nama_jabatan", "jabatan", $db->lastInsertId());
            $_SESSION['alert'] = ['msg' => 'Jabatan baru berhasil ditambahkan!', 'type' => 'success'];
            header("Location: jabatan.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['alert'] = ['msg' => 'Gagal menambah jabatan: ' . $e->getMessage(), 'type' => 'danger'];
            header("Location: jabatan_tambah.php");
            exit;
        }
    }

    if (isset($_POST['update'])) {
        $id_jabatan = $_POST['id_jabatan'];
        try {
            $stmt = $db->prepare("UPDATE jabatan SET nama_jabatan = ?, level_hierarki = ?, parent_id = ?, can_buat_surat = ?, can_disposisi = ?, can_verifikasi = ?, can_tanda_tangan = ? 
                                 WHERE id_jabatan = ?");
            $stmt->execute([$nama_jabatan, $level_hierarki, $parent_id, $can_buat_surat, $can_disposisi, $can_verifikasi, $can_tanda_tangan, $id_jabatan]);
            
            logActivity("Memperbarui data jabatan: $nama_jabatan", "jabatan", $id_jabatan);
            $_SESSION['alert'] = ['msg' => 'Data jabatan berhasil diperbarui!', 'type' => 'success'];
            header("Location: jabatan.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['alert'] = ['msg' => 'Gagal memperbarui jabatan: ' . $e->getMessage(), 'type' => 'danger'];
            header("Location: jabatan_tambah.php?id=$id_jabatan");
            exit;
        }
    }
}

// DELETE Handler
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $id = $_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM jabatan WHERE id_jabatan = ?");
        $stmt->execute([$id]);
        
        logActivity("Menghapus jabatan id: $id", "jabatan", $id);
        $_SESSION['alert'] = ['msg' => 'Jabatan berhasil dihapus!', 'type' => 'success'];
        header("Location: jabatan.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['alert'] = ['msg' => 'Gagal menghapus jabatan: Ada user yang masih menggunakan jabatan ini.', 'type' => 'danger'];
        header("Location: jabatan.php");
        exit;
    }
}
?>

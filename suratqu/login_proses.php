<?php
// login_proses.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $db->prepare("SELECT u.*, j.can_verifikasi, j.can_tanda_tangan 
                              FROM users u 
                              LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan 
                              WHERE u.username = ? AND u.is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['id_jabatan'] = $user['id_jabatan'];
                $_SESSION['can_verifikasi'] = $user['can_verifikasi'] ?? 0;
                $_SESSION['can_tanda_tangan'] = $user['can_tanda_tangan'] ?? 0;

                logActivity("User Login Berhasil");
                header("Location: index.php");
                exit;
            }
        }

        $_SESSION['alert'] = ['msg' => 'Username atau Password salah!', 'type' => 'danger'];
        header("Location: login.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['alert'] = ['msg' => 'Terjadi kesalahan sistem.', 'type' => 'danger'];
        header("Location: login.php");
        exit;
    }
}
?>

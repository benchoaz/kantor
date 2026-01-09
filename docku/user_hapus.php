<?php
// user_hapus.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/integration_helper.php';
require_role(['admin']);

$id = $_GET['id'] ?? 0;

    // CENTRALIZED USER MANAGEMENT ENFORCEMENT
    die('<div style="text-align:center; padding:50px; font-family:sans-serif;">
        <h1>Akses Tidak Diizinkan</h1>
        <p>Penghapusan User sekarang terpusat di Panel Admin API.</p>
        <a href="https://api.sidiksae.my.id/admin" style="background:#dc3545; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">Ke Admin Panel Pusat</a>
        <br><br>
        <a href="users.php">Kembali</a>
    </div>');

header("Location: users.php?msg=error");
exit;
?>

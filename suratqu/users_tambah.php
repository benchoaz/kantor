<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

// CENTRALIZED USER MANAGEMENT ENFORCEMENT
die('<div style="text-align:center; padding:50px; font-family:sans-serif;">
    <h1>Akses Tidak Diizinkan</h1>
    <p>Manajemen User (Tambah/Edit) sekarang terpusat di Panel Admin API.</p>
    <a href="https://api.sidiksae.my.id/admin" style="background:#0d6efd; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">Ke Admin Panel Pusat</a>
    <br><br>
    <a href="users.php">Kembali</a>
</div>');
?>

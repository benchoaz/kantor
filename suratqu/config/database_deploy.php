<?php
/**
 * Database Configuration for SuratQu
 * Rename to database.php
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'sidiksae_suratqu');
define('DB_USER', 'sidiksae_user'); // GANTI dengan prefix user jika perlu
define('DB_PASS', 'Belajaran123');

try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $db = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Koneksi Database SuratQu Gagal.");
}

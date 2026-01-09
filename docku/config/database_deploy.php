<?php
// config/database.php
// Konfigurasi Database Docku (Pelaksana)

$host = 'localhost';
$db   = 'sidiksae_docku';
$user = 'sidiksae_user'; // GANTI dengan prefix user jika perlu (misal: beni_sidiksae_user)
$pass = 'Belajaran123';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Koneksi Database Docku Gagal: " . $e->getMessage());
}
?>

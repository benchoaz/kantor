<?php
// config/database.php
// Konfigurasi Database Docku untuk PRODUCTION (cPanel)

$host = 'localhost';
$db   = 'beni_sidiksae_api'; // Production database name with prefix
$user = 'beni_sidiksae_user'; // Production user with prefix
$pass = 'Belajaran123'; // Production password (notice no exclamation mark)
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

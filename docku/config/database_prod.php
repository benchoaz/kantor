<?php
// config/database.php - Production Config
// Fixed 500 Error on docku.sidiksae.my.id

$host = 'localhost';
$db   = 'sidiksae_docku';
$user = 'sidiksae_user';
$pass = 'Belajaran123'; // Production Password (cPanel)
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
    // Return vague error to user but log specific
    error_log("DB Connection Error: " . $e->getMessage());
    throw new \PDOException("Database connection failed", 500); 
}
?>

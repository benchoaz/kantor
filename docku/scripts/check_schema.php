<?php
// docku/scripts/check_schema.php
$host = 'localhost';
$db   = 'sidiksae_docku';
$user = 'sidiksae_user';
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
    echo "Connected to $db.\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM disposisi_penerima");
    print_r($stmt->fetchAll());
} catch (\PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

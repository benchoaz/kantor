<?php
// Local WSL Password Check
$databases = ['sidiksae_suratqu', 'sidiksae_id', 'sidiksae_docku'];
$user = 'sidiksae_user';
$pass = 'Belajaran123!'; // WSL Password
$host = 'localhost';

foreach ($databases as $db) {
    echo "Testing connection to: $db ... ";
    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        echo "SUCCESS!\n";
    } catch (PDOException $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}

<?php
$config = require __DIR__ . '/config/database.php';
echo "Host: " . $config['host'] . "\n";
echo "DB: " . $config['database'] . "\n";
echo "User: " . $config['username'] . "\n";

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    echo "Connected successfully!\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}

<?php
// id/run_rbac_migration.php
$config = require __DIR__ . '/config/database.php';

// OVERRIDE for WSL Local
$config['password'] = 'Belajaran123!'; 

$dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    echo "Connected to DB: {$config['database']}\n";
    
    $sql = file_get_contents(__DIR__ . '/rbac_schema.sql');
    
    // Split by statement if needed, but for simple schema raw exec might fail if multiple statements are restricted
    // PDO::exec handles multiple statements if driver allows, otherwise split
    $pdo->exec($sql);
    
    echo "Migration Executed Successfully!\n";
} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}

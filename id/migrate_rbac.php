<?php
// migrate_rbac.php
// Run this via browser: https://id.sidiksae.my.id/migrate_rbac.php

header('Content-Type: text/plain');

$configFile = __DIR__ . '/config/database.php';
$schemaFile = __DIR__ . '/rbac_schema.sql';

if (!file_exists($configFile)) {
    die("Error: Config file not found at $configFile");
}

if (!file_exists($schemaFile)) {
    die("Error: Schema file not found at $schemaFile");
}

$config = require $configFile;
echo "Loaded Config. Host: {$config['host']}, DB: {$config['database']}\n";

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    echo "Database Connected!\n";
    
    $sql = file_get_contents($schemaFile);
    
    // Execute Schema
    // Note: PDO::exec might fail on multiple statements if emulation is off.
    // We'll split by double newline or semicolon if needed, but typically likely OK for this simple schema
    // Better: Enable emulation for this script
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    
    $pdo->exec($sql);
    
    echo "---------------------------------------------------\n";
    echo "SUCCESS: RBAC Schema Migrated.\n";
    echo "Roles Created: Super Admin, Admin, Operator, Auditor\n";
    echo "---------------------------------------------------\n";
    echo "Please delete this file (migrate_rbac.php) after use.\n";

} catch (PDOException $e) {
    echo "ERROR: Database Error\n";
    echo $e->getMessage();
} catch (Exception $e) {
    echo "ERROR: General Error\n";
    echo $e->getMessage();
}

<?php
// id/check_rbac_status.php
header('Content-Type: text/plain');
require_once __DIR__ . '/config/database.php';

echo "--- RBAC DIAGNOSTIC ---\n";

try {
    $config = require __DIR__ . '/config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Check Roles
    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($roles) . " Roles:\n";
    foreach ($roles as $r) {
        echo "- [{$r['slug']}] {$r['name']}\n";
    }
    
    // Check Permissions
    $stmt = $pdo->query("SELECT COUNT(*) FROM permissions");
    $permCount = $stmt->fetchColumn();
    echo "\nFound $permCount Permissions.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

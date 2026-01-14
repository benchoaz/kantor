<?php
// id/debug_roles_direct.php

// Use WSL Password !
$user = 'sidiksae_user';
$pass = 'Belajaran123!';
$dsn = 'mysql:host=localhost;dbname=sidiksae_id;charset=utf8mb4';

try {
    $pdo = new PDO($dsn, $user, $pass);
    echo "Connected to DB.\n";
    
    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Roles found: " . count($roles) . "\n";
    foreach ($roles as $r) {
        echo "- [{$r['slug']}] {$r['name']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

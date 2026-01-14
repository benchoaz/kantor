<?php
// id/debug_roles.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/Role.php';

echo "--- CHECKING DATABASE CONNECTION ---\n";
try {
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "Connected.\n";
} catch (Exception $e) {
    die("Connection Failed: " . $e->getMessage());
}

echo "\n--- CHECKING ROLES TABLE ---\n";
try {
    $stmt = $db->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($roles) . "\n";
    print_r($roles);
} catch (Exception $e) {
    echo "Query Failed: " . $e->getMessage() . "\n";
}

echo "\n--- TESTING MODEL METHOD ---\n";
try {
    $roleModel = new \App\Models\Role();
    $all = $roleModel->getAll();
    echo "Model returned " . count($all) . " roles.\n";
} catch (Exception $e) {
    echo "Model Error: " . $e->getMessage() . "\n";
}

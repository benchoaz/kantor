<?php
/**
 * Test Identity Module Sync Endpoint
 * Check if /v1/sync/users is accessible
 */

header('Content-Type: text/plain');

echo "=== IDENTITY SYNC ENDPOINT TEST ===\n\n";

// Test 1: Check if SyncController exists
$controllerPath = __DIR__ . '/controllers/SyncController.php';
echo "1. SyncController.php exists: " . (file_exists($controllerPath) ? "YES ✅" : "NO ❌") . "\n";

// Test 2: Check if Route is registered
$indexPath = __DIR__ . '/index.php';
$indexContent = file_get_contents($indexPath);
echo "2. Route 'sync' registered: " . (strpos($indexContent, "case 'sync':") !== false ? "YES ✅" : "NO ❌") . "\n";

// Test 3: Check Role model dependency
$rolePath = __DIR__ . '/models/Role.php';
echo "3. Role.php exists: " . (file_exists($rolePath) ? "YES ✅" : "NO ❌") . "\n";

// Test 4: Database connection
try {
    require_once __DIR__ . '/core/Database.php';
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "4. Database connected: YES ✅\n";
} catch (Exception $e) {
    echo "4. Database connected: NO ❌ - " . $e->getMessage() . "\n";
}

// Test 5: Try to instantiate SyncController
if (file_exists($controllerPath)) {
    try {
        require_once $controllerPath;
        $controller = new \App\Controllers\SyncController();
        echo "5. SyncController instantiable: YES ✅\n";
    } catch (Exception $e) {
        echo "5. SyncController instantiable: NO ❌ - " . $e->getMessage() . "\n";
    }
} else {
    echo "5. SyncController instantiable: SKIP (file not found)\n";
}

echo "\n=== END TEST ===\n";

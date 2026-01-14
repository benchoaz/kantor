<?php
/**
 * Test Direct Sync Request to Identity Module
 * Simulates a sync request to catch actual errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== DIRECT SYNC REQUEST TEST ===\n\n";

// Simulate sync payload
$testPayload = [
    'users' => [
        [
            'username' => 'testuser',
            'password' => password_hash('test123', PASSWORD_BCRYPT),
            'full_name' => 'Test User',
            'status' => 'active'
        ]
    ]
];

// Test as POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/v1/sync/users';
$_SERVER['HTTP_X_API_KEY'] = 'sk_sync_docku_2026';
$_SERVER['HTTP_X_SOURCE_APP'] = 'test';

// Inject POST data
file_put_contents('php://input', json_encode($testPayload));

echo "Request Method: POST\n";
echo "Endpoint: /v1/sync/users\n";
echo "Payload: " . json_encode($testPayload) . "\n\n";

echo "Attempting to call SyncController...\n\n";

try {
    require_once __DIR__ . '/core/Database.php';
    require_once __DIR__ . '/core/Request.php';
    require_once __DIR__ . '/core/Response.php';
    require_once __DIR__ . '/controllers/SyncController.php';
    
    $controller = new \App\Controllers\SyncController();
    
    echo "Controller instantiated ✅\n";
    echo "Calling syncUsers()...\n\n";
    
    // Capture output
    ob_start();
    $controller->syncUsers();
    $output = ob_get_clean();
    
    echo "Response:\n";
    echo $output;
    
} catch (Exception $e) {
    echo "❌ ERROR CAUGHT:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ FATAL ERROR CAUGHT:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END TEST ===\n";

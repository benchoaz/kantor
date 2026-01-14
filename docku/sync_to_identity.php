<?php
/**
 * Docku → Identity Module Sync
 * Safe user synchronization with password preservation
 */

require_once __DIR__ . '/config/database_deploy.php';

// Identity Module Config
$identityUrl = 'https://id.sidiksae.my.id/v1/sync/users';
$identityApiKey = 'sk_sync_docku_2026';

try {
    // Use config file instead of hardcoded credentials
    if (!isset($pdo) || !$pdo) {
        die("❌ Database connection not available\n");
    }
    
    echo "=== DOCKU → IDENTITY SYNC ===\n\n";
    
    // Fetch all users except admin (include operator, pimpinan, kasi, sekcam)
    // Note: Set status='active' for all users (production DB may not have is_active column)
    $stmt = $pdo->query("
        SELECT username, password, nama as full_name, 'active' as status
        FROM users
        WHERE role NOT IN ('admin')
        AND username IS NOT NULL AND username != ''
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($users) . " users to sync.\n\n";
    
    if (empty($users)) {
        die("No users to sync.\n");
    }
    
    // Prepare payload
    $payload = json_encode(['users' => $users]);
    
    // Send to Identity Module
    $ch = curl_init($identityUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-KEY: ' . $identityApiKey,
        'X-SOURCE-APP: docku'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $result = json_decode($response, true);
        echo "✅ Sync successful!\n";
        echo "Batch ID: " . ($result['data']['batch_id'] ?? 'N/A') . "\n";
        echo "Inserted: " . ($result['data']['stats']['inserted'] ?? 0) . "\n";
        echo "Updated : " . ($result['data']['stats']['updated'] ?? 0) . "\n";
        echo "Skipped : " . ($result['data']['stats']['skipped'] ?? 0) . "\n";
        echo "Conflicts: " . ($result['data']['stats']['conflicts'] ?? 0) . "\n";
        
        if (!empty($result['data']['stats']['errors'])) {
            echo "\n⚠️ Errors:\n";
            foreach ($result['data']['stats']['errors'] as $err) {
                echo "  - {$err['username']}: {$err['error']}\n";
            }
        }
    } else {
        echo "❌ Sync failed!\n";
        echo "HTTP Code: $httpCode\n";
        echo "Error: " . ($error ?: 'No error message') . "\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
        
        // Log detailed error
        error_log("[Docku Sync Error] HTTP $httpCode: $response");
    }
    
} catch (PDOException $e) {
    die("❌ Database Error: " . $e->getMessage() . "\n");
}

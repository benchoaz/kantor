<?php
/**
 * API → Identity Module Sync (TEMPORARY - Before Migration)
 * Safe user synchronization with password preservation
 */

// ===== CONFIGURATION =====
// For Production (cPanel): Use 'Belajaran123'
// For Local (WSL): Use 'Belajaran123!'
$DB_PASSWORD = 'Belajaran123'; // Change to 'Belajaran123!' for local testing

// Database config
$apiDb = new PDO("mysql:host=localhost;dbname=sidiksae_api", "sidiksae_user", $DB_PASSWORD);

// Identity Module Config
$identityUrl = 'https://id.sidiksae.my.id/v1/sync/users';
$identityApiKey = 'sk_sync_api_2026'; // Configure this in Identity Module

try {
    echo "=== API → IDENTITY SYNC ===\n\n";
    
    // Fetch all active users from API
    // TEMPORARY: Use 'uuid' until migration to 'uuid_user'
    $stmt = $apiDb->query("
        SELECT uuid as uuid_user, username, password, nama as full_name,
               CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as status
        FROM users
        WHERE username IS NOT NULL AND username != ''
        AND uuid IS NOT NULL AND uuid != ''
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
        'X-SOURCE-APP: api'
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
        echo "Error: " . ($error ?: $response) . "\n";
    }
    
} catch (PDOException $e) {
    die("❌ Database Error: " . $e->getMessage() . "\n");
}

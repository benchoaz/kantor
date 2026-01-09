<?php
// test_api_connection_detailed.php
// Comprehensive API Connection Test for SuratQu → SidikSae

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== SuratQu → SidikSae API Connection Test ===\n\n";

// Load configuration
$configFile = __DIR__ . '/config/integration.php';
if (!file_exists($configFile)) {
    die("❌ Config file not found: $configFile\n");
}

$config = require $configFile;
$api = $config['sidiksae'];

echo "1. Configuration Loaded:\n";
echo "   Base URL: " . $api['base_url'] . "\n";
echo "   API Key: " . substr($api['api_key'], 0, 15) . "..." . "\n";
echo "   Client ID: " . $api['client_id'] . "\n";
echo "   Enabled: " . ($api['enabled'] ? 'Yes' : 'No') . "\n";
echo "   Timeout: " . $api['timeout'] . "s\n\n";

// Test 1: Basic Connectivity
echo "2. Testing Basic Connectivity...\n";
$testUrl = $api['base_url'];
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode > 0) {
    echo "   ✓ Server is reachable (HTTP $httpCode)\n\n";
} else {
    echo "   ❌ Cannot reach server: $error\n";
    die("   Please check your internet connection or API URL.\n");
}

// Test 2: API Authentication
echo "3. Testing API Authentication...\n";
$authUrl = rtrim($api['base_url'], '/') . '/v1/surat';
echo "   Endpoint: $authUrl\n";

$ch = curl_init($authUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, (int)$api['timeout']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-KEY: ' . $api['api_key'],
    'X-APP-ID: ' . $api['client_id'],
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";

if ($error) {
    echo "   ❌ cURL Error: $error\n\n";
} elseif ($httpCode == 200 || $httpCode == 201) {
    echo "   ✓ Authentication successful!\n";
    $data = json_decode($response, true);
    if ($data) {
        echo "   Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    }
} elseif ($httpCode == 401) {
    echo "   ❌ Authentication failed (401 Unauthorized)\n";
    echo "   Please check your API Key.\n\n";
} elseif ($httpCode == 403) {
    echo "   ❌ Access forbidden (403 Forbidden)\n";
    echo "   API Key may not have permission.\n\n";
} elseif ($httpCode == 404) {
    echo "   ⚠️  Endpoint not found (404). Trying alternative endpoint...\n\n";
    
    // Test alternative endpoint
    $altUrl = rtrim($api['base_url'], '/') . '/';
    echo "4. Testing Alternative Endpoint: $altUrl\n";
    
    $ch = curl_init($altUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-KEY: ' . $api['api_key'],
        'X-APP-ID: ' . $api['client_id']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   HTTP Code: $httpCode\n";
    if ($httpCode == 200) {
        echo "   ✓ API is accessible!\n";
    }
} else {
    echo "   ❌ Unexpected response: HTTP $httpCode\n";
    echo "   Response: " . substr($response, 0, 200) . "...\n\n";
}

// Test 3: Configuration Check
echo "\n5. Configuration Verification:\n";
$issues = [];

if (strpos($api['base_url'], 'https://') !== 0) {
    $issues[] = "⚠️  Base URL should use HTTPS";
}

if (strlen($api['api_key']) < 20) {
    $issues[] = "⚠️  API Key seems too short";
}

if (!isset($api['app_id'])) {
    echo "   ℹ️  No app_id in config (using client_id instead)\n";
}

if (empty($issues)) {
    echo "   ✓ Configuration looks good\n";
} else {
    foreach ($issues as $issue) {
        echo "   $issue\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nRecommendations:\n";
echo "1. Ensure API Key is correct: sk_live_suratqu_surat2026\n";
echo "2. Base URL should be: https://api.sidiksae.my.id/api\n";
echo "3. Check that API server is running\n";
echo "4. Verify firewall/network allows outgoing HTTPS connections\n";

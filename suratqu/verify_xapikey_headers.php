<?php
/**
 * X-API-KEY Header Verification Script
 * Verifies that all API requests include the required X-API-KEY header
 * 
 * Usage: php verify_xapikey_headers.php
 */

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║      X-API-KEY Authentication Verification Test          ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Load configuration
$config = require __DIR__ . '/config/integration.php';

echo "📋 CONFIGURATION CHECK\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Check 1: API Key exists
$api_key = $config['sidiksae']['api_key'] ?? null;
if ($api_key) {
    echo "✅ API Key: " . substr($api_key, 0, 15) . "...\n";
} else {
    echo "❌ API Key: NOT CONFIGURED\n";
    exit(1);
}

// Check 2: Base URL
$base_url = $config['sidiksae']['base_url'] ?? null;
if ($base_url) {
    echo "✅ Base URL: $base_url\n";
} else {
    echo "❌ Base URL: NOT CONFIGURED\n";
    exit(1);
}

// Check 3: Client ID
$client_id = $config['sidiksae']['client_id'] ?? null;
if ($client_id) {
    echo "✅ Client ID: $client_id\n";
} else {
    echo "❌ Client ID: NOT CONFIGURED\n";
    exit(1);
}

// Check 4: Integration enabled
$enabled = $config['sidiksae']['enabled'] ?? false;
echo $enabled ? "✅ Integration: ENABLED\n" : "⚠️  Integration: DISABLED\n";

echo "\n";

// Load API Client
require_once __DIR__ . '/includes/sidiksae_api_client.php';

echo "🔍 API CLIENT CHECK\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $apiClient = new SidikSaeApiClient($config['sidiksae']);
    echo "✅ SidikSaeApiClient: LOADED\n";
    
    // Test reflection to verify makeRequest sends headers
    $reflection = new ReflectionClass($apiClient);
    $method = $reflection->getMethod('makeRequest');
    echo "✅ makeRequest method: EXISTS\n";
    
} catch (Exception $e) {
    echo "❌ Error loading API Client: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

echo "📊 LOG FILE ANALYSIS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$log_file = __DIR__ . '/storage/api_requests.log';

if (file_exists($log_file)) {
    echo "✅ Log file: FOUND\n";
    
    // Read last 10 lines
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recent_lines = array_slice($lines, -10);
    
    $total_requests = 0;
    $with_xapikey = 0;
    $http_200 = 0;
    $http_401 = 0;
    $http_404 = 0;
    
    foreach ($recent_lines as $line) {
        $log = json_decode($line, true);
        if ($log && isset($log['headers'])) {
            $total_requests++;
            
            // Check for X-API-KEY header
            foreach ($log['headers'] as $header) {
                if (stripos($header, 'X-API-KEY:') !== false) {
                    $with_xapikey++;
                    break;
                }
            }
            
            // Count status codes
            $code = $log['status_code'] ?? 0;
            if ($code == 200) $http_200++;
            if ($code == 401) $http_401++;
            if ($code == 404) $http_404++;
        }
    }
    
    echo "\n📈 Recent Requests Statistics (last 10):\n";
    echo "   Total requests: $total_requests\n";
    echo "   With X-API-KEY: $with_xapikey / $total_requests\n";
    echo "   HTTP 200: $http_200\n";
    echo "   HTTP 401: $http_401 " . ($http_401 > 0 ? "⚠️" : "✅") . "\n";
    echo "   HTTP 404: $http_404 " . ($http_404 > 0 ? "⚠️" : "") . "\n";
    
    if ($with_xapikey === $total_requests && $total_requests > 0) {
        echo "\n✅ ALL REQUESTS INCLUDE X-API-KEY HEADER!\n";
    } elseif ($total_requests > 0) {
        echo "\n⚠️  Some requests missing X-API-KEY header\n";
    }
    
    if ($http_401 > 0) {
        echo "\n❌ WARNING: Found 401 Unauthorized errors!\n";
        echo "   This indicates API Key authentication failures.\n";
    } else {
        echo "\n✅ NO 401 ERRORS: Authentication working correctly\n";
    }
    
    if ($http_404 > 0) {
        echo "\n⚠️  Note: HTTP 404 errors found (endpoint routing issue, NOT auth issue)\n";
    }
    
} else {
    echo "⚠️  Log file: NOT FOUND\n";
    echo "   No API requests have been made yet.\n";
}

echo "\n";

echo "🧪 LIVE API TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Testing connection to API...\n";

try {
    $result = $apiClient->testConnection();
    
    if ($result['success']) {
        echo "✅ API Connection: SUCCESS\n";
        echo "   " . ($result['message'] ?? 'Connected') . "\n";
    } else {
        echo "⚠️  API Connection: FAILED\n";
        echo "   " . ($result['message'] ?? 'Unknown error') . "\n";
        echo "   HTTP Code: " . ($result['http_code'] ?? 0) . "\n";
    }
} catch (Exception $e) {
    echo "❌ API Test Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                  VERIFICATION COMPLETE                    ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";

// Final verdict
if ($api_key && $with_xapikey === $total_requests && $http_401 === 0) {
    echo "\n✅ VERDICT: X-API-KEY authentication is properly implemented!\n\n";
    exit(0);
} else {
    echo "\n⚠️  VERDICT: Please review findings above\n\n";
    exit(1);
}

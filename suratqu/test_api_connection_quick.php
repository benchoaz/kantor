<?php
/**
 * Quick API Connection Test (Full Flow)
 * Test koneksi ke SidikSae API dengan Auth Flow yang benar
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load config
$config = require __DIR__ . '/config/integration.php';
$api_config = $config['sidiksae'];

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║           🧪 SIDIKSAE API CONNECTION TEST (FULL FLOW)            ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Configuration:\n";
echo "─────────────────────────────────────────────────────────────────────\n";
echo "Base URL:    {$api_config['base_url']}\n";
echo "API Key:     " . substr($api_config['api_key'], 0, 10) . "..." . substr($api_config['api_key'], -5) . "\n";
echo "Client ID:   {$api_config['client_id']}\n";
echo "User ID:     {$api_config['user_id']}\n"; 
echo "\n";

// Helper function for cURL
function make_request($method, $url, $data = null, $token = null, $api_key) {
    $ch = curl_init($url);
    
    $headers = [
        'X-API-KEY: ' . $api_key,
        'Accept: application/json',
        'User-Agent: SuratQu/TestScript'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    if ($data) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    if ($method === 'POST') curl_setopt($ch, CURLOPT_POST, true);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verified working
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Verified working
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $elapsed = round((microtime(true) - $start) * 1000, 2);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    
    return [
        'code' => $http_code,
        'body' => $response,
        'error' => $error,
        'errno' => $errno,
        'time' => $elapsed
    ];
}

// ----------------------------------------------------------------------
// STEP 1: Authenticate & Get Token
// ----------------------------------------------------------------------
echo "STEP 1: Authenticating (Getting JWT Token)...\n";
$authUrl = rtrim($api_config['base_url'], '/') . '/api/v1/auth/token';
echo "  URL: $authUrl\n";

$authPayload = [
    'user_id' => $api_config['user_id'] ?? 1,
    'client_id' => $api_config['client_id'],
    'api_key' => $api_config['api_key'],
    'client_secret' => $api_config['client_secret']
];

$authRes = make_request('POST', $authUrl, $authPayload, null, $api_config['api_key']);

if ($authRes['errno']) {
    echo "  ❌ Connection Failed: " . $authRes['error'] . "\n";
    exit(1);
}

echo "  HTTP Code: " . $authRes['code'] . " (" . $authRes['time'] . "ms)\n";

$token = null;
if ($authRes['code'] == 200) {
    $body = json_decode($authRes['body'], true);
    if (isset($body['data']['token'])) {
        $token = $body['data']['token'];
        echo "  ✅ Auth Success! Token obtained.\n";
        echo "     Token: " . substr($token, 0, 20) . "...\n";
    } else {
        echo "  ❌ Auth Failed: Token not found in response.\n";
        echo "     Response: " . substr($authRes['body'], 0, 200) . "\n";
        exit(1);
    }
} else {
    echo "  ❌ Auth Failed with HTTP " . $authRes['code'] . "\n";
    echo "     Response: " . substr($authRes['body'], 0, 200) . "\n";
    exit(1);
}
echo "\n";

// ----------------------------------------------------------------------
// STEP 2: Test Push Endpoint (with Token)
// ----------------------------------------------------------------------
echo "STEP 2: Testing Push Data (with Token)...\n";
$pushUrl = rtrim($api_config['base_url'], '/') . '/api/v1/disposisi/push';
echo "  URL: $pushUrl\n";

$pushPayload = [
    'source_app' => 'suratqu',
    'external_id' => 999999,
    'surat' => [
        'nomor_agenda' => 'TEST/001/I/2026',
        'perihal' => 'Connectivity Test',
        'asal_surat' => 'System Test',
        'tanggal_surat' => date('Y-m-d')
    ],
    'pengirim' => [
        'jabatan' => 'System',
        'nama' => 'Tester'
    ],
    'link_detail' => 'https://example.com',
    'timestamp' => date('c'),
    '_test_mode' => true
];

$pushRes = make_request('POST', $pushUrl, $pushPayload, $token, $api_config['api_key']);

echo "  HTTP Code: " . $pushRes['code'] . " (" . $pushRes['time'] . "ms)\n";

if ($pushRes['code'] == 200 || $pushRes['code'] == 201) {
    echo "  ✅ Push Success!\n";
    echo "     Response: " . substr($pushRes['body'], 0, 100) . "...\n";
} elseif ($pushRes['code'] == 422 || $pushRes['code'] == 400) {
    echo "  ⚠️  Endpoint Reachable (Validation Error is OK for test data)\n";
    echo "     Response: " . substr($pushRes['body'], 0, 100) . "...\n";
} else {
    echo "  ❌ Push Failed.\n";
    echo "     Response: " . substr($pushRes['body'], 0, 200) . "\n";
}
echo "\n";

echo "RESULT: Connectivity is CONFIRMED working if Step 1 was successful.\n";


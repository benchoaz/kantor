<?php
/**
 * Test Disposition Create
 * Script to test creating a disposition
 */

define('APP_INIT', true);
require_once 'config/config.php';
require_once 'config/api.php';
require_once 'helpers/api_helper.php';

// Mock session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "\n========================================\n";
echo "TEST DISPOSITION CREATION\n";
echo "========================================\n\n";

$token = $_SESSION['api_token'] ?? null;
if (!$token) {
     echo "⚠️  Running without User Token (relying on API KEY)\n";
}

// 1. Get List of Targets
echo "1. Fetching Disposition Targets...\n";
$targetsUrl = ENDPOINT_DAFTAR_TUJUAN;
$respTargets = call_api('GET', $targetsUrl, [], $token);

if (!$respTargets['success']) {
    echo "❌ Failed to fetch targets: " . $respTargets['message'] . "\n";
    print_r($respTargets);
    exit;
}

$targets = $respTargets['data'];
echo "✅ Targets fetched: " . count($targets) . " items.\n";
if (empty($targets)) {
    echo "❌ No targets available to dispose to.\n";
    exit;
}
$firstTarget = $targets[0];
$targetId = $firstTarget['id'] ?? $firstTarget['user_id'] ?? null;
echo "   Selected Target: " . ($firstTarget['name'] ?? 'Unknown') . " (ID: $targetId)\n\n";

// 2. Prepare Disposition Data
$suratId = 99999; // Using the dummy ID seen in previous test
echo "2. Creating Disposition for Surat ID $suratId...\n";

$payload = [
    'surat_id' => $suratId,
    'tujuan' => $targetId, // Try single value directly
    'catatan' => 'Test Disposition from CLI via New API Key (Single Target)',
    'deadline' => date('Y-m-d', strtotime('+3 days')),
    'sifat_disposisi' => 'BIASA' // Or ID? Assuming string or integer. Usually there is a reference for Sifat.
];

// Checking Sifat - usually 1=Biasa, 2=Penting, 3=Segera? Or strings.
// I'll stick to 'BIASA' string or 1 if failed.
// Let's try to just send it.

// NOTE: Creating disposition usually requires a valid User Token because 'who' is sender.
// If this fails with 401, we know we need a token.
$respCreate = call_api('POST', ENDPOINT_DISPOSISI_CREATE, $payload, $token);

echo "Response Code: " . ($respCreate['http_code'] ?? '???') . "\n";
if ($respCreate['success']) {
    echo "✅ Disposition Created Successfully!\n";
    print_r($respCreate['data']);
} else {
    echo "❌ Failed to create disposition.\n";
    echo "Message: " . ($respCreate['message'] ?? '-') . "\n";
    if (isset($respCreate['errors'])) {
        print_r($respCreate['errors']);
    }
    
    // Fallback: If failed, maybe 'tujuan' should be single value?
    if ($respCreate['http_code'] == 422 || $respCreate['http_code'] == 400) {
        echo "\nRetrying with single value for 'tujuan'...\n";
        $payload['tujuan'] = $targetId;
        $respCreate2 = call_api('POST', ENDPOINT_DISPOSISI_CREATE, $payload, $token);
        if ($respCreate2['success']) {
             echo "✅ Disposition Created Successfully (Single Target)!\n";
        } else {
             echo "❌ Retry Failed.\n";
             echo "Message: " . ($respCreate2['message'] ?? '-') . "\n"; 
        }
    }
}
echo "\n========================================\n";

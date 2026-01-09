<?php
/**
 * Test Payload Structure
 * Diagnoses the correct JSON payload format for the API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/sidiksae_api_client.php';
$config = require __DIR__ . '/config/integration.php';

$client = new SidikSaeApiClient($config['sidiksae']);
$token = $client->authenticate();

if (!$token) {
    die("❌ Failed to get token. Fix auth first.\n");
}
echo "✅ Auth Token: " . substr($token, 0, 10) . "...\n\n";

// Common Data
$ep = rtrim($config['sidiksae']['base_url'], '/') . '/api/v1/disposisi/push';
$data = [
    'nomor_agenda' => 'TEST/DIAG/001',
    'nomor_surat' => '005/123/2026',
    'perihal' => 'Payload Diagnostic Test',
    'asal_surat' => 'Diagnostic Script',
    'tanggal_surat' => date('Y-m-d')
];

function test_payload($label, $payload) {
    global $client, $ep, $token;
    echo "Testing $label...\n";
    
    // We manually construct curl to ensure exact payload control
    $ch = curl_init($ep);
    $headers = [
        'Authorization: Bearer ' . $token,
        'X-API-KEY: ' . $GLOBALS['config']['sidiksae']['api_key'],
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    // Ignore ssl for diagnosis if needed, but lets keep true
    
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "  HTTP $code\n";
    echo "  Response: " . substr($resp, 0, 300) . "\n\n";
}

// 1. FLAT Structure (Currently used in App)
$payloadFlat = [
    'source_app' => 'suratqu',
    'external_id' => 1001,
    'nomor_agenda' => $data['nomor_agenda'],
    'nomor_surat' => $data['nomor_surat'],
    'perihal' => $data['perihal'],
    'asal_surat' => $data['asal_surat'],
    'tanggal_surat' => $data['tanggal_surat']
];
test_payload("FLAT Payload (Core data only)", $payloadFlat);

// 2. FLAT + scan_surat (Empty String)
$payloadFlatScan = $payloadFlat;
$payloadFlatScan['scan_surat'] = '';
test_payload("FLAT Payload + scan_surat (empty string)", $payloadFlatScan);

// 3. FLAT + scan_surat (Base64 Dummy)
$payloadFlatScanB64 = $payloadFlat;
$payloadFlatScanB64['scan_surat'] = 'data:image/jpeg;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
test_payload("FLAT Payload + scan_surat (base64)", $payloadFlatScanB64);

// 4. NESTED Structure (Old style / Test script style)
$payloadNested = [
    'source_app' => 'suratqu',
    'external_id' => 1002,
    'surat' => $data,
    'pengirim' => ['nama' => 'Tester', 'jabatan' => 'System']
];
test_payload("NESTED Payload", $payloadNested);

// 5. NESTED + scan_surat inside 'surat'
$payloadNestedScan = $payloadNested;
$payloadNestedScan['surat']['scan_surat'] = '';
test_payload("NESTED Payload + scan_surat", $payloadNestedScan);


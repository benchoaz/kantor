<?php
// test_c2_integration.php
// Script to simulate SuratQu upload and API integration (Step C2)

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/sidiksae_api_client.php';

echo "🔍 TEST C2: SURATQU CLIENT INTEGRATION\n";
echo "=====================================\n";

// 1. Load Configuration
$config = require 'config/integration.php';
if (!isset($config['sidiksae']['enabled']) || !$config['sidiksae']['enabled']) {
    die("❌ API Integration is DISABLED in config/integration.php\n");
}
echo "✅ API Config Loaded: " . $config['sidiksae']['base_url'] . "\n";

// 2. Prepare Mock Data (Surat Final)
$uuid = 'test-c2-' . time();
$payload = [
    'uuid_surat' => $uuid,
    'nomor_surat' => 'TEST/C2/001',
    'tanggal_surat' => date('Y-m-d'),
    'pengirim' => 'Unit Testing Bot',
    'perihal' => 'Pengujian Integrasi Step C2',
    'file_pdf' => 'https://suratqu.sidiksae.my.id/storage/test/dummy.pdf', // Public URL
    'file_hash' => hash('sha256', 'dummy'),
    'file_size' => 1024
];

echo "📝 Registering Surat: $uuid\n";

// 3. Test API Client Directly
try {
    $client = new SidikSaeApiClient($config['sidiksae']);
    $response = $client->registerSurat($payload);

    echo "📡 API Response:\n";
    print_r($response);

    if ($response['success']) {
        echo "✅ API Integration SUCCESS!\n";
        
        if (isset($response['http_code']) && $response['http_code'] == 200) {
             echo "ℹ️ Note: Idempotent success (already existed)\n";
        } elseif (isset($response['http_code']) && $response['http_code'] == 201) {
             echo "🎉 Created NEW record on Event Store\n";
        }
        
    } else {
        echo "❌ API Integration FAILED: " . $response['message'] . "\n";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
?>

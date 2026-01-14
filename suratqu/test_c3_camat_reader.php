<?php
// test_c3_camat_reader.php
require_once 'includes/sidiksae_api_client.php';

echo "🔍 TEST C3: CAMAT READER (API CONSUMPTION)\n";
echo "========================================\n";

// 1. Mock Configuration & Session
$config = require 'config/integration.php';
$role = 'camat'; 

echo "👤 Simulating Role: " . strtoupper($role) . "\n";
echo "🔗 API Target: " . $config['sidiksae']['base_url'] . "/api/pimpinan/surat-masuk\n";

// 2. Logic Condition Check
$is_pimpinan_mode = in_array($role, ['camat', 'pimpinan', 'sekcam']);
if (!$is_pimpinan_mode) {
    die("❌ Logic Error: Role '$role' should trigger Pimpinan Mode\n");
}
echo "✅ Logic Check: Pimpinan Mode Active\n";

// 3. Perform Fetch
try {
    $client = new SidikSaeApiClient($config['sidiksae']);
    
    echo "📡 Fetching data from API...\n";
    $start = microtime(true);
    $res = $client->getSuratMasuk(['limit' => 5]);
    $duration = round((microtime(true) - $start) * 1000, 2);
    
    echo "⏱️ Response Time: {$duration}ms\n";

    if ($res['success']) {
        echo "✅ API Success (HTTP " . $res['http_code'] . ")\n";
        $items = $res['data']['items'] ?? [];
        echo "📄 Records Found: " . count($items) . "\n\n";
        
        // 4. Validate Data Mapping
        if (count($items) > 0) {
            $item = $items[0];
            echo "🔎 Sample Data Inspection:\n";
            echo "   - UUID: " . $item['uuid'] . "\n";
            echo "   - Nomor: " . $item['nomor_surat'] . "\n";
            echo "   - Scan URL: " . $item['scan_surat'] . "\n";
            echo "   - Status: " . $item['status'] . "\n";
            
            // Validate URL format
            if (filter_var($item['scan_surat'], FILTER_VALIDATE_URL)) {
                 echo "✅ Scan URL is valid\n";
            } else {
                 echo "⚠️ Scan URL might be invalid format\n";
            }
        } else {
            echo "⚠️ No data found (Empty List). Check if any finalized surat exists in API.\n";
        }
    } else {
        echo "❌ API Failed: " . $res['message'] . "\n";
        print_r($res);
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
?>

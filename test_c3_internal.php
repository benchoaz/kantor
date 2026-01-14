<?php
// test_c3_internal.php
// Directly test SuratController to bypass web server/DNS lag

// Mock Environment BEFORE require
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_X_API_KEY'] = 'sk_live_suratqu_surat2026';
$_SERVER['REQUEST_URI'] = '/api/pimpinan/surat-masuk'; // Needed for parsing
$_GET['limit'] = 5;

// Directly test SuratController to bypass web server/DNS lag
require_once 'api/controllers/SuratController.php';

echo "🔍 INTERNAL TEST C3: SuratController Direct Access\n";

// Buffer output because Controller echoes JSON
ob_start();
$controller = new SuratController();
$controller->listForPimpinan();
$output = ob_get_clean();

$data = json_decode($output, true);

if ($data['success']) {
    echo "✅ Success! HTTP " . http_response_code() . "\n";
    echo "📄 Items: " . count($data['data']['items']) . "\n";
    if (count($data['data']['items']) > 0) {
        $item = $data['data']['items'][0];
        echo "   - UUID: " . $item['uuid'] . "\n";
        echo "   - Status: " . $item['status'] . "\n";
    }
} else {
    echo "❌ Failed! HTTP " . http_response_code() . "\n";
    echo "   Msg: " . $data['message'] . "\n";
}
?>

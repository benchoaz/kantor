<?php
// test_c4_full_check.php
// SIMULASI Client Camat ke API
// Untuk mendiagnosa "Database Error" atau "Endpoint Not Found"

echo "🔍 DIAGNOSTIC START...\n";

$url = 'https://api.sidiksae.my.id/api/pimpinan/surat-masuk';
$apiKey = 'sk_live_camat_c4m4t2026'; // Key resmi Camat

echo "Testing URL: $url\n";
echo "Using Key: $apiKey\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-KEY: $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_VERBOSE, true); // Debugging

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\n---------------------------------------------------\n";
echo "HTTP Status: $httpCode\n";
echo "Raw Response:\n$response\n";
echo "---------------------------------------------------\n";

if ($httpCode == 200) {
    echo "✅ SUCCESS! Endpoint Accessible.\n";
    $json = json_decode($response, true);
    if ($json) {
        echo "✅ JSON Valid.\n";
        print_r($json);
    } else {
        echo "❌ JSON Invalid (This causes 'Database Error' in Client).\n";
    }
} else {
    echo "❌ FAILED! Endpoint issue.\n";
}
?>

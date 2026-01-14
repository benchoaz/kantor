<?php
// docku/debug_500.php
// Debug 500 Error by showing Raw Response

$apiBaseUrl = 'https://api.sidiksae.my.id/api';
$apiKey = 'sk_live_camat_c4m4t2026';
$uuid = '0f7212b1-ec89-11f0-9d22-ea2d3165cda0'; // Beni Trisna

echo "=== 🕵️ DEBUG 500 ERROR ===\n";
echo "Target: $apiBaseUrl/disposisi/penerima/$uuid\n\n";

$endpoint = "$apiBaseUrl/disposisi/penerima/$uuid?api_key=$apiKey";
        
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-KEY: $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";
echo "Response Body:\n";
echo "--------------------------------------------------\n";
echo $response;
echo "\n--------------------------------------------------\n";

if ($httpCode == 500) {
    echo "\n⚠️ ANALYSIS:\n";
    if (strpos($response, 'Statement could not be executed') !== false) {
        echo "-> SQL Error. Check column names.\n";
    }
    if (strpos($response, 'failed to open stream') !== false) {
        echo "-> Missing File Error. Likely core/ files missing.\n";
    }
}

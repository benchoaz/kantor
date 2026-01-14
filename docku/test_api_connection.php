<?php
// docku/test_api_connection.php
// Debug script to check API connectivity and Auth

$apiKey = 'sk_live_camat_c4m4t2026';
$uuid   = '5a8c63bc-0a1b-56e5-8ee0-de4c3b1786fe'; // Sekcam UUID
$endpoints = [
    'https://api.sidiksae.my.id/api/health',
    'https://api.sidiksae.my.id/api/disposisi/penerima/' . $uuid
];

header('Content-Type: text/plain');
echo "=== 🕵️ API CONNECTION DIAGNOSTIC ===\n\n";

foreach ($endpoints as $url) {
    echo "------------------------------------------------\n";
    echo "Target: $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-KEY: $apiKey",
        "Accept: application/json",
        "User-Agent: DockuSync/1.0"
    ]);
    curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in output
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        echo "❌ CURL Error: " . curl_error($ch) . "\n";
    } else {
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        echo "HTTP Status: $httpCode\n";
        echo "Response Headers:\n$header\n";
        
        // Try to decode body if JSON
        $json = json_decode($body, true);
        if ($json) {
            echo "Body (JSON):\n" . json_encode($json, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Body (Raw):\n" . substr($body, 0, 500) . "...\n";
        }
    }
    
    echo "\nVerbose Log:\n";
    rewind($verbose);
    echo stream_get_contents($verbose);
    echo "\n";
    
    curl_close($ch);
}

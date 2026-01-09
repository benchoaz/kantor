<?php
/**
 * Test API SidikSae dari Server
 */

echo "<h2>🧪 Test SidikSae API dari Server</h2>";
echo "<hr>";

// Test 1: Health Check
echo "<h3>1️⃣ Test Health Check</h3>";
$ch = curl_init('https://api.sidiksae.my.id/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode == 200) {
    echo "✅ <strong style='color: green'>SUKSES!</strong><br>";
    echo "HTTP Code: $httpCode<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "❌ <strong style='color: red'>GAGAL!</strong><br>";
    echo "HTTP Code: $httpCode<br>";
    echo "Error: $error<br>";
}

echo "<hr>";

// Test 2: Authentication
echo "<h3>2️⃣ Test Authentication</h3>";

$authData = [
    'user_id' => 1,
    'client_id' => 'suratqu',
    'api_key' => 'sk_live_suratqu_a1b2c3d4e5f6g7h8',
    'client_secret' => 'suratqu_secret_2026'
];

$ch = curl_init('https://api.sidiksae.my.id/api/v1/auth/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-KEY: ' . $authData['api_key']
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($authData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";

if ($httpCode == 201) {
    $result = json_decode($response, true);
    if (isset($result['data']['token'])) {
        echo "✅ <strong style='color: green'>AUTENTIKASI BERHASIL!</strong><br>";
        echo "Token: " . substr($result['data']['token'], 0, 50) . "...<br>";
    } else {
        echo "⚠️ Response tidak mengandung token<br>";
        echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "❌ <strong style='color: red'>AUTENTIKASI GAGAL!</strong><br>";
    echo "Error: $error<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<hr>";

// Test 3: cURL Extension Check
echo "<h3>3️⃣ Server Info</h3>";
echo "cURL Extension: " . (function_exists('curl_version') ? '✅ Installed' : '❌ Not Installed') . "<br>";
if (function_exists('curl_version')) {
    $ver = curl_version();
    echo "cURL Version: " . $ver['version'] . "<br>";
    echo "SSL Version: " . $ver['ssl_version'] . "<br>";
}
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Time: " . date('Y-m-d H:i:s') . "<br>";

?>

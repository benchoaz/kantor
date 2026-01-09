<?php
// debug_login.php

define('APP_INIT', true);

require_once 'config/config.php';

echo "DEBUGGING LOGIN ENDPOINT - ROUND 6 (INVALID KEY PROBE)\n";
echo "Goal: Determine if server crashes BEFORE or AFTER auth check.\n";
echo "Target URL: " . API_BASE_URL . '/auth/login' . "\n\n";

$payload = [
    'username' => 'pakcamat',
    'password' => 'password_placeholder'
];

echo "--------------------------------------------------\n";
echo "TESTING: INVALID API KEY\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, API_BASE_URL . '/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-API-KEY: INVALID_KEY_TEST_12345',
    'X-CLIENT-ID: ' . CLIENT_ID
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP CODE: $httpCode\n";
echo "RESPONSE: " . substr($response, 0, 500) . "...\n\n";

if ($httpCode == 401) {
    echo "CONCLUSION: Server is alive! The 500 error only happens when we provide VALID credentials.\n";
    echo "ACTION: The issue is likely in the Code Logic or Database Query inside the Login Controller.\n";
} elseif ($httpCode == 500) {
    echo "CONCLUSION: Server crashes even with invalid key.\n";
    echo "ACTION: The issue is likely Global (Bootstrap, Config, Database Connection) or in the Auth Middleware itself.\n";
}

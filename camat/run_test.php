<?php
define('APP_INIT', true);
require_once 'config/config.php';
require_once 'includes/api_client.php';

$api = new ApiClient();
$response = $api->login('pimpinan', 'pimpinan123'); // Assuming default credentials from history if any

if ($response['success']) {
    echo "Login Success! Token: " . $_SESSION['api_token'] . "\n";
    // Now trigger the test logic
    require 'test_api_endpoints.php';
} else {
    echo "Login Failed: " . ($response['message'] ?? 'Unknown error') . "\n";
    print_r($response);
}

<?php
// scripts/test_token_refresh.php

echo "--- Testing Token Login & Refresh (Two-Process Multi-Step) ---\n";

// 1. LOGIN
$loginCmd = "DB_ID_PASS='Belajaran123!' DB_ID_NAME='sidiksae_id' php -r '
require_once \"scripts/test_bootstrap.php\";
\$_SERVER[\"REMOTE_ADDR\"] = \"127.0.0.1\";
\$_SERVER[\"HTTP_X_APP_ID\"] = \"api_gateway\";
\$_SERVER[\"HTTP_X_APP_KEY\"] = \"sk_live_api_gateway_2026\";
\$_REQUEST[\"username\"] = \"admin_demo\";
\$_REQUEST[\"password\"] = \"Password123!\";
\$controller = new \App\Controllers\AuthController();
\$controller->login();
'";

$loginOutput = shell_exec($loginCmd);
$loginResult = json_decode($loginOutput, true);

if (!isset($loginResult['data']['refresh_token'])) {
    echo "FAIL: Could not login or get refresh token.\n";
    echo "Output: $loginOutput\n";
    exit(1);
}

$refreshToken = $loginResult['data']['refresh_token'];
echo "SUCCESS: Logged in. Refresh Token: $refreshToken\n";

// 2. REFRESH
$refreshCmd = "DB_ID_PASS='Belajaran123!' DB_ID_NAME='sidiksae_id' php -r '
require_once \"scripts/test_bootstrap.php\";
\$_SERVER[\"REMOTE_ADDR\"] = \"127.0.0.1\";
\$_REQUEST[\"refresh_token\"] = \"$refreshToken\";
\$controller = new \App\Controllers\AuthController();
\$controller->refresh();
'";

$refreshOutput = shell_exec($refreshCmd);
$refreshResult = json_decode($refreshOutput, true);

if (isset($refreshResult['data']['access_token'])) {
    echo "SUCCESS: Refreshed Access Token: " . $refreshResult['data']['access_token'] . "\n";
    echo "SUCCESS: New Refresh Token: " . $refreshResult['data']['refresh_token'] . "\n";
} else {
    echo "FAIL: Could not refresh token.\n";
    echo "Output: $refreshOutput\n";
    exit(1);
}

<?php
// test_api_push.php - Simple test to verify API integration
require_once 'config/database.php';
require_once 'includes/integrasi_sistem_handler.php';

// Get the latest disposition ID
$stmt = $db->query("SELECT id_disposisi FROM disposisi ORDER BY id_disposisi DESC LIMIT 1");
$latest_id = $stmt->fetchColumn();

if (!$latest_id) {
    die("No dispositions found in database.");
}

echo "<h3>Testing API Push for Disposition ID: $latest_id</h3>";
echo "<pre>";

// Check if function exists
echo "Function exists: " . (function_exists('pushDisposisiToSidikSae') ? "YES" : "NO") . "\n";

// Check config
$config = require 'config/integration.php';
echo "API Enabled: " . ($config['sidiksae']['enabled'] ? "YES" : "NO") . "\n";
echo "API URL: " . $config['sidiksae']['base_url'] . "\n";

// Try to push
echo "\n--- Attempting Push ---\n";
$result = pushDisposisiToSidikSae($db, $latest_id);

echo "Result:\n";
print_r($result);

echo "</pre>";
?>

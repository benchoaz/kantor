<?php
// tools/test_sync_payload.php
require_once 'config/database.php';
require_once 'includes/integration_helper.php';

echo "--- Testing User Sync Payload ---\n";

// 1. Get Users
$stmtUser = $pdo->query("SELECT nama, jabatan, role FROM users LIMIT 3");
$users = $stmtUser->fetchAll(PDO::FETCH_ASSOC);

echo "Users to sync: " . count($users) . "\n";
print_r($users);

// 2. Mocking Config for Test (if needed) or using real one
$stmt = $pdo->prepare("SELECT outbound_url, outbound_key FROM integrasi_config WHERE label = 'SidikSae'");
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    die("Error: Config 'SidikSae' not found.\n");
}

echo "Target URL: " . rtrim($config['outbound_url'], '/') . "/users/sync\n";
echo "API Key: " . (empty($config['outbound_key']) ? "MISSING" : "PRESENT") . "\n";

echo "\n--- Attempting Real Sync (Dry Run simulation not possible easily without modifying helper) ---\n";
echo "Triggering syncUsersToCamas...\n";

$result = syncUsersToCamas($pdo);

echo "Result:\n";
print_r($result);
echo "Check logs/integration.log for details.\n";

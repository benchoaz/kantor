<?php
// api/insert_apikey.php
require_once __DIR__ . '/config/database.php';

echo "🔑 INTIALIZING API KEY...\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Create table if not exists (just in case)
    $conn->exec("CREATE TABLE IF NOT EXISTS api_clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(100),
        api_key VARCHAR(100) UNIQUE,
        is_active TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $conn->prepare("INSERT IGNORE INTO api_clients (client_name, api_key) VALUES ('suratqu_test', 'sk_live_suratqu_surat2026')");
    $stmt->execute();
    
    echo "✅ API Key 'sk_live_suratqu_surat2026' inserted/verified.\n";

} catch (PDOException $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}
?>

<?php
/**
 * SETUP QUARANTINE TABLE (WEB VERSION)
 * Run this by opening: https://docku.sidiksae.my.id/setup_quarantine_web.php?key=StartSync2026
 */

require_once 'config/database.php';

// Security Check
$key = $_GET['key'] ?? '';
if ($key !== 'StartSync2026') {
    die("⛔ Access Denied.");
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== 🛡️ DATABASE SETUP: SYNC QUARANTINE ===\n\n";

try {
    $sql = "CREATE TABLE IF NOT EXISTS sync_quarantine (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(50) NOT NULL,
        reason VARCHAR(255) NOT NULL,
        payload JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "✅ SUCCESS: Table 'sync_quarantine' is ready.\n";
    echo "💡 You can now proceed to run the sync script.";
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

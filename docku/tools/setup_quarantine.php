<?php
require_once __DIR__ . '/../config/database.php';

echo "=== DATABASE MAINTENANCE: CREATE QUARANTINE TABLE ===\n";

try {
    $sql = "CREATE TABLE IF NOT EXISTS sync_quarantine (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(50) NOT NULL,
        reason VARCHAR(255) NOT NULL,
        payload JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "✅ Table 'sync_quarantine' created successfully.\n";
} catch (PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
}

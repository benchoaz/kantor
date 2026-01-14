<?php
/**
 * MAINTENANCE: RESET MIRROR TABLES
 * This script safely clears Docku mirror tables to allow a clean sync.
 * WARNING: Use with caution.
 */

require_once __DIR__ . '/../config/database.php';

echo "=== MAINTENANCE: RESET MIRROR TABLES ===\n";

if (php_sapi_name() !== 'cli') {
    die("❌ This script can only be run via CLI for safety.\n");
}

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    echo "Truncating 'disposisi_penerima'...\n";
    $pdo->exec("TRUNCATE TABLE disposisi_penerima;");
    
    echo "Truncating 'disposisi'...\n";
    $pdo->exec("TRUNCATE TABLE disposisi;");
    
    echo "Truncating 'surat'...\n";
    $pdo->exec("TRUNCATE TABLE surat;");
    
    echo "Truncating 'sync_quarantine'...\n";
    // $pdo->exec("TRUNCATE TABLE sync_quarantine;"); // Keep quarantine logs?
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "✅ Reset complete. Mirror tables are now empty.\n";
    echo "💡 You can now run 'php scripts/sync_disposisi_final.php' to rebuild the data.\n";
    
} catch (PDOException $e) {
    echo "❌ Error during reset: " . $e->getMessage() . "\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
}

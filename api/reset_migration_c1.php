<?php
// api/reset_migration_c1.php
require_once __DIR__ . '/config/database.php';

echo "♻️ RESETTING Migration: Step C1 Surat Schema\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // 1. DROP
    $conn->exec("DROP TABLE IF EXISTS surat");
    echo "🗑️ Table 'surat' dropped.\n";
    
    // 2. CREATE
    $sql = file_get_contents(__DIR__ . '/migrations/step_c1_surat_schema.sql');
    if (!$sql) die("❌ Failed to read migration file.\n");
    
    $conn->exec($sql);
    echo "✅ Table 'surat' recreated successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Reset Failed: " . $e->getMessage() . "\n";
}
?>

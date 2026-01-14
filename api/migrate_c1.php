<?php
// api/migrate_c1.php
require_once __DIR__ . '/config/database.php';

echo "🚀 Running Migration: Step C1 Surat Schema\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = file_get_contents(__DIR__ . '/migrations/step_c1_surat_schema.sql');
    
    if (!$sql) {
        die("❌ Failed to read migration file.\n");
    }
    
    $conn->exec($sql);
    echo "✅ Migration executed successfully!\n";
    echo "   Table 'surat' created/verified.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
}
?>

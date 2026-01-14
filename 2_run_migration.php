<?php
// api/migrate_c4.php
require_once __DIR__ . '/config/database.php';

echo "🚀 Running Migration: Step C4 Disposisi Schema\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = file_get_contents(__DIR__ . '/migrations/step_c4_disposisi_schema.sql');
    
    if (!$sql) {
        die("❌ Failed to read migration file.\n");
    }
    
    // Split by semicolons simple check
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $conn->exec($stmt);
        }
    }
    
    echo "✅ Migration executed successfully!\n";
    echo "   Tables 'disposisi', 'disposisi_penerima', etc created.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
}
?>

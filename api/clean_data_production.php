<?php
// api/clean_data_production.php
// Force clean invalid disposition data in production master database

require_once 'config/database.php';
require_once 'core/Response.php';

echo "=== 🧹 MASTER API DATA CLEANER ===\n\n";

$db = new Database();
$conn = $db->getConnection();

if (!$conn) die("Connection Failed.");

try {
    // 1. Delete rows from disposisi_penerima where parent disposisi has NULL/Empty UUID
    echo "1. Cleaning disposisi_penerima...\n";
    $sql1 = "DELETE FROM disposisi_penerima WHERE disposisi_uuid NOT IN (SELECT uuid FROM disposisi WHERE uuid IS NOT NULL AND uuid != '')";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute();
    echo "   ✅ Rows deleted: " . $stmt1->rowCount() . "\n";

    // 2. Delete rows from disposisi where uuid_surat is NULL or uuid is NULL
    echo "2. Cleaning disposisi...\n";
    $sql2 = "DELETE FROM disposisi WHERE uuid IS NULL OR uuid = '' OR uuid_surat IS NULL OR uuid_surat = ''";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute();
    echo "   ✅ Rows deleted: " . $stmt2->rowCount() . "\n";

    // 3. Optional: Clean orphan instructions
    echo "3. Cleaning orphaned instructions...\n";
    $sql3 = "DELETE FROM instruksi WHERE disposisi_uuid NOT IN (SELECT uuid FROM disposisi)";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->execute();
    echo "   ✅ Rows deleted: " . $stmt3->rowCount() . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✨ Master API Data is now CLEAN for production.";

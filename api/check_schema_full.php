<?php
require_once __DIR__ . '/config/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in sidiksae_api:\n";
foreach ($tables as $t) {
    echo "- $t\n";
    // DESCRIBE
    $stmt2 = $conn->query("DESCRIBE $t");
    $cols = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    echo "  Columns: " . implode(", ", $cols) . "\n";
}

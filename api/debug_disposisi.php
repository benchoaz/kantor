<?php
require_once 'config/database.php';
require_once 'core/Response.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: text/plain');

echo "=== API DISPOSISI DIAGNOSTIC ===\n\n";

try {
    // 1. Check Table Structure
    echo "[1] Table Structure: disposisi\n";
    $stmt = $conn->query("DESCRIBE disposisi");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("    %-20s | %-20s | %-5s\n", $row['Field'], $row['Type'], $row['Null']);
    }

    echo "\n[2] Latest 10 Data in disposisi table\n";
    $stmt = $conn->query("SELECT id, uuid, uuid_surat, catatan, created_at FROM disposisi ORDER BY created_at DESC LIMIT 10");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        echo "    (Table is empty)\n";
    } else {
        foreach ($data as $row) {
            printf("    ID: %-5d | UUID: %-36s | SURAT_UUID: %-36s | DATE: %s\n", 
                $row['id'], 
                $row['uuid'] ?? 'NULL', 
                $row['uuid_surat'] ?? 'NULL',
                $row['created_at']
            );
        }
    }

    echo "\n[3] Check Disposisi Penerima\n";
    $stmt = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN disposisi_uuid IS NULL THEN 1 ELSE 0 END) as missing_uuid FROM disposisi_penerima");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "    Total Penerima: " . $stats['total'] . "\n";
    echo "    Missing UUID in Penerima: " . $stats['missing_uuid'] . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

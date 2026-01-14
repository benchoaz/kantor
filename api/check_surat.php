<?php
/**
 * Simple Surat Check
 */

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "Checking surat table...\n\n";

// Simple query without source_app
$stmt = $conn->query("SELECT COUNT(*) as total FROM surat");
$total = $stmt->fetchColumn();
echo "Total surat: $total\n\n";

if ($total > 0) {
    $stmt = $conn->query("SELECT uuid, file_path, created_at FROM surat ORDER BY created_at DESC LIMIT 3");
    
    echo "Recent surat:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- UUID: {$row['uuid']}\n";
        echo "  File: {$row['file_path']}\n";
        echo "  Created: {$row['created_at']}\n\n";
    }
} else {
    echo "❌ No surat found!\n";
}

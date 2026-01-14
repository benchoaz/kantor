<?php
// api/insert_dummy_c2.php
require_once __DIR__ . '/config/database.php';

echo "🛠️ Inserting Dummy Data for C3 Verification...\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $uuid = 'dummy-c3-reader-' . time();
    $sql = "INSERT INTO surat (uuid, nomor_surat, tanggal_surat, pengirim, perihal, scan_surat, is_final, source_app, status, created_at) 
            VALUES (:uuid, 'TEST/C3/READER', CURDATE(), 'System Bot', 'Cek Integrasi Reader C3', 'https://example.com/scan.pdf', 1, 'suratqu', 'FINAL', NOW())";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':uuid' => $uuid]);
    
    echo "✅ Dummy Data Inserted! UUID: $uuid\n";

} catch (PDOException $e) {
    echo "❌ Insert Failed: " . $e->getMessage() . "\n";
}
?>

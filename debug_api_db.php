<?php
// debug_api_db.php
// Diagnostic script to find the SQL error in SuratController

require_once 'api/config/database.php';

echo "🔍 DEBUG API DATABASE...\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Exact query from SuratController::listForPimpinan
    $query = "
        SELECT 
            uuid,
            nomor_surat,
            tanggal_surat,
            pengirim as asal_surat,
            perihal,
            scan_surat,
            status,
            source_app,
            created_at
        FROM surat
        WHERE is_final = 1
        ORDER BY created_at DESC LIMIT 20 OFFSET 0
    ";
    
    echo "Testing Query:\n$query\n";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ SUCCESS! Found " . count($items) . " items.\n";
    print_r($items[0] ?? "No items found");

} catch (PDOException $e) {
    echo "❌ SQL ERROR: " . $e->getMessage() . "\n";
}
?>

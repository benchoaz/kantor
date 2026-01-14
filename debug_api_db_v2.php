<?php
// debug_api_db_v2.php
// Strict simulation of SuratController logic

require_once 'api/config/database.php';

echo "🔍 DEBUG API DATABASE V2 (STRICT MODE)...\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $limit = 20;
    $offset = 0;
    $sourceApp = null;
    
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
    ";
    
    if ($sourceApp) {
        $query .= " AND source_app = :source_app";
    }
    
    $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    echo "Query: $query\n";
    
    $stmt = $conn->prepare($query);
    
    if ($sourceApp) {
        $stmt->bindParam(':source_app', $sourceApp);
    }
    
    // Binding exactly as Controller
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    echo "Executing with limit=$limit, offset=$offset...\n";
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ SUCCESS! Found " . count($items) . " items.\n";
    print_r($items[0] ?? []);

} catch (PDOException $e) {
    echo "❌ SQL ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "❌ GENERIC ERROR: " . $e->getMessage() . "\n";
}
?>

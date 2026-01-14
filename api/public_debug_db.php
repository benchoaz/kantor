<?php
// api/public_debug_db.php
// WEB VERSION OF DEBUG_API_DB_V2
// Akses via Browser untuk melihat Error Detail

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "🔍 WEB DIAGNOSTIC: API DATABASE & QUERY\n";
echo "=======================================\n";

// 1. Check File Locations
echo "[1] Checking Environment...\n";
$configFile = __DIR__ . '/config/database.php';
if (file_exists($configFile)) {
    echo "✅ found config/database.php\n";
    require_once $configFile;
} else {
    die("❌ FATAL: config/database.php NOT FOUND at $configFile");
}

// 2. Test Connection
echo "\n[2] Testing Connection...\n";
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Connection SUCCESS\n";
    echo "   Server Info: " . $conn->getAttribute(PDO::ATTR_SERVER_INFO) . "\n";
} catch (Exception $e) {
    die("❌ CONNECTION FAILED: " . $e->getMessage());
}

// 3. Test Query (Exact Copy of SuratController logic)
echo "\n[3] Testing Surat Query (List for Pimpinan)...\n";
try {
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
    
    echo "   Query: $query\n";
    
    $stmt = $conn->prepare($query);
    
    if ($sourceApp) {
        $stmt->bindParam(':source_app', $sourceApp);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ QUERY SUCCESS! Found " . count($items) . " items.\n";
    print_r($items);

} catch (PDOException $e) {
    echo "❌ PDO EXCEPTION (This is the 'Database error' source):\n";
    echo "   Code: " . $e->getCode() . "\n";
    echo "   Message: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ GENERAL EXCEPTION:\n";
    echo "   Message: " . $e->getMessage() . "\n";
}
?>

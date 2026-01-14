<?php
// test_c2_verification.php
// Script to check API Database directly for Surat content

require_once 'api/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check total surat
    $stmt = $conn->query("SELECT COUNT(*) as total FROM surat");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "📊 API EVENT STORE CHECK:\n";
    echo "Total Surat: " . $total . "\n";
    
    if ($total > 0) {
        $stmt = $conn->query("SELECT uuid, status, is_final, created_at FROM surat ORDER BY created_at DESC LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n-------- Latest 5 Surat --------\n";
        foreach ($rows as $r) {
            echo "UUID: " . $r['uuid'] . " | Status: " . $r['status'] . " | Final: " . $r['is_final'] . "\n";
        }
    } else {
        echo "❌ DATA KOSONG. Artinya SuratQu belum berhasil push ke API.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
/**
 * Fix Admin Auth & Diagnose Connection
 * 1. Updates the API Secret Hash to the correct value.
 * 2. Tests the connection to the Identity API.
 */

require_once __DIR__ . '/id/core/Database.php';
use App\Core\Database;

header('Content-Type: text/plain');

echo "--- MULA DIAGNOSA & PERBAIKAN ---\n\n";

// 1. UPDATE DATABASE HASH
try {
    echo "[1] Memperbarui API Secret Hash di Database...\n";
    $db = new Database();
    $conn = $db->connect();
    
    // Hash valid untuk 'admin_portal_secret_key_2026'
    $validHash = '$2y$10$lhq/pkDJzrTczUghXEs06.dmXCJFuyTAif9xsj6p8HSRXwzGt5FzG';
    
    $stmt = $conn->prepare("UPDATE authorized_apps SET api_secret_hash = ? WHERE app_id = 'admin_portal'");
    $stmt->execute([$validHash]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ BERHASIL: Hash diperbarui.\n";
    } else {
        echo "ℹ️ INFO: Hash sudah benar atau app_id tidak ditemukan.\n";
    }
} catch (Exception $e) {
    echo "❌ GAGAL DB: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. TEST KONEKSI CURL
echo "[2] Mengetes Koneksi ke Identity API...\n";
$url = 'https://id.sidiksae.my.id/v1/auth/login';
echo "URL Target: $url\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
// Send dummy data just to trigger a response (even 401 is success for connectivity)
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'test'])); 
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

if ($httpCode > 0) {
    echo "✅ KONEKSI BERHASIL (HTTP Code: $httpCode)\n";
    echo "Response awal: " . substr($response, 0, 100) . "...\n";
    echo "\nKESIMPULAN: Masalah koneksi teratasi. Silakan coba login kembali.\n";
} else {
    echo "❌ KONEKSI GAGAL (HTTP Code: 0)\n";
    echo "Curl Error #$curlErrno: $curlError\n";
    echo "\nANALISA:\n";
    echo "- Server tidak bisa menghubungi domain-nya sendiri (Loopback issue).\n";
    echo "- Kemungkinan DNS atau Firewall memblokir outgoing connection ke id.sidiksae.my.id.\n";
    echo "- SOLUSI: Coba ganti URL di login.php menjadi 'http://localhost/id/v1/auth/login' atau hubungi hosting provider.\n";
}

echo "\n--- SELESAI ---";
?>

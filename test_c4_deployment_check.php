<?php
// test_c4_deployment_check.php
// Script untuk mengecek apakah deployment Step C4 berhasil
// dengan cara mengecek endpoint API Disposisi.

$targetUrl = "https://api.sidiksae.my.id/api/disposisi";
$apiKey = "sk_live_camat_c4m4t2026"; // Key Camat

echo "🔍 CHECKING DEPLOYMENT C4...\n";
echo "Target: $targetUrl\n";

// 1. Cek Endpoint Existence (Kirim Empty Request)
// Harapan: 400 Bad Request atau 422 Unprocessable Entity (artinya endpoint ada, tapi data kosong)
// Jika 404 Not Found -> Belum deploy
// Jika 401 Unauthorized -> Key salah (tapi endpoint kemungkinan ada)

$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, "{}"); // Empty JSON
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "X-API-KEY: $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";

if ($httpCode == 404) {
    echo "❌ GAGAL: Endpoint tidak ditemukan (404).\n";
    echo "   Kemungkinan file 'DisposisiController.php' belum terupdate atau routing di 'index.php' belum ada.\n";
    echo "   -> Pastikan 'deploy_c4_web.php' sudah dijalankan!\n";
} elseif ($httpCode == 500) {
    echo "❌ ERROR: Internal Server Error (500).\n";
    echo "   Cek error log. Kemungkinan tabel database belum dimigrasi?\n";
    echo "   -> Coba jalankan ulang 'deploy_c4_web.php' bagian migrasi.\n";
} elseif ($httpCode == 401) {
    echo "⚠️ UNAUTHORIZED (401).\n";
    echo "   Endpoint ADA, tapi API Key ditolak. Pastikan key 'sk_live_camat_c4m4t2026' terdaftar.\n";
} elseif ($httpCode >= 200 && $httpCode < 500) {
    echo "✅ SUKSES! Endpoint Disposisi TERDETEKSI.\n";
    echo "   Kode ($httpCode) menandakan controller merespons input kita.\n";
    echo "   Fitur siap digunakan.\n";
} else {
    echo "❓ Unknown response: $httpCode\n";
}
echo "\n";
?>

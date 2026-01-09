<?php
/**
 * Test Script: API Integration Compliance
 * =========================================
 * Script ini menguji:
 * 1. Header X-CLIENT-ID dikirim dengan benar
 * 2. Endpoint GET /api/v1/surat/{id} berfungsi
 * 3. Error handling yang jelas (404, 500, dll)
 * 4. Format tanggal Indonesia
 * 
 * Usage: php test_api_compliance.php
 */

require_once 'includes/sidiksae_api_client.php';
require_once 'includes/functions.php';

$integration_config = require 'config/integration.php';
$config = $integration_config['sidiksae'];

echo "==============================================\n";
echo "  TEST KEPATUHAN INTEGRASI API SURATQU\n";
echo "==============================================\n\n";

// Initialize API Client
$apiClient = new SidikSaeApiClient($config);

// Test 1: Authentication & Header
echo "[TEST 1] Autentikasi dengan Header Lengkap\n";
echo "-------------------------------------------\n";
$token = $apiClient->authenticate();
if ($token) {
    echo "✅ Autentikasi berhasil\n";
    echo "   Token: " . substr($token, 0, 20) . "...\n\n";
} else {
    echo "❌ Autentikasi gagal\n";
    echo "   Periksa credentials di config/sidiksae.php\n\n";
    exit(1);
}

// Test 2: Get Surat Detail (ID Valid)
echo "[TEST 2] Get Detail Surat (ID Valid)\n";
echo "-------------------------------------------\n";
echo "Masukkan ID Surat untuk ditest (contoh: 15): ";
$test_id = trim(fgets(STDIN));

if (!$test_id) {
    echo "⚠️  ID kosong, skip test ini\n\n";
} else {
    $response = $apiClient->getSuratDetail($test_id);
    
    echo "HTTP Code: {$response['http_code']}\n";
    echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";
    echo "Message: {$response['message']}\n";
    
    if ($response['success'] && $response['data']) {
        echo "\n✅ Data surat berhasil dimuat:\n";
        $surat = $response['data'];
        echo "   - ID Surat: " . ($surat['id_surat'] ?? '-') . "\n";
        echo "   - Nomor Surat: " . ($surat['nomor_surat'] ?? '-') . "\n";
        echo "   - Asal Surat: " . ($surat['asal_surat'] ?? '-') . "\n";
        echo "   - Tanggal Surat: " . (isset($surat['tanggal_surat']) ? format_tgl_indo($surat['tanggal_surat']) : '-') . "\n";
        echo "   - Perihal: " . ($surat['perihal'] ?? '-') . "\n";
        echo "   - Sifat: " . ($surat['sifat'] ?? '-') . "\n";
        
        if (!empty($surat['scan_surat'])) {
            echo "   - File Scan: Ada (" . $surat['scan_surat'] . ")\n";
        }
        echo "\n";
    } else {
        echo "\n❌ Gagal atau data kosong\n\n";
    }
}

// Test 3: Error Handling (404)
echo "[TEST 3] Error Handling - Surat Tidak Ditemukan (404)\n";
echo "-------------------------------------------\n";
$invalid_id = 99999;
$response = $apiClient->getSuratDetail($invalid_id);

echo "HTTP Code: {$response['http_code']}\n";
echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";
echo "Message: {$response['message']}\n";

if ($response['http_code'] === 404 && !$response['success']) {
    echo "\n✅ Error 404 ditangani dengan benar\n";
    echo "   Pesan error dari API: {$response['message']}\n\n";
} else {
    echo "\n⚠️  Response tidak sesuai ekspektasi untuk 404\n\n";
}

// Test 4: Format Tanggal & Waktu
echo "[TEST 4] Format Tanggal & Waktu Indonesia\n";
echo "-------------------------------------------\n";

$test_date = '2025-12-31';
$test_datetime = '2025-12-31 09:30:00';

echo "Input tanggal: $test_date\n";
echo "Output format_tgl_indo(): " . format_tgl_indo($test_date) . "\n";
echo "Expected: 31 Desember 2025\n\n";

echo "Input datetime: $test_datetime\n";
echo "Output format_jam_wib(): " . format_jam_wib($test_datetime) . "\n";
echo "Expected: 09:30 WIB\n\n";

echo "Output format_tgl_jam_wib(): " . format_tgl_jam_wib($test_datetime) . "\n";
echo "Expected: 31 Desember 2025 09:30 WIB\n\n";

if (format_tgl_indo($test_date) === '31 Desember 2025' &&
    format_jam_wib($test_datetime) === '09:30 WIB' &&
    format_tgl_jam_wib($test_datetime) === '31 Desember 2025 09:30 WIB') {
    echo "✅ Semua format tanggal/waktu sesuai standar\n\n";
} else {
    echo "⚠️  Ada format yang tidak sesuai\n\n";
}

// Test 5: Check API Request Logs
echo "[TEST 5] Verifikasi Header di Log\n";
echo "-------------------------------------------\n";
$log_file = 'storage/api_requests.log';

if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $last_100_chars = substr($logs, -500); // Ambil 500 char terakhir
    
    if (strpos($last_100_chars, 'X-CLIENT-ID') !== false) {
        echo "✅ Header X-CLIENT-ID ditemukan di log\n";
        echo "   Log file: $log_file\n\n";
    } else {
        echo "⚠️  Header X-CLIENT-ID TIDAK ditemukan di log\n";
        echo "   Periksa file: $log_file\n\n";
    }
} else {
    echo "⚠️  Log file tidak ditemukan: $log_file\n\n";
}

// Summary
echo "==============================================\n";
echo "  RINGKASAN TEST\n";
echo "==============================================\n";
echo "✅ = Lolos, ⚠️ = Perlu Perhatian, ❌ = Gagal\n\n";

echo "Hasil yang Diharapkan:\n";
echo "✅ Integrasi stabil (autentikasi berhasil)\n";
echo "✅ Error terlihat jelas (message dari API)\n";
echo "✅ Tidak ada form kosong (ada placeholder '-')\n";
echo "✅ Tidak ada redirect misterius (error ditampilkan)\n";
echo "✅ HTTP 200 + success:true = data valid\n";
echo "✅ HTTP ≠ 200 atau success:false = error jelas\n\n";

echo "Script selesai.\n";

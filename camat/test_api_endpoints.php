<?php
/**
 * API Endpoint Tester
 * Script untuk test endpoint API pusat
 * 
 * CARA PAKAI:
 * php test_api_endpoints.php
 */

define('APP_INIT', true);
require_once 'config/config.php';
require_once 'config/api.php';
require_once 'helpers/api_helper.php';
require_once 'helpers/session_helper.php';

echo "\n========================================\n";
echo "TEST API ENDPOINTS - SidikSae\n";
echo "========================================\n\n";

// Buat session dummy untuk get_token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// IMPORTANT: Ganti dengan token valid dari login
// Atau jalankan setelah login via browser
$token = $_SESSION['api_token'] ?? null;

if (!$token) {
    echo "⚠️  WARNING: Tidak ada token di session.\n";
    echo "   Silakan login dulu via browser, atau masukkan token manual:\n";
    echo "   \$_SESSION['api_token'] = 'YOUR_TOKEN_HERE';\n\n";
    echo "   Test akan dilanjutkan tanpa auth (mungkin gagal)\n\n";
}

echo "📍 API Base URL: " . API_BASE_URL . "\n\n";

// ==================================================
// TEST 1: Surat List Endpoint
// ==================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: Surat Masuk (List)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Endpoint: " . ENDPOINT_SURAT_MASUK . "\n";
echo "Full URL: " . API_BASE_URL . ENDPOINT_SURAT_MASUK . "\n\n";

$response1 = call_api('GET', ENDPOINT_SURAT_MASUK, ['limit' => 1], $token);
echo "HTTP Code: " . ($response1['http_code'] ?? 'unknown') . "\n";
echo "Success: " . ($response1['success'] ? 'YES' : 'NO') . "\n";
echo "Message: " . ($response1['message'] ?? '-') . "\n";

if (isset($response1['data']) && is_array($response1['data'])) {
    $count = count($response1['data']);
    echo "Data Count: " . $count . "\n";
    if ($count > 0) {
        $firstItem = is_array($response1['data']) && isset($response1['data'][0]) 
            ? $response1['data'][0] 
            : $response1['data'];
        echo "Sample Keys: " . implode(', ', array_keys($firstItem)) . "\n";
        echo "Sample Values:\n";
        foreach ($firstItem as $k => $v) {
            if (is_scalar($v)) echo "  - $k: $v\n";
        }
    }
} else {
    echo "Data: EMPTY or NOT ARRAY\n";
}

// ==================================================
// TEST 2: Surat Detail Endpoint (NEW)
// ==================================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: Surat Detail (NEW ENDPOINT)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Ambil ID dari list jika ada
$testId = null;
if (isset($response1['data']) && is_array($response1['data']) && count($response1['data']) > 0) {
    $first = is_array($response1['data'][0]) ? $response1['data'][0] : $response1['data'];
    $testId = $first['id'] ?? $first['id_surat'] ?? $first['ref_id'] ?? null;
}

if (!$testId) {
    echo "⚠️  Tidak ada ID dari test 1, menggunakan ID dummy: 1\n";
    $testId = 1;
}

echo "Test ID: " . $testId . "\n";
echo "Endpoint: " . ENDPOINT_SURAT_DETAIL . "/{id}\n";
echo "Full URL: " . API_BASE_URL . ENDPOINT_SURAT_DETAIL . '/' . $testId . "\n\n";

$response2 = call_api('GET', ENDPOINT_SURAT_DETAIL . '/' . $testId, [], $token);
echo "HTTP Code: " . ($response2['http_code'] ?? 'unknown') . "\n";
echo "Success: " . ($response2['success'] ? 'YES' : 'NO') . "\n";
echo "Message: " . ($response2['message'] ?? '-') . "\n";

if (isset($response2['data']) && is_array($response2['data'])) {
    echo "Data Keys: " . implode(', ', array_keys($response2['data'])) . "\n";
    
    // Check critical fields
    $criticalFields = ['nomor_surat', 'asal_surat', 'perihal', 'tanggal_surat'];
    echo "\nCritical Fields Check:\n";
    foreach ($criticalFields as $field) {
        $exists = isset($response2['data'][$field]);
        $value = $exists ? $response2['data'][$field] : 'MISSING';
        echo "  - " . $field . ": " . ($exists ? '✅' : '❌') . " (" . $value . ")\n";
    }
} else {
    echo "Data: EMPTY or NOT ARRAY\n";
}

// ==================================================
// TEST 3: Disposisi List Endpoint
// ==================================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: Disposisi List\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Endpoint: " . ENDPOINT_DISPOSISI_LIST . "\n";
echo "Full URL: " . API_BASE_URL . ENDPOINT_DISPOSISI_LIST . "\n\n";

$response3 = call_api('GET', ENDPOINT_DISPOSISI_LIST, [], $token);
echo "HTTP Code: " . ($response3['http_code'] ?? 'unknown') . "\n";
echo "Success: " . ($response3['success'] ? 'YES' : 'NO') . "\n";
echo "Message: " . ($response3['message'] ?? '-') . "\n";

// ==================================================
// SUMMARY
// ==================================================
echo "\n========================================\n";
echo "📊 SUMMARY\n";
echo "========================================\n";

$tests = [
    'Surat Masuk List' => $response1,
    'Surat Detail' => $response2,
    'Disposisi List' => $response3,
];

foreach ($tests as $name => $resp) {
    $status = ($resp['success'] ?? false) ? '✅ PASS' : '❌ FAIL';
    $code = $resp['http_code'] ?? '???';
    echo sprintf("%-20s %s (HTTP %s)\n", $name . ':', $status, $code);
}

echo "\n⚠️  JIKA ADA YANG FAIL:\n";
echo "1. Cek apakah backend API sudah support endpoint baru\n";
echo "2. Kembalikan ENDPOINT_SURAT_DETAIL ke '/surat' (legacy)\n";
echo "3. Koordinasi dengan backend developer\n";
echo "========================================\n\n";

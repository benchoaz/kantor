<?php
/**
 * CLI Checker untuk Integrasi SidikSae
 */

echo "\n";
echo "====================================================\n";
echo "   PEMERIKSAAN KESIAPAN INTEGRASI SIDIKSAE\n";
echo "====================================================\n\n";

$passed = 0;
$total = 6;

// 1. Cek File
echo "[1/6] Cek File-file Penting...\n";
$required_files = [
    'config/integration.php',
    'includes/sidiksae_api_client.php',
    'includes/integrasi_sistem_handler.php',
    'disposisi_proses.php'
];

$all_exist = true;
foreach ($required_files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "  - $file: " . ($exists ? "✓ TERSEDIA" : "✗ TIDAK ADA") . "\n";
    if (!$exists) $all_exist = false;
}
if ($all_exist) $passed++;
echo $all_exist ? "  HASIL: ✓ LULUS\n\n" : "  HASIL: ✗ GAGAL\n\n";

// 2. Cek Konfigurasi
echo "[2/6] Cek Konfigurasi...\n";
try {
    $config = require __DIR__ . '/config/integration.php';
    $enabled = $config['sidiksae']['enabled'] ?? false;
    $base_url = $config['sidiksae']['base_url'] ?? '';
    $api_key = $config['sidiksae']['api_key'] ?? '';
    $client_id = $config['sidiksae']['client_id'] ?? '';
    
    echo "  - Base URL: " . ($base_url ? $base_url : "KOSONG") . "\n";
    echo "  - Client ID: " . ($client_id ? $client_id : "KOSONG") . "\n";
    echo "  - API Key: " . (!empty($api_key) ? "✓ TERISI" : "✗ KOSONG") . "\n";
    echo "  - Status: " . ($enabled ? "✓ AKTIF" : "✗ NON-AKTIF") . "\n";
    
    $config_ok = !empty($base_url) && !empty($api_key) && !empty($client_id);
    if ($config_ok) $passed++;
    echo $config_ok ? "  HASIL: ✓ LULUS\n\n" : "  HASIL: ✗ GAGAL (konfigurasi tidak lengkap)\n\n";
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
    echo "  HASIL: ✗ GAGAL\n\n";
}

// 3. Cek Database
echo "[3/6] Cek Database Schema...\n";
try {
    require_once __DIR__ . '/config/database.php';
    
    $stmt = $db->query("SHOW TABLES LIKE 'integrasi_docku_log'");
    $table_exists = $stmt->rowCount() > 0;
    
    echo "  - Tabel integrasi_docku_log: " . ($table_exists ? "✓ ADA" : "✗ TIDAK ADA") . "\n";
    
    if ($table_exists) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM integrasi_docku_log");
        $count = $stmt->fetch()['count'];
        echo "  - Jumlah record: $count\n";
        $passed++;
        echo "  HASIL: ✓ LULUS\n\n";
    } else {
        echo "  HASIL: ✗ GAGAL (jalankan database/integrasi_sistem.sql)\n\n";
    }
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
    echo "  HASIL: ✗ GAGAL\n\n";
}

// 4. Cek Folder Storage
echo "[4/6] Cek Folder Storage...\n";
$storage_path = __DIR__ . '/storage';
$exists = is_dir($storage_path);
$writable = is_writable($storage_path);

echo "  - Folder exists: " . ($exists ? "✓ YA" : "✗ TIDAK") . "\n";
echo "  - Writable: " . ($writable ? "✓ YA" : "✗ TIDAK") . "\n";

if ($exists && $writable) $passed++;
echo ($exists && $writable) ? "  HASIL: ✓ LULUS\n\n" : "  HASIL: ✗ GAGAL\n\n";

// 5. Cek PHP Extensions
echo "[5/6] Cek PHP Extensions...\n";
$extensions = ['curl', 'json', 'pdo', 'pdo_mysql'];
$all_loaded = true;
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "  - $ext: " . ($loaded ? "✓ LOADED" : "✗ NOT LOADED") . "\n";
    if (!$loaded) $all_loaded = false;
}
if ($all_loaded) $passed++;
echo $all_loaded ? "  HASIL: ✓ LULUS\n\n" : "  HASIL: ✗ GAGAL\n\n";

// 6. Test API Connection
echo "[6/6] Test Koneksi ke API SidikSae...\n";
if (isset($config) && $config['sidiksae']['enabled']) {
    try {
        require_once __DIR__ . '/includes/sidiksae_api_client.php';
        
        $apiClient = new SidikSaeApiClient($config['sidiksae']);
        $result = $apiClient->testConnection();
        
        if ($result['success']) {
            echo "  - Status: ✓ BERHASIL TERHUBUNG\n";
            echo "  - Message: " . $result['message'] . "\n";
            $passed++;
            echo "  HASIL: ✓ LULUS\n\n";
        } else {
            echo "  - Status: ✗ GAGAL TERHUBUNG\n";
            echo "  - Message: " . $result['message'] . "\n";
            echo "  HASIL: ✗ GAGAL\n\n";
        }
    } catch (Exception $e) {
        echo "  - ERROR: " . $e->getMessage() . "\n";
        echo "  HASIL: ✗ GAGAL\n\n";
    }
} else {
    echo "  - Status: SKIP (integrasi non-aktif)\n";
    echo "  HASIL: ⊘ DILEWATI\n\n";
}

// Kesimpulan
echo "====================================================\n";
echo "                   KESIMPULAN\n";
echo "====================================================\n";
echo "Lulus: $passed dari $total pemeriksaan\n";
$percentage = round(($passed / $total) * 100);
echo "Persentase: $percentage%\n\n";

if ($passed == $total) {
    echo "✓✓✓ SISTEM SIAP DIGUNAKAN! ✓✓✓\n";
    echo "\nAnda dapat:\n";
    echo "1. Buka aplikasi di browser\n";
    echo "2. Buat disposisi baru\n";
    echo "3. Lihat monitoring di: integrasi_sistem.php\n";
} elseif ($passed >= 4) {
    echo "⚠ HAMPIR SIAP - Perbaiki beberapa hal:\n\n";
    echo "1. Pastikan konfigurasi lengkap di: integrasi_pengaturan.php\n";
    echo "2. Test koneksi API dari UI\n";
    echo "3. Aktifkan toggle 'Sinkronisasi Otomatis'\n";
} else {
    echo "✗ BELUM SIAP - Perlu setup:\n\n";
    echo "1. Jalankan: mysql < database/integrasi_sistem.sql\n";
    echo "2. Atur konfigurasi di: integrasi_pengaturan.php\n";
    echo "3. Test koneksi dan aktifkan integrasi\n";
}

echo "\n====================================================\n";
echo "Buka check_readiness.php di browser untuk hasil visual\n";
echo "====================================================\n\n";

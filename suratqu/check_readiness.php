<?php
/**
 * SuratQu - SidikSae Integration Readiness Checker
 * Memeriksa kesiapan sistem untuk integrasi
 */

// Nonaktifkan error di output untuk tampilan lebih rapi
error_reporting(0);
ini_set('display_errors', 0);

$results = [];
$total_checks = 0;
$passed_checks = 0;

// ============================================================
// 1. CEK FILE-FILE PENTING
// ============================================================
$total_checks++;
$required_files = [
    'config/integration.php' => 'Konfigurasi Integrasi',
    'includes/sidiksae_api_client.php' => 'API Client SidikSae',
    'includes/integrasi_sistem_handler.php' => 'Integration Handler',
    'disposisi_proses.php' => 'Proses Disposisi',
    'integrasi_sistem.php' => 'Monitoring Integrasi',
    'integrasi_pengaturan.php' => 'Pengaturan Integrasi'
];

$files_ok = true;
$file_details = [];
foreach ($required_files as $file => $desc) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $file_details[] = [
        'file' => $file,
        'desc' => $desc,
        'status' => $exists
    ];
    if (!$exists) $files_ok = false;
}

$results[] = [
    'check' => 'File-file Integrasi',
    'status' => $files_ok,
    'message' => $files_ok ? 'Semua file integrasi tersedia' : 'Ada file yang hilang',
    'details' => $file_details
];
if ($files_ok) $passed_checks++;

// ============================================================
// 2. CEK KONFIGURASI
// ============================================================
$total_checks++;
try {
    $config = require __DIR__ . '/config/integration.php';
    
    $config_valid = true;
    $config_issues = [];
    
    // Cek struktur konfigurasi
    if (!isset($config['sidiksae'])) {
        $config_valid = false;
        $config_issues[] = 'Konfigurasi sidiksae tidak ditemukan';
    } else {
        $required_keys = ['base_url', 'api_key', 'client_id', 'client_secret', 'enabled'];
        foreach ($required_keys as $key) {
            if (!isset($config['sidiksae'][$key])) {
                $config_valid = false;
                $config_issues[] = "Key '$key' tidak ditemukan";
            }
        }
        
        // Cek nilai konfigurasi
        if (isset($config['sidiksae']['base_url']) && empty($config['sidiksae']['base_url'])) {
            $config_valid = false;
            $config_issues[] = 'base_url kosong';
        }
        
        if (isset($config['sidiksae']['api_key']) && empty($config['sidiksae']['api_key'])) {
            $config_valid = false;
            $config_issues[] = 'api_key kosong';
        }
    }
    
    $results[] = [
        'check' => 'Validasi Konfigurasi',
        'status' => $config_valid,
        'message' => $config_valid ? 'Konfigurasi valid' : 'Konfigurasi tidak lengkap',
        'details' => [
            'enabled' => $config['sidiksae']['enabled'] ?? false,
            'base_url' => $config['sidiksae']['base_url'] ?? 'TIDAK DIATUR',
            'client_id' => $config['sidiksae']['client_id'] ?? 'TIDAK DIATUR',
            'has_api_key' => !empty($config['sidiksae']['api_key'] ?? ''),
            'has_client_secret' => !empty($config['sidiksae']['client_secret'] ?? ''),
            'issues' => $config_issues
        ]
    ];
    if ($config_valid) $passed_checks++;
    
} catch (Exception $e) {
    $results[] = [
        'check' => 'Validasi Konfigurasi',
        'status' => false,
        'message' => 'Error saat membaca konfigurasi: ' . $e->getMessage(),
        'details' => []
    ];
}

// ============================================================
// 3. CEK DATABASE
// ============================================================
$total_checks++;
try {
    require_once __DIR__ . '/config/database.php';
    
    // Cek tabel integrasi_docku_log
    $stmt = $db->query("SHOW TABLES LIKE 'integrasi_docku_log'");
    $table_exists = $stmt->rowCount() > 0;
    
    $columns_ok = false;
    $required_columns = ['id', 'disposisi_id', 'payload_hash', 'payload', 'status', 'response_code', 'response_body', 'created_at', 'updated_at'];
    $existing_columns = [];
    
    if ($table_exists) {
        $stmt = $db->query("DESCRIBE integrasi_docku_log");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $existing_columns = $columns;
        
        $missing_columns = array_diff($required_columns, $columns);
        $columns_ok = empty($missing_columns);
    }
    
    $results[] = [
        'check' => 'Database Schema',
        'status' => $table_exists && $columns_ok,
        'message' => $table_exists ? ($columns_ok ? 'Tabel integrasi_docku_log valid' : 'Tabel ada tapi kolom tidak lengkap') : 'Tabel integrasi_docku_log belum dibuat',
        'details' => [
            'table_exists' => $table_exists,
            'required_columns' => $required_columns,
            'existing_columns' => $existing_columns
        ]
    ];
    
    if ($table_exists && $columns_ok) $passed_checks++;
    
} catch (Exception $e) {
    $results[] = [
        'check' => 'Database Schema',
        'status' => false,
        'message' => 'Error koneksi database: ' . $e->getMessage(),
        'details' => []
    ];
}

// ============================================================
// 4. CEK FOLDER STORAGE
// ============================================================
$total_checks++;
$storage_path = __DIR__ . '/storage';
$storage_ok = is_dir($storage_path) && is_writable($storage_path);

$results[] = [
    'check' => 'Folder Storage',
    'status' => $storage_ok,
    'message' => $storage_ok ? 'Folder storage siap dan writable' : 'Folder storage tidak ada atau tidak writable',
    'details' => [
        'path' => $storage_path,
        'exists' => is_dir($storage_path),
        'writable' => is_writable($storage_path)
    ]
];
if ($storage_ok) $passed_checks++;

// ============================================================
// 5. CEK EKSTENSI PHP
// ============================================================
$total_checks++;
$required_extensions = ['curl', 'json', 'pdo', 'pdo_mysql'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

$extensions_ok = empty($missing_extensions);

$results[] = [
    'check' => 'Ekstensi PHP',
    'status' => $extensions_ok,
    'message' => $extensions_ok ? 'Semua ekstensi tersedia' : 'Ada ekstensi yang hilang: ' . implode(', ', $missing_extensions),
    'details' => [
        'required' => $required_extensions,
        'missing' => $missing_extensions,
        'php_version' => phpversion()
    ]
];
if ($extensions_ok) $passed_checks++;

// ============================================================
// 6. TEST KONEKSI API (Jika konfigurasi valid)
// ============================================================
$total_checks++;
$api_test_result = null;

if (isset($config) && $config['sidiksae']['enabled']) {
    try {
        require_once __DIR__ . '/includes/sidiksae_api_client.php';
        
        $apiClient = new SidikSaeApiClient($config['sidiksae']);
        $api_test_result = $apiClient->testConnection();
        
        $results[] = [
            'check' => 'Koneksi ke API SidikSae',
            'status' => $api_test_result['success'] ?? false,
            'message' => $api_test_result['message'] ?? 'Tidak ada response',
            'details' => $api_test_result
        ];
        
        if ($api_test_result['success'] ?? false) $passed_checks++;
        
    } catch (Exception $e) {
        $results[] = [
            'check' => 'Koneksi ke API SidikSae',
            'status' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'details' => []
        ];
    }
} else {
    $results[] = [
        'check' => 'Koneksi ke API SidikSae',
        'status' => false,
        'message' => 'Integrasi dinonaktifkan atau konfigurasi tidak valid',
        'details' => ['skipped' => true]
    ];
}

// ============================================================
// TAMPILKAN HASIL
// ============================================================
$percentage = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100) : 0;
$ready = $passed_checks == $total_checks;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Kesiapan Integrasi - SuratQu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 900px;
        }
        .card {
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border: none;
        }
        .progress {
            height: 30px;
            border-radius: 15px;
            background: #e9ecef;
        }
        .progress-bar {
            font-weight: bold;
            border-radius: 15px;
            transition: width 1s ease;
        }
        .check-item {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .check-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .check-success {
            background: linear-gradient(to right, #d4edda, #c3e6cb);
            border-color: #28a745;
        }
        .check-failed {
            background: linear-gradient(to right, #f8d7da, #f5c6cb);
            border-color: #dc3545;
        }
        .badge-xl {
            font-size: 1.5rem;
            padding: 15px 25px;
            border-radius: 50px;
        }
        .detail-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        .status-icon {
            font-size: 2rem;
            margin-right: 15px;
        }
        pre {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 10px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-body text-center p-5">
                <i class="fas fa-network-wired text-primary mb-3" style="font-size: 4rem;"></i>
                <h1 class="fw-bold mb-2">Pemeriksaan Kesiapan Integrasi</h1>
                <p class="text-muted mb-4">SuratQu → SidikSae API</p>
                
                <div class="progress mb-3">
                    <div class="progress-bar <?= $ready ? 'bg-success' : ($percentage > 50 ? 'bg-warning' : 'bg-danger') ?>" 
                         role="progressbar" 
                         style="width: <?= $percentage ?>%">
                        <?= $percentage ?>%
                    </div>
                </div>
                
                <h3 class="mb-3">
                    <?php if ($ready): ?>
                        <span class="badge bg-success badge-xl">
                            <i class="fas fa-check-circle me-2"></i>
                            SISTEM SIAP DIGUNAKAN!
                        </span>
                    <?php elseif ($percentage >= 50): ?>
                        <span class="badge bg-warning badge-xl">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            PERLU PERBAIKAN
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger badge-xl">
                            <i class="fas fa-times-circle me-2"></i>
                            BELUM SIAP
                        </span>
                    <?php endif; ?>
                </h3>
                
                <p class="text-muted mb-0">
                    <strong><?= $passed_checks ?></strong> dari <strong><?= $total_checks ?></strong> pemeriksaan berhasil
                </p>
            </div>
        </div>

        <!-- Hasil Pemeriksaan -->
        <?php foreach ($results as $index => $result): ?>
        <div class="check-item <?= $result['status'] ? 'check-success' : 'check-failed' ?>">
            <div class="d-flex align-items-start">
                <div class="status-icon">
                    <?php if ($result['status']): ?>
                        <i class="fas fa-check-circle text-success"></i>
                    <?php else: ?>
                        <i class="fas fa-times-circle text-danger"></i>
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-2">
                        <?= ($index + 1) ?>. <?= htmlspecialchars($result['check']) ?>
                    </h5>
                    <p class="mb-2"><?= htmlspecialchars($result['message']) ?></p>
                    
                    <?php if (!empty($result['details']) && $result['details'] !== ['skipped' => true]): ?>
                    <button class="btn btn-sm btn-outline-secondary" type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#detail-<?= $index ?>" 
                            aria-expanded="false">
                        <i class="fas fa-info-circle me-1"></i> Lihat Detail
                    </button>
                    
                    <div class="collapse" id="detail-<?= $index ?>">
                        <div class="detail-box mt-2">
                            <pre><?= json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Action Buttons -->
        <div class="card mt-4">
            <div class="card-body text-center p-4">
                <?php if ($ready): ?>
                    <h5 class="fw-bold text-success mb-3">
                        <i class="fas fa-rocket me-2"></i>
                        Sistem sudah siap! Anda dapat mulai menggunakan fitur integrasi.
                    </h5>
                    <p class="text-muted mb-4">
                        Setiap disposisi yang dibuat akan otomatis dikirim ke sistem terpusat SidikSae.
                    </p>
                    <a href="integrasi_sistem.php" class="btn btn-success btn-lg me-2">
                        <i class="fas fa-chart-line me-2"></i>Monitoring Integrasi
                    </a>
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-home me-2"></i>Ke Dashboard
                    </a>
                <?php else: ?>
                    <h5 class="fw-bold text-warning mb-3">
                        <i class="fas fa-tools me-2"></i>
                        Ada beberapa hal yang perlu diperbaiki
                    </h5>
                    <p class="text-muted mb-4">
                        Silakan perbaiki masalah di atas sebelum menggunakan fitur integrasi.
                    </p>
                    <a href="integrasi_pengaturan.php" class="btn btn-warning btn-lg me-2">
                        <i class="fas fa-cog me-2"></i>Pengaturan Integrasi
                    </a>
                    <a href="INTEGRASI_SIDIKSAE.md" class="btn btn-outline-primary btn-lg" target="_blank">
                        <i class="fas fa-book me-2"></i>Panduan Setup
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 text-white">
            <small>
                <i class="fas fa-clock me-1"></i>
                Diperiksa pada: <?= date('d/m/Y H:i:s') ?>
            </small>
            <br>
            <a href="?refresh=1" class="text-white text-decoration-none">
                <i class="fas fa-sync-alt me-1"></i>Periksa Ulang
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

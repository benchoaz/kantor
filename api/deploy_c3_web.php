<?php
// deploy_c3_web.php
// Web-Based Deployer for Step C3 (No Terminal Needed)

header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "🚀 WEB DEPLOYMENT STEP C3\n";
echo "=========================\n\n";

$targetDir = __DIR__;
$tarFile = $targetDir . '/step_c3_hotfix.tar.gz';

// 1. EXTRACT HOTFIX
echo "[1] Extracting Package...\n";
if (file_exists($tarFile)) {
    try {
        $phar = new PharData($tarFile);
        $phar->extractTo($targetDir, null, true); // Overwrite enabled
        echo "✅ Extraction Success!\n";
    } catch (Exception $e) {
        echo "❌ Extraction Failed: " . $e->getMessage() . "\n";
        echo "⚠️ Try extracting manually via cPanel File Manager.\n";
    }
} else {
    echo "❌ File not found: $tarFile\n";
    echo "   Please upload step_c3_hotfix.tar.gz to this folder.\n";
    exit;
}

// 2. RUN DATABASE MIGRATION
echo "\n[2] Database Migration (Surat Table)...\n";
require_once $targetDir . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Fix Table Schema
    $sql = "CREATE TABLE IF NOT EXISTS surat (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        uuid CHAR(36) NOT NULL UNIQUE COMMENT 'UUID from source app',
        nomor_surat VARCHAR(100) NULL,
        tanggal_surat DATE NULL,
        pengirim VARCHAR(200) NULL COMMENT 'Asal surat / pengirim',
        perihal TEXT NULL,
        scan_surat VARCHAR(500) NOT NULL COMMENT 'URL to PDF file (wajib)',
        is_final TINYINT(1) DEFAULT 1 COMMENT '1 = final, ready for disposition',
        status VARCHAR(50) DEFAULT 'FINAL',
        source_app VARCHAR(50) DEFAULT 'suratqu' COMMENT 'Source: suratqu, camat, docku',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_uuid (uuid),
        INDEX idx_source_app (source_app),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->exec($sql);
    echo "✅ Table 'surat' verified/created.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration Failed: " . $e->getMessage() . "\n";
}

// 3. AUTH SETUP
echo "\n[3] API Key Setup...\n";
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS api_clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(100),
        api_key VARCHAR(100) UNIQUE,
        is_active TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $conn->prepare("INSERT IGNORE INTO api_clients (client_name, api_key) VALUES ('suratqu_prod', 'sk_live_suratqu_surat2026')");
    $stmt->execute();
    echo "✅ API Key inserted.\n";
    
} catch (PDOException $e) {
    echo "❌ Auth Setup Failed: " . $e->getMessage() . "\n";
}

echo "\n=========================\n";
echo "🎉 DEPLOYMENT FINISHED\n";
echo "Silakan lanjut ke Step C4.";
?>

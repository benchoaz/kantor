<?php
// api/run_fix_schema.php
// HOTFIX V3: Complete Database Repair (Surat + EventLog + Disposisi)

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

require_once 'config/database.php';

echo "🛠️ STARTING SCHEMA HOTFIX V3 (FINAL CHECK)...\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Helper function to check column
    function checkColumn($conn, $table, $col) {
        try {
            $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE '$col'");
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false; // Table might not exist
        }
    }

    // Helper to check table
    function checkTable($conn, $table) {
        try {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // --- 1. AUDIT LOG (Missing in Production) ---
    if (!checkTable($conn, 'event_log')) {
        echo "⚠️ Table 'event_log' MISSING. Creating it...\n";
        $sql = "CREATE TABLE IF NOT EXISTS event_log (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_uuid CHAR(36) NOT NULL,
            actor_uuid CHAR(36) NULL,
            payload JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_type (event_type),
            INDEX idx_entity_uuid (entity_uuid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->exec($sql);
        echo "✅ Table 'event_log' CREATED.\n";
    } else {
        echo "✅ Table 'event_log' already exists.\n";
    }

    // --- 2. SURAT COLUMNS (Make sure all are there) ---
    $suratCols = [
        'scan_surat' => "VARCHAR(500) NOT NULL COMMENT 'URL to PDF file (wajib)' AFTER perihal",
        'is_final' => "TINYINT(1) DEFAULT 1 AFTER scan_surat",
        'status' => "VARCHAR(50) DEFAULT 'FINAL' AFTER is_final",
        'source_app' => "VARCHAR(50) DEFAULT 'suratqu' AFTER status",
        'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];

    foreach ($suratCols as $col => $def) {
        if (!checkColumn($conn, 'surat', $col)) {
            echo "⚠️ Column '$col' MISSING in 'surat'. Adding it...\n";
            $conn->exec("ALTER TABLE surat ADD COLUMN $col $def");
            echo "✅ Column '$col' ADDED.\n";
        } else {
            echo "✅ Column '$col' OK.\n";
        }
    }

    // --- 3. DISPOSISI TABLES (Prevent next crash) ---
    if (!checkTable($conn, 'disposisi')) {
        echo "⚠️ Table 'disposisi' MISSING. Creating it...\n";
        $sql = "CREATE TABLE IF NOT EXISTS disposisi (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(36) NOT NULL UNIQUE,
            surat_uuid CHAR(36) NOT NULL,
            pembuat_uuid CHAR(36) NOT NULL,
            catatan TEXT NULL,
            sifat_disposisi VARCHAR(50) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_surat_uuid (surat_uuid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->exec($sql);
        echo "✅ Table 'disposisi' CREATED.\n";
    } else {
        echo "✅ Table 'disposisi' already exists.\n";
    }

    if (!checkTable($conn, 'disposisi_penerima')) {
        echo "⚠️ Table 'disposisi_penerima' MISSING. Creating it...\n";
        $sql = "CREATE TABLE IF NOT EXISTS disposisi_penerima (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            disposisi_uuid CHAR(36) NOT NULL,
            penerima_uuid CHAR(36) NOT NULL,
            status_baca TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_disposisi (disposisi_uuid),
            INDEX idx_penerima (penerima_uuid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->exec($sql);
        echo "✅ Table 'disposisi_penerima' CREATED.\n";
    } else {
        echo "✅ Table 'disposisi_penerima' already exists.\n";
    }


    echo "\n🎉 DATABASE FULLY REPAIRED (V3)!\n";
    echo "Please re-submit message from SuratQu.\n";

} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
}
?>

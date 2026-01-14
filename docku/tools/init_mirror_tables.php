<?php
/**
 * DOCKU: INITIALIZE MIRROR TABLES (v19)
 * Creates the necessary tables for disposition synchronization.
 */

require_once __DIR__ . '/../config/database.php';

echo "=== DOCKU: INITIALIZING MIRROR TABLES ===\n";

if (php_sapi_name() !== 'cli') {
    die("❌ This script can only be run via CLI.\n");
}

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Table: surat
    echo "Creating 'surat' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS surat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uuid VARCHAR(36) NOT NULL UNIQUE,
        nomor_surat VARCHAR(100),
        perihal TEXT,
        asal_surat VARCHAR(255),
        tanggal_surat DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Table: disposisi
    echo "Creating 'disposisi' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS disposisi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uuid VARCHAR(36) NOT NULL UNIQUE,
        uuid_surat VARCHAR(36) NOT NULL,
        from_role VARCHAR(50),
        to_role VARCHAR(50),
        instruksi TEXT,
        catatan TEXT,
        tgl_disposisi DATETIME,
        status_global VARCHAR(20) DEFAULT 'BARU',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_surat_uuid (uuid_surat),
        INDEX idx_to_role (to_role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Table: disposisi_penerima
    echo "Creating 'disposisi_penerima' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS disposisi_penerima (
        id INT AUTO_INCREMENT PRIMARY KEY,
        disposisi_uuid VARCHAR(36) NOT NULL,
        disposisi_id INT,
        user_id INT NOT NULL,
        status VARCHAR(20) DEFAULT 'baru',
        laporan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unq_disp_user (disposisi_uuid, user_id),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "✅ Initialization complete. Mirror tables are ready.\n";
    
} catch (PDOException $e) {
    echo "❌ Error during initialization: " . $e->getMessage() . "\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
}

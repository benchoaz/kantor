<?php
// update_schema_v3_final.php
require_once 'config/database.php';

echo "<h2>SuratQu V3 Final Database Update</h2>";
echo "<pre>";

try {
    // 1. Update Tabel surat_masuk (Status & Kolom Tujuan)
    echo "Checking table 'surat_masuk'...\n";
    
    // Check if 'tujuan' column exists
    $cols = $db->query("SHOW COLUMNS FROM surat_masuk LIKE 'tujuan'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE surat_masuk ADD COLUMN tujuan TEXT NULL AFTER asal_surat");
        echo "[OK] Added column 'tujuan' to 'surat_masuk'.\n";
    } else {
        echo "[SKIP] Column 'tujuan' already exists.\n";
    }

    // Update ENUM status in surat_masuk
    $db->exec("ALTER TABLE surat_masuk MODIFY COLUMN status ENUM('draft','valid','teragenda','disposisi_dibuat','baru','disposisi','proses','selesai','arsip') DEFAULT 'draft'");
    echo "[OK] Updated ENUM status in 'surat_masuk'.\n";


    // 2. Update Tabel disposisi (Status Baca & Pengerjaan)
    echo "\nChecking table 'disposisi'...\n";
    
    // Check status_baca
    $cols = $db->query("SHOW COLUMNS FROM disposisi LIKE 'status_baca'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE disposisi ADD COLUMN status_baca ENUM('belum','sudah') DEFAULT 'belum'");
        $db->exec("ALTER TABLE disposisi ADD COLUMN tanggal_baca DATETIME NULL");
        echo "[OK] Added 'status_baca' columns.\n";
    }

    // Check status_pengerjaan + Ensure ENUM includes 'proses'
    $cols = $db->query("SHOW COLUMNS FROM disposisi LIKE 'status_pengerjaan'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE disposisi ADD COLUMN status_pengerjaan ENUM('menunggu','proses','selesai') DEFAULT 'menunggu'");
        $db->exec("ALTER TABLE disposisi ADD COLUMN tanggal_selesai DATETIME NULL");
        $db->exec("ALTER TABLE disposisi ADD COLUMN catatan_hasil TEXT NULL");
        $db->exec("ALTER TABLE disposisi ADD COLUMN file_hasil VARCHAR(255) NULL");
        echo "[OK] Added 'status_pengerjaan' and result columns.\n";
    } else {
        // Enforce ENUM update to include 'proses' if it was missing
        $db->exec("ALTER TABLE disposisi MODIFY COLUMN status_pengerjaan ENUM('menunggu','proses','selesai') DEFAULT 'menunggu'");
        echo "[OK] Updated ENUM 'status_pengerjaan' (ensured 'proses' exists).\n";
    }


    // 3. Create Integration Log Table
    echo "\nChecking table 'integrasi_docku_log'...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS integrasi_docku_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        disposisi_id INT NOT NULL,
        payload_hash VARCHAR(64) NULL,
        payload TEXT NULL,
        response_code INT NULL,
        response_body TEXT NULL,
        status ENUM('pending','success','failed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL
    ) ENGINE=InnoDB");
    echo "[OK] Table 'integrasi_docku_log' ready.\n";

    echo "\n<h3>UPDATE SUCCESSFUL! All schemas are V3 ready.</h3>";

} catch (PDOException $e) {
    echo "\n<h3 style='color:red;'>ERROR: " . $e->getMessage() . "</h3>";
}
echo "</pre>";
?>

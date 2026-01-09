<?php
// update_schema_v3.php
require_once 'config/database.php';

try {
    // 1. Update ENUM status in surat_masuk
    // Existing: 'baru', 'disposisi', 'proses', 'selesai'
    // New: 'draft', 'valid', 'teragenda', 'disposisi_dibuat' (and legacy ones to avoid error)
    $sql = "ALTER TABLE surat_masuk MODIFY COLUMN status ENUM('draft', 'valid', 'teragenda', 'disposisi_dibuat', 'baru', 'disposisi', 'proses', 'selesai') DEFAULT 'draft'";
    $db->exec($sql);
    echo "SUCCESS: Updated status ENUM in surat_masuk.<br>";

    // 2. Add 'tujuan_id' if not exists (Legacy/Optional now)
    $cols = $db->query("SHOW COLUMNS FROM surat_masuk LIKE 'tujuan_id'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE surat_masuk ADD COLUMN tujuan_id INT NULL AFTER perihal");
        echo "SUCCESS: Added column 'tujuan_id'.<br>";
    }

    // 2b. Add 'tujuan' (Text) if not exists (CRITICAL FIX)
    $cols = $db->query("SHOW COLUMNS FROM surat_masuk LIKE 'tujuan'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE surat_masuk ADD COLUMN tujuan VARCHAR(255) NULL AFTER perihal");
        echo "SUCCESS: Added column 'tujuan' (Fix SQL Error).<br>";
    }

    // 3. Add 'tgl_agenda' if not exists
    $cols = $db->query("SHOW COLUMNS FROM surat_masuk LIKE 'tgl_agenda'")->fetch();
    if (!$cols) {
        $db->exec("ALTER TABLE surat_masuk ADD COLUMN tgl_agenda DATETIME NULL AFTER tgl_surat");
        echo "SUCCESS: Added column 'tgl_agenda'.<br>";
    }
    
    // 4. Update existing 'baru' to 'draft' (Optional, for consistency)
    $db->exec("UPDATE surat_masuk SET status='draft' WHERE status='baru'");
    echo "SUCCESS: Migrated legacy 'baru' status to 'draft'.<br>";

    echo "DONE. Schema Refactor V3 Applied.";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

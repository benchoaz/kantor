<?php
// tools/migrate_002.php
require_once dirname(__DIR__) . '/config/database.php';

try {
    echo "Starting Migration 002: Add Time and Workflow Columns...\n";
    
    // 1. Add jam_mulai
    $pdo->exec("ALTER TABLE kegiatan ADD COLUMN jam_mulai TIME NULL AFTER tanggal");
    echo "Column 'jam_mulai' added.\n";
    
    // 2. Add jam_selesai
    $pdo->exec("ALTER TABLE kegiatan ADD COLUMN jam_selesai TIME NULL AFTER jam_mulai");
    echo "Column 'jam_selesai' added.\n";
    
    // 3. Add status with ENUM
    $pdo->exec("ALTER TABLE kegiatan ADD COLUMN status ENUM('draft', 'pending', 'verified', 'rejected', 'revision') DEFAULT 'draft' AFTER status_pengaduan");
    echo "Column 'status' added.\n";
    
    // 4. Add catatan_revisi
    $pdo->exec("ALTER TABLE kegiatan ADD COLUMN catatan_revisi TEXT NULL AFTER status");
    echo "Column 'catatan_revisi' added.\n";

    // 4b. Add join_code (Required for Team Workflow)
    $pdo->exec("ALTER TABLE kegiatan ADD COLUMN join_code VARCHAR(10) NULL AFTER created_by");
    echo "Column 'join_code' added.\n";

    // 5. Update existing data to 'verified' so they remain visible
    $pdo->exec("UPDATE kegiatan SET status = 'verified' WHERE status = 'draft'");
    echo "Existing activities updated to status='verified'.\n";

    // 6. Set default times for existing data
    $pdo->exec("UPDATE kegiatan SET jam_mulai = TIME(created_at), jam_selesai = ADDTIME(TIME(created_at), '01:00:00') WHERE jam_mulai IS NULL");
    echo "Default times populated for existing records.\n";
    
    echo "Migration 002 Completed Successfully!\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Migration already applied (Duplicate column error). Skipping.\n";
    } else {
        echo "Migration Failed: " . $e->getMessage() . "\n";
    }
}
?>

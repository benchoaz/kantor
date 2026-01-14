-- =====================================================
-- SURATQU GOVERNANCE SCHEMA - FINAL PRODUCTION VERSION
-- Compatible with MariaDB/MySQL
-- Handles existing columns gracefully
-- =====================================================

USE sidiksae_suratqu;

-- ============================================
-- SURAT_MASUK: Core Governance Fields
-- ============================================

-- Step 1: Add columns (one at a time for safety)
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id_sm;
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS created_by CHAR(36) NULL COMMENT 'UUID from Identity';
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS created_by_legacy INT(11) NULL;
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS source_app VARCHAR(50) DEFAULT 'suratqu';
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS is_final TINYINT(1) DEFAULT 0;
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS finalized_at DATETIME NULL;
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS finalized_by CHAR(36) NULL;
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS tgl_agenda DATETIME NULL;
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS tujuan VARCHAR(200) NULL;
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Step 2: Add indexes (check if exists first via procedure)
DROP PROCEDURE IF EXISTS AddIndexIfNotExists;

DELIMITER $$
CREATE PROCEDURE AddIndexIfNotExists(
    IN tableName VARCHAR(128),
    IN indexName VARCHAR(128),
    IN indexDef VARCHAR(500)
)
BEGIN
    DECLARE index_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO index_exists
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = tableName
    AND index_name = indexName;
    
    IF index_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' ADD ', indexDef);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- Add UUID unique index
CALL AddIndexIfNotExists('surat_masuk', 'idx_uuid', 'UNIQUE KEY idx_uuid (uuid)');
CALL AddIndexIfNotExists('surat_masuk', 'idx_created_by', 'INDEX idx_created_by (created_by)');
CALL AddIndexIfNotExists('surat_masuk', 'idx_is_final', 'INDEX idx_is_final (is_final)');
CALL AddIndexIfNotExists('surat_masuk', 'idx_source_app', 'INDEX idx_source_app (source_app)');

-- Step 3: Rename file_path to scan_surat (if exists)
SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'surat_masuk' 
    AND column_name = 'file_path'
);

SET @rename_sql = IF(@column_exists > 0,
    'ALTER TABLE surat_masuk CHANGE COLUMN file_path scan_surat VARCHAR(500) NULL',
    'SELECT 1 as noop'
);

PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- If scan_surat still doesn't exist, add it
ALTER TABLE surat_masuk ADD COLUMN IF NOT EXISTS scan_surat VARCHAR(500) NULL;

-- ============================================
-- SURAT_KELUAR: Same Treatment
-- ============================================

ALTER TABLE surat_keluar ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL;
ALTER TABLE surat_keluar ADD COLUMN IF NOT EXISTS created_by CHAR(36) NULL;
ALTER TABLE surat_keluar ADD COLUMN IF NOT EXISTS source_app VARCHAR(50) DEFAULT 'suratqu';
ALTER TABLE surat_keluar ADD COLUMN IF NOT EXISTS is_final TINYINT(1) DEFAULT 0;
ALTER TABLE surat_keluar ADD COLUMN IF NOT EXISTS finalized_at DATETIME NULL;
ALTER TABLE surat_keluar ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add indexes
CALL AddIndexIfNotExists('surat_keluar', 'idx_uuid_sk', 'UNIQUE KEY idx_uuid_sk (uuid)');
CALL AddIndexIfNotExists('surat_keluar', 'idx_created_by_sk', 'INDEX idx_created_by_sk (created_by)');

-- Rename file_path
SET @column_exists_sk = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'surat_keluar' 
    AND column_name = 'file_path'
);

SET @rename_sql_sk = IF(@column_exists_sk > 0,
    'ALTER TABLE surat_keluar CHANGE COLUMN file_path scan_surat VARCHAR(500) NULL',
    'SELECT 1 as noop'  
);

PREPARE stmt FROM @rename_sql_sk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE surat_keluar ADD COLUMN IF NOT EXISTS scan_surat VARCHAR(500) NULL;

-- ============================================
-- BACKFILL: Generate Missing Data
-- ============================================

-- Generate UUIDs
UPDATE surat_masuk SET uuid = UUID() WHERE uuid IS NULL OR uuid = '';
UPDATE surat_keluar SET uuid = UUID() WHERE uuid IS NULL OR uuid = '';

-- Set source_app
UPDATE surat_masuk SET source_app = 'suratqu' WHERE source_app IS NULL OR source_app = '';
UPDATE surat_keluar SET source_app = 'suratqu' WHERE source_app IS NULL OR source_app = '';

-- ============================================
-- CLEANUP
-- ============================================

DROP PROCEDURE IF EXISTS AddIndexIfNotExists;

-- ============================================
-- VERIFICATION
-- ============================================

SELECT '✅ Schema migration completed successfully!' as status;

SELECT 'surat_masuk columns:' as info;
SHOW COLUMNS FROM surat_masuk;

SELECT 'surat_masuk indexes:' as info;
SHOW INDEX FROM surat_masuk;

SELECT 'Data summary:' as info;
SELECT 
    'surat_masuk' as tabel,
    COUNT(*) as total,
    SUM(CASE WHEN uuid IS NOT NULL AND uuid != '' THEN 1 ELSE 0 END) as has_uuid,
    SUM(CASE WHEN scan_surat IS NOT NULL THEN 1 ELSE 0 END) as has_scan,
    SUM(CASE WHEN is_final = 1 THEN 1 ELSE 0 END) as finalized
FROM surat_masuk

UNION ALL

SELECT 
    'surat_keluar',
    COUNT(*),
    SUM(CASE WHEN uuid IS NOT NULL AND uuid != '' THEN 1 ELSE 0 END),
    SUM(CASE WHEN scan_surat IS NOT NULL THEN 1 ELSE 0 END),
    SUM(CASE WHEN is_final = 1 THEN 1 ELSE 0 END)
FROM surat_keluar;

SELECT '
🎯 NEXT STEPS:
1. Verify all columns added above
2. Check that UUIDs generated for existing records
3. Proceed to Step 2: File upload validation
' as next_action;

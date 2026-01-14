-- =====================================================
-- SURATQU GOVERNANCE SCHEMA - MINIMAL SAFE VERSION
-- Only adds columns that don't exist
-- Safe backfill only for columns that exist
-- =====================================================

USE sidiksae_suratqu;

SET @dbname = 'sidiksae_suratqu';

-- ============================================
-- SURAT_MASUK: Required Governance Fields
-- ============================================

-- Add columns only if they don't exist
ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id_sm,
ADD IF NOT EXISTS UNIQUE KEY idx_uuid (uuid);

ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS created_by CHAR(36) NULL COMMENT 'UUID from Identity' AFTER status;

ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS created_by_legacy INT(11) NULL AFTER created_by;

ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS source_app VARCHAR(50) DEFAULT 'suratqu' AFTER created_by_legacy;

ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS is_final TINYINT(1) DEFAULT 0 AFTER status;

ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS finalized_at DATETIME NULL AFTER is_final;

ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS finalized_by CHAR(36) NULL AFTER finalized_at;

ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS tgl_agenda DATETIME NULL AFTER tgl_diterima;

ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS tujuan VARCHAR(200) NULL AFTER perihal;

ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER finalized_by;

ALTER TABLE surat_masuk
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Rename file_path to scan_surat if column exists
SET @check_scan = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = @dbname 
                    AND TABLE_NAME = 'surat_masuk' 
                    AND COLUMN_NAME = 'scan_surat');

SET @check_file = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = @dbname 
                    AND TABLE_NAME = 'surat_masuk' 
                    AND COLUMN_NAME = 'file_path');

-- Only rename if file_path exists and scan_surat doesn't
ALTER TABLE surat_masuk
CHANGE COLUMN IF EXISTS file_path scan_surat VARCHAR(500) NULL;

-- ============================================
-- SURAT_KELUAR: Same Treatment (Minimal)
-- ============================================

ALTER TABLE surat_keluar
ADD COLUMN IF NOT EXISTS uuid CHAR(36) NULL AFTER id_sk,
ADD IF NOT EXISTS UNIQUE KEY idx_uuid_sk (uuid);

ALTER TABLE surat_keluar
ADD COLUMN IF NOT EXISTS created_by CHAR(36) NULL;

ALTER TABLE surat_keluar
ADD COLUMN IF NOT EXISTS source_app VARCHAR(50) DEFAULT 'suratqu';

ALTER TABLE surat_keluar
ADD COLUMN IF NOT EXISTS is_final TINYINT(1) DEFAULT 0;

ALTER TABLE surat_keluar
ADD COLUMN IF NOT EXISTS finalized_at DATETIME NULL;

ALTER TABLE surat_keluar
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE surat_keluar
CHANGE COLUMN IF EXISTS file_path scan_surat VARCHAR(500) NULL;

-- ============================================
-- BACKFILL: Only update what exists
-- ============================================

-- Generate UUIDs for records without them
UPDATE surat_masuk SET uuid = UUID() WHERE uuid IS NULL OR uuid = '';
UPDATE surat_keluar SET uuid = UUID() WHERE uuid IS NULL OR uuid = '';

-- Set source_app only if column exists (check first)
SET @has_source_masuk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_SCHEMA = @dbname 
                          AND TABLE_NAME = 'surat_masuk' 
                          AND COLUMN_NAME = 'source_app');

SET @has_source_keluar = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_SCHEMA = @dbname 
                           AND TABLE_NAME = 'surat_keluar' 
                           AND COLUMN_NAME = 'source_app');

-- Conditional backfill
SET @update_masuk = IF(@has_source_masuk > 0, 
                       "UPDATE surat_masuk SET source_app = 'suratqu' WHERE source_app IS NULL",
                       "SELECT 1");
PREPARE stmt FROM @update_masuk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @update_keluar = IF(@has_source_keluar > 0,
                        "UPDATE surat_keluar SET source_app = 'suratqu' WHERE source_app IS NULL",
                        "SELECT 1");
PREPARE stmt FROM @update_keluar;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- VERIFICATION
-- ============================================

SELECT 'Schema migration completed!' as status;

-- Show current schema
SHOW COLUMNS FROM surat_masuk;

-- Summary statistics
SELECT 
    'surat_masuk' as tabel,
    COUNT(*) as total_records,
    SUM(CASE WHEN uuid IS NOT NULL THEN 1 ELSE 0 END) as has_uuid,
    SUM(CASE WHEN scan_surat IS NOT NULL OR file_path IS NOT NULL THEN 1 ELSE 0 END) as has_file
FROM surat_masuk

UNION ALL

SELECT 
    'surat_keluar',
    COUNT(*),
    SUM(CASE WHEN uuid IS NOT NULL THEN 1 ELSE 0 END),
    SUM(CASE WHEN scan_surat IS NOT NULL OR file_path IS NOT NULL THEN 1 ELSE 0 END)
FROM surat_keluar;

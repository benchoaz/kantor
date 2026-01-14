-- =====================================================
-- SURATQU GOVERNANCE COMPLIANCE MIGRATION
-- Step 1: Schema Hardening for Administrative Validity
-- =====================================================
-- CRITICAL: This is REGULATORY COMPLIANCE, not optional
-- Without these fields: disposisi cacat administrasi
-- =====================================================

USE sidiksae_suratqu;

-- ============================================
-- SURAT MASUK: Add Mandatory Governance Fields
-- ============================================

-- 1. UUID (Immutable Primary Reference)
ALTER TABLE surat_masuk
ADD COLUMN uuid CHAR(36) NULL AFTER id_sm,
ADD UNIQUE KEY idx_uuid (uuid);

-- 2. Created By (Audit Trail - WHO created)
ALTER TABLE surat_masuk  
ADD COLUMN created_by CHAR(36) NULL COMMENT 'UUID from Identity Module' AFTER status,
ADD COLUMN created_by_legacy INT(11) NULL COMMENT 'Fallback to local user_id' AFTER created_by;

-- 3. Source App (System Identifier)
ALTER TABLE surat_masuk
ADD COLUMN source_app VARCHAR(50) DEFAULT 'suratqu' AFTER created_by_legacy;

-- 4. Scan Surat (WAJIB - Governance Rule)
-- Rename file_path to scan_surat for clarity
ALTER TABLE surat_masuk
CHANGE COLUMN file_path scan_surat VARCHAR(500) NULL COMMENT 'URL or path to scanned document';

-- 5. Final Status Marker
ALTER TABLE surat_masuk
ADD COLUMN is_final TINYINT(1) DEFAULT 0 COMMENT 'Ready for disposition' AFTER status,
ADD COLUMN finalized_at DATETIME NULL COMMENT 'When marked final' AFTER is_final,
ADD COLUMN finalized_by CHAR(36) NULL COMMENT 'UUID who finalized' AFTER finalized_at;

-- 6. Timestamps (Audit Trail - WHEN)
ALTER TABLE surat_masuk
ADD COLUMN tgl_agenda DATETIME NULL COMMENT 'Registration time' AFTER tgl_diterima,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER finalized_by,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- 7. Tujuan (Destination - for outgoing flow)
ALTER TABLE surat_masuk
ADD COLUMN tujuan VARCHAR(200) NULL COMMENT 'Destination for outgoing' AFTER perihal;

-- ============================================
-- SURAT KELUAR: Same Standards
-- ============================================

-- UUID
ALTER TABLE surat_keluar
ADD COLUMN uuid CHAR(36) NULL AFTER id_sk,
ADD UNIQUE KEY idx_uuid_sk (uuid);

-- Identity Integration
ALTER TABLE surat_keluar
CHANGE COLUMN id_user_pembuat created_by_legacy INT(11) NOT NULL,
ADD COLUMN created_by CHAR(36) NULL COMMENT 'UUID from Identity' AFTER created_by_legacy;

-- Source app
ALTER TABLE surat_keluar
ADD COLUMN source_app VARCHAR(50) DEFAULT 'suratqu' AFTER created_by;

-- Scan surat
ALTER TABLE surat_keluar
CHANGE COLUMN file_path scan_surat VARCHAR(500) NULL;

-- Finalization
ALTER TABLE surat_keluar
ADD COLUMN is_final TINYINT(1) DEFAULT 0 AFTER status,
ADD COLUMN finalized_at DATETIME NULL AFTER is_final;

-- Updated timestamp (created_at already exists)
ALTER TABLE surat_keluar
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- ============================================
-- CONSTRAINTS: Enforce Governance Rules
-- ============================================

-- Rule 1: scan_surat WAJIB when is_final=1
-- (Will be enforced in application layer - triggers too complex)

-- Rule 2: UUID must exist before is_final=1
-- (Application layer validation)

-- Rule 3: created_by must exist
-- (Application layer - gradual migration)

-- ============================================
-- INDEXES: Performance for UUID Queries
-- ============================================

-- Surat Masuk indexes
ALTER TABLE surat_masuk
ADD INDEX idx_created_by (created_by),
ADD INDEX idx_is_final (is_final),
ADD INDEX idx_source_app (source_app),
ADD INDEX idx_created_at (created_at);

-- Surat Keluar indexes  
ALTER TABLE surat_keluar
ADD INDEX idx_created_by_sk (created_by),
ADD INDEX idx_is_final_sk (is_final),
ADD INDEX idx_source_app_sk (source_app);

-- ============================================
-- BACKFILL: Generate UUIDs for Existing Data
-- ============================================

-- Generate UUIDs for existing surat_masuk (if any)
UPDATE surat_masuk 
SET uuid = UUID() 
WHERE uuid IS NULL;

-- Generate UUIDs for existing surat_keluar (if any)
UPDATE surat_keluar
SET uuid = UUID()
WHERE uuid IS NULL;

-- Set source_app for existing records
UPDATE surat_masuk SET source_app = 'suratqu' WHERE source_app IS NULL;
UPDATE surat_keluar SET source_app = 'suratqu' WHERE source_app IS NULL;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Verify surat_masuk schema
SHOW COLUMNS FROM surat_masuk;

-- Count records needing UUID
SELECT 
    COUNT(*) as total,
    COUNT(uuid) as has_uuid,
    COUNT(*) - COUNT(uuid) as missing_uuid
FROM surat_masuk;

-- Check for scan_surat coverage
SELECT 
    status,
    COUNT(*) as total,
    SUM(CASE WHEN scan_surat IS NOT NULL THEN 1 ELSE 0 END) as has_scan,
    SUM(CASE WHEN scan_surat IS NULL THEN 1 ELSE 0 END) as no_scan
FROM surat_masuk
GROUP BY status;

-- ============================================
-- ROLLBACK PLAN (If Needed)
-- ============================================

/*
-- Remove added columns (ONLY if absolutely necessary)
ALTER TABLE surat_masuk
DROP COLUMN uuid,
DROP COLUMN created_by,
DROP COLUMN created_by_legacy,
DROP COLUMN source_app,
DROP COLUMN is_final,
DROP COLUMN finalized_at,
DROP COLUMN finalized_by,
DROP COLUMN tgl_agenda,
DROP COLUMN created_at,
DROP COLUMN updated_at,
DROP COLUMN tujuan;

-- Rename scan_surat back to file_path
ALTER TABLE surat_masuk
CHANGE COLUMN scan_surat file_path VARCHAR(255) NULL;
*/

-- ============================================
-- SUCCESS MESSAGE
-- ============================================

SELECT 'Schema migration completed successfully!' as status,
       'SuratQu is now governance-compliant' as message,
       'Next: Enforce validation rules in PHP' as next_step;

-- ==================================================================
-- DISPOSISI ROLE-BASED ARCHITECTURE MIGRATION
-- ==================================================================
-- Date: 2026-01-10
-- Purpose: Migrate disposisi to role-based architecture
-- Strategy: SAFE MIGRATION (non-breaking, backfill, then enforce)
--
-- CRITICAL: from_user_id and to_user_id MUST be UUID (VARCHAR 36)
-- ==================================================================

USE sidiksae_api;

-- ==================================================================
-- PHASE 1: ADD COLUMNS (NON-BREAKING)
-- ==================================================================

-- Step 1.1: Add role columns to disposisi table
ALTER TABLE disposisi
ADD COLUMN from_role VARCHAR(50) NULL COMMENT 'Role pembuatdisposisi: pimpinan, sekcam, kasi, staff' AFTER created_by,
ADD COLUMN to_role VARCHAR(50) NULL COMMENT 'Target role penerima disposisi' AFTER from_role;

-- Step 1.2: Ensure user_id columns are UUID (VARCHAR 36)
-- Check current schema and alter if needed
-- NOTE: Run this ONLY if current schema uses INT

-- For from_user_id (created_by):
ALTER TABLE disposisi
MODIFY COLUMN created_by VARCHAR(36) NULL COMMENT 'UUID of user who created disposisi';

-- Step 1.3: Add indexes for role-based queries
ALTER TABLE disposisi
ADD INDEX idx_from_role (from_role),
ADD INDEX idx_to_role (to_role),
ADD INDEX idx_roles_flow (from_role, to_role),
ADD INDEX idx_to_role_status (to_role, status);

-- Step 1.4: Update disposisi_penerima to use UUID
ALTER TABLE disposisi_penerima
MODIFY COLUMN user_id VARCHAR(36) NOT NULL COMMENT 'UUID of recipient user';

-- ==================================================================
-- PHASE 2: CREATE AUDIT TABLE
-- ==================================================================

CREATE TABLE IF NOT EXISTS disposisi_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid_disposisi VARCHAR(36) NOT NULL COMMENT 'Disposisi UUID',
    user_id VARCHAR(36) NOT NULL COMMENT 'UUID from Identity Module',
    user_role VARCHAR(50) NOT NULL COMMENT 'Role: pimpinan, sekcam, kasi, staff',
    action VARCHAR(50) NOT NULL COMMENT 'CREATE, READ, DONE, CANCEL, UPDATE',
    uuid_surat VARCHAR(36) NOT NULL COMMENT 'Immutable surat reference',
    to_role VARCHAR(50) NULL COMMENT 'Target role for CREATE action',
    metadata JSON NULL COMMENT 'Action details: instruksi, sifat, etc',
    ip_address VARCHAR(45) NULL COMMENT 'Client IP for forensics',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_disposisi (uuid_disposisi),
    INDEX idx_surat (uuid_surat),
    INDEX idx_user_action (user_id, action),
    INDEX idx_user_role (user_role),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail for all disposisi actions (BPK/Inspektorat ready)';

-- ==================================================================
-- PHASE 3: VERIFICATION QUERIES
-- ==================================================================

-- Check schema changes
SHOW COLUMNS FROM disposisi WHERE Field IN ('from_role', 'to_role', 'created_by');

-- Check indexes
SHOW INDEX FROM disposisi WHERE Key_name LIKE 'idx_%role%';

-- Check audit table
DESCRIBE disposisi_audit;

-- Count existing disposisi (for backfill planning)
SELECT 
    COUNT(*) as total_disposisi,
    COUNT(DISTINCT created_by) as unique_creators,
    COUNT(DISTINCT uuid_surat) as unique_surat
FROM disposisi;

-- ==================================================================
-- ROLLBACK PLAN (If needed)
-- ==================================================================

/*
-- Remove added columns
ALTER TABLE disposisi
DROP COLUMN from_role,
DROP COLUMN to_role;

-- Remove indexes
ALTER TABLE disposisi
DROP INDEX idx_from_role,
DROP INDEX idx_to_role,
DROP INDEX idx_roles_flow,
DROP INDEX idx_to_role_status;

-- Drop audit table
DROP TABLE IF EXISTS disposisi_audit;
*/

-- ==================================================================
-- NOTES FOR DBA
-- ==================================================================
-- 1. This migration is SAFE - adds columns without dropping anything
-- 2. Existing disposisi will have NULL role values temporarily
-- 3. Run PHASE 4 (backfill) script after this completes
-- 4. After backfill verification, enforce NOT NULL in PHASE 5
-- ==================================================================

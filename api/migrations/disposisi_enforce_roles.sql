-- ==================================================================
-- DISPOSISI ROLE-BASED ARCHITECTURE - ENFORCEMENT
-- ==================================================================
-- Date: 2026-01-10
-- Purpose: Make role columns mandatory after backfill
-- Prerequisites: PHASE 4 backfill completed and verified
--
-- WARNING: Run this ONLY after verifying backfill success
-- ==================================================================

USE sidiksae_api;

-- ==================================================================
-- PHASE 8: ENFORCE NOT NULL
-- ==================================================================

-- Step 8.1: Make from_role mandatory
ALTER TABLE disposisi
MODIFY COLUMN from_role VARCHAR(50) NOT NULL COMMENT 'Role pembuat disposisi: pimpinan, sekcam, kasi, staff';

-- Step 8.2: Make to_role mandatory
ALTER TABLE disposisi
MODIFY COLUMN to_role VARCHAR(50) NOT NULL COMMENT 'Target role penerima disposisi';

-- ==================================================================
-- PHASE 9: ADD CONSTRAINTS
-- ==================================================================

-- Step 9.1: Drop existing constraints if they exist (from previous migration)
ALTER TABLE disposisi
DROP CONSTRAINT IF EXISTS chk_from_role;

ALTER TABLE disposisi
DROP CONSTRAINT IF EXISTS chk_to_role;

-- Step 9.2: Add CHECK constraint for valid roles (MySQL 8.0.16+)
-- If MySQL version < 8.0.16, skip this and handle in application

ALTER TABLE disposisi
ADD CONSTRAINT chk_from_role 
CHECK (from_role IN ('pimpinan', 'sekcam', 'kasi', 'staff'));

ALTER TABLE disposisi
ADD CONSTRAINT chk_to_role 
CHECK (to_role IN ('pimpinan', 'sekcam', 'kasi', 'staff'));

-- ==================================================================
-- VERIFICATION
-- ==================================================================

-- Confirm NOT NULL constraints
SHOW COLUMNS FROM disposisi WHERE Field IN ('from_role', 'to_role');

-- Test INSERT (should fail if role is NULL)
-- INSERT INTO disposisi (uuid, uuid_surat, from_role, to_role) 
-- VALUES ('test', 'test', NULL, 'sekcam'); -- Should fail

-- ==================================================================
-- ROLLBACK (Emergency only)
-- ==================================================================

/*
-- Remove constraints
ALTER TABLE disposisi
DROP CONSTRAINT chk_from_role,
DROP CONSTRAINT chk_to_role;

-- Make columns nullable again
ALTER TABLE disposisi
MODIFY COLUMN from_role VARCHAR(50) NULL,
MODIFY COLUMN to_role VARCHAR(50) NULL;
*/

-- ==================================================================
-- MIGRATION COMPLETE
-- ==================================================================
-- Next steps:
-- 1. Update DisposisiController to use new role fields
-- 2. Add flow validation logic
-- 3. Update Camat/Docku clients to send role in payload
-- ==================================================================

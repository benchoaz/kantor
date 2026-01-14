-- ==================================================================
-- DISPOSISI CLEAN START - Remove Legacy Test Data
-- ==================================================================
-- Date: 2026-01-10
-- Purpose: Clear broken test data before implementing role-based system
-- Reason: Old disposisi never worked (created_by=NULL, never reached Docku)
-- ==================================================================

USE sidiksae_api;

-- ==================================================================
-- SAFETY CHECK: Backup count before deletion
-- ==================================================================

SELECT 
    'BEFORE DELETE' as status,
    (SELECT COUNT(*) FROM surat) as surat_count,
    (SELECT COUNT(*) FROM disposisi) as disposisi_count,
    (SELECT COUNT(*) FROM disposisi_penerima) as penerima_count,
    (SELECT COUNT(*) FROM disposisi_status) as status_count,
    (SELECT COUNT(*) FROM instruksi) as instruksi_count,
    (SELECT COUNT(*) FROM instruksi_penerima) as instruksi_penerima_count;

-- ==================================================================
-- PHASE 1: DELETE IN CORRECT ORDER (Child → Parent)
-- ==================================================================

-- Step 1: Delete instruksi_penerima (child of instruksi)
DELETE FROM instruksi_penerima;
SELECT ROW_COUNT() as deleted_instruksi_penerima;

-- Step 2: Delete instruksi (child of disposisi)
DELETE FROM instruksi;
SELECT ROW_COUNT() as deleted_instruksi;

-- Step 3: Delete disposisi_status (child of disposisi)
DELETE FROM disposisi_status;
SELECT ROW_COUNT() as deleted_disposisi_status;

-- Step 4: Delete disposisi_penerima (child of disposisi)
DELETE FROM disposisi_penerima;
SELECT ROW_COUNT() as deleted_disposisi_penerima;

-- Step 5: Delete disposisi (references surat)
DELETE FROM disposisi;
SELECT ROW_COUNT() as deleted_disposisi;

-- Step 6: Delete surat (parent - all test data)
DELETE FROM surat;
SELECT ROW_COUNT() as deleted_surat;

-- ==================================================================
-- VERIFICATION: All tables should be empty
-- ==================================================================

SELECT 
    'AFTER DELETE' as status,
    (SELECT COUNT(*) FROM surat) as surat_count,
    (SELECT COUNT(*) FROM disposisi) as disposisi_count,
    (SELECT COUNT(*) FROM disposisi_penerima) as penerima_count,
    (SELECT COUNT(*) FROM disposisi_status) as status_count,
    (SELECT COUNT(*) FROM instruksi) as instruksi_count,
    (SELECT COUNT(*) FROM instruksi_penerima) as instruksi_penerima_count;

-- ==================================================================
-- RESET AUTO_INCREMENT (Optional - Fresh IDs)
-- ==================================================================

ALTER TABLE surat AUTO_INCREMENT = 1;
ALTER TABLE disposisi AUTO_INCREMENT = 1;
ALTER TABLE disposisi_penerima AUTO_INCREMENT = 1;
ALTER TABLE disposisi_status AUTO_INCREMENT = 1;
ALTER TABLE instruksi AUTO_INCREMENT = 1;
ALTER TABLE instruksi_penerima AUTO_INCREMENT = 1;

-- ==================================================================
-- FINAL VERIFICATION
-- ==================================================================

-- All counts should be 0
SELECT 
    'FINAL CHECK' as status,
    CASE 
        WHEN (SELECT COUNT(*) FROM surat) = 0
        AND (SELECT COUNT(*) FROM disposisi) = 0 
        AND (SELECT COUNT(*) FROM disposisi_penerima) = 0 
        AND (SELECT COUNT(*) FROM disposisi_status) = 0
        THEN '✅ CLEAN - Ready for role-based disposisi & real surat data'
        ELSE '⚠️ ERROR - Some records remain'
    END as result;

-- ==================================================================
-- NOTES
-- ==================================================================
-- After this cleanup:
-- 1. Run Phase 3: disposisi_enforce_roles.sql (make roles NOT NULL)
-- 2. Update DisposisiController to enforce role-based validation
-- 3. All new disposisi will be role-based from day 1
-- ==================================================================

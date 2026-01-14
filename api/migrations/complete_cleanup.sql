-- ==================================================================
-- COMPLETE CLEANUP - DISPOSISI & SURAT (Test Data)
-- ==================================================================
-- Date: 2026-01-10
-- Purpose: Remove all test data from sidiksae_api database
-- IMPORTANT: Make sure you're in the correct database!
-- ==================================================================

-- Force correct database
USE sidiksae_api;

-- Disable foreign key checks for clean deletion
SET FOREIGN_KEY_CHECKS = 0;

-- ==================================================================
-- BACKUP COUNT
-- ==================================================================

SELECT 
    '=== BEFORE DELETE ===' as status,
    (SELECT COUNT(*) FROM surat) as surat_count,
    (SELECT COUNT(*) FROM disposisi) as disposisi_count,
    (SELECT COUNT(*) FROM disposisi_penerima) as penerima_count,
    (SELECT COUNT(*) FROM disposisi_status) as status_count,
    (SELECT COUNT(*) FROM instruksi) as instruksi_count,
    (SELECT COUNT(*) FROM instruksi_penerima) as instruksi_penerima_count;

-- ==================================================================
-- DELETE ALL TEST DATA
-- ==================================================================

-- Step 1: Children of instruksi
TRUNCATE TABLE instruksi_penerima;

-- Step 2: Children of disposisi
TRUNCATE TABLE instruksi;
TRUNCATE TABLE disposisi_status;
TRUNCATE TABLE disposisi_penerima;

-- Step 3: Disposisi
TRUNCATE TABLE disposisi;

-- Step 4: Surat (parent)
TRUNCATE TABLE surat;

-- ==================================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- ==================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ==================================================================
-- VERIFICATION
-- ==================================================================

SELECT 
    '=== AFTER DELETE ===' as status,
    (SELECT COUNT(*) FROM surat) as surat_count,
    (SELECT COUNT(*) FROM disposisi) as disposisi_count,
    (SELECT COUNT(*) FROM disposisi_penerima) as penerima_count,
    (SELECT COUNT(*) FROM disposisi_status) as status_count,
    (SELECT COUNT(*) FROM instruksi) as instruksi_count,
    (SELECT COUNT(*) FROM instruksi_penerima) as instruksi_penerima_count;

-- ==================================================================
-- FINAL CHECK
-- ==================================================================

SELECT 
    '=== FINAL STATUS ===' as status,
    CASE 
        WHEN (SELECT COUNT(*) FROM surat) = 0
        AND (SELECT COUNT(*) FROM disposisi) = 0
        THEN '✅ SUCCESS - All test data removed'
        ELSE '❌ ERROR - Some data remains'
    END as result;

-- ==================================================================
-- NOTES
-- ==================================================================
-- ✅ All counts should be 0
-- ✅ AUTO_INCREMENT reset by TRUNCATE
-- ✅ Next inserts will start from ID 1
-- ✅ Database: sidiksae_api (verified)
-- ==================================================================

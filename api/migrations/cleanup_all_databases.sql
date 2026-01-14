-- ==================================================================
-- COMPREHENSIVE CLEANUP - BOTH DATABASES
-- ==================================================================
-- Date: 2026-01-10
-- Purpose: Remove ALL test surat from SuratQu AND API databases
-- WARNING: This will delete all uploaded surat + disposisi
-- ==================================================================

-- ==================================================================
-- PART 1: CLEANUP SIDIKSAE_SURATQU (SuratQu uploaded surat)
-- ==================================================================

USE sidiksae_suratqu;

SET FOREIGN_KEY_CHECKS = 0;

-- Backup count
SELECT 
    'SURATQU - BEFORE DELETE' as status,
    (SELECT COUNT(*) FROM surat_masuk) as surat_masuk_count,
    (SELECT COUNT(*) FROM disposisi) as disposisi_count;

-- Delete disposisi first (child table)
DELETE FROM disposisi;

-- Delete all surat from SuratQu (parent table)
DELETE FROM surat_masuk;

-- Verify
SELECT 
    'SURATQU - AFTER DELETE' as status,
    (SELECT COUNT(*) FROM surat_masuk) as surat_masuk_count,
    (SELECT COUNT(*) FROM disposisi) as disposisi_count;

SET FOREIGN_KEY_CHECKS = 1;

-- ==================================================================
-- PART 2: CLEANUP SIDIKSAE_API (Disposisi + References)
-- ==================================================================

USE sidiksae_api;

SET FOREIGN_KEY_CHECKS = 0;

-- Backup count
SELECT 
    'API - BEFORE DELETE' as status,
    (SELECT COUNT(*) FROM surat) as surat_count,
    (SELECT COUNT(*) FROM disposisi) as disposisi_count,
    (SELECT COUNT(*) FROM disposisi_penerima) as penerima_count,
    (SELECT COUNT(*) FROM disposisi_status) as status_count,
    (SELECT COUNT(*) FROM instruksi) as instruksi_count,
    (SELECT COUNT(*) FROM instruksi_penerima) as instruksi_penerima_count;

-- Delete disposisi chain
TRUNCATE TABLE instruksi_penerima;
TRUNCATE TABLE instruksi;
TRUNCATE TABLE disposisi_status;
TRUNCATE TABLE disposisi_penerima;
TRUNCATE TABLE disposisi;

-- Delete surat references
TRUNCATE TABLE surat;

-- Verify
SELECT 
    'API - AFTER DELETE' as status,
    (SELECT COUNT(*) FROM surat) as surat_count,
    (SELECT COUNT(*) FROM disposisi) as disposisi_count;

SET FOREIGN_KEY_CHECKS = 1;

-- ==================================================================
-- FINAL VERIFICATION
-- ==================================================================

SELECT 'CLEANUP COMPLETE' as status;

-- Check SuratQu
SELECT 
    'sidiksae_suratqu' as database_name,
    (SELECT COUNT(*) FROM sidiksae_suratqu.surat_masuk) as surat_masuk_count,
    (SELECT COUNT(*) FROM sidiksae_suratqu.disposisi) as disposisi_count;

-- Check API
SELECT 
    'sidiksae_api' as database_name,
    (SELECT COUNT(*) FROM sidiksae_api.surat) as surat_count,
    (SELECT COUNT(*) FROM sidiksae_api.disposisi) as disposisi_count;

-- Final result
SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM sidiksae_suratqu.surat_masuk) = 0
        AND (SELECT COUNT(*) FROM sidiksae_suratqu.disposisi) = 0
        AND (SELECT COUNT(*) FROM sidiksae_api.surat) = 0
        AND (SELECT COUNT(*) FROM sidiksae_api.disposisi) = 0
        THEN '✅ SUCCESS - All test data removed from BOTH databases'
        ELSE '❌ ERROR - Some data remains'
    END as final_result;

-- ==================================================================
-- NOTES
-- ==================================================================
-- ✅ DELETED FROM SURATQU:
--    - All uploaded surat (from SuratQu app)
--
-- ✅ DELETED FROM API:
--    - All surat references
--    - All disposisi + related tables
--
-- 🎯 READY FOR:
--    - Fresh surat upload from SuratQu
--    - Role-based disposisi from Camat
--
-- 🔄 NEXT STEPS:
--    1. Upload real surat from SuratQu
--    2. Run Phase 3: disposisi_enforce_roles.sql
--    3. Update DisposisiController
--    4. Test Camat → Docku flow
-- ==================================================================

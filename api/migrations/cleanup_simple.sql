-- ==================================================================
-- SAFE CLEANUP - FULLY QUALIFIED TABLE NAMES
-- ==================================================================
-- Date: 2026-01-10
-- Purpose: Clean all test data with explicit database.table notation
-- ==================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ==================================================================
-- BACKUP COUNTS
-- ==================================================================

SELECT 'BEFORE DELETE - SURATQU' as status,
    (SELECT COUNT(*) FROM sidiksae_suratqu.surat_masuk) as surat_masuk,
    (SELECT COUNT(*) FROM sidiksae_suratqu.disposisi) as disposisi;

SELECT 'BEFORE DELETE - API' as status,
    (SELECT COUNT(*) FROM sidiksae_api.surat) as surat,
    (SELECT COUNT(*) FROM sidiksae_api.disposisi) as disposisi,
    (SELECT COUNT(*) FROM sidiksae_api.disposisi_penerima) as penerima,
    (SELECT COUNT(*) FROM sidiksae_api.instruksi) as instruksi;

-- ==================================================================
-- DELETE SURATQU DATABASE
-- ==================================================================

DELETE FROM sidiksae_suratqu.disposisi;
DELETE FROM sidiksae_suratqu.surat_masuk;

-- ==================================================================
-- DELETE API DATABASE
-- ==================================================================

DELETE FROM sidiksae_api.instruksi_penerima;
DELETE FROM sidiksae_api.instruksi;
DELETE FROM sidiksae_api.disposisi_status;
DELETE FROM sidiksae_api.disposisi_penerima;
DELETE FROM sidiksae_api.disposisi;
DELETE FROM sidiksae_api.surat;

SET FOREIGN_KEY_CHECKS = 1;

-- ==================================================================
-- VERIFICATION
-- ==================================================================

SELECT 'AFTER DELETE - SURATQU' as status,
    (SELECT COUNT(*) FROM sidiksae_suratqu.surat_masuk) as surat_masuk,
    (SELECT COUNT(*) FROM sidiksae_suratqu.disposisi) as disposisi;

SELECT 'AFTER DELETE - API' as status,
    (SELECT COUNT(*) FROM sidiksae_api.surat) as surat,
    (SELECT COUNT(*) FROM sidiksae_api.disposisi) as disposisi;

-- ==================================================================
-- FINAL CHECK
-- ==================================================================

SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM sidiksae_suratqu.surat_masuk) = 0
        AND (SELECT COUNT(*) FROM sidiksae_suratqu.disposisi) = 0
        AND (SELECT COUNT(*) FROM sidiksae_api.surat) = 0
        AND (SELECT COUNT(*) FROM sidiksae_api.disposisi) = 0
        THEN '✅ SUCCESS - All databases clean'
        ELSE '❌ FAILED - Data remains'
    END as result;

-- ==================================================================
-- READY FOR PHASE 3: disposisi_enforce_roles.sql
-- ==================================================================

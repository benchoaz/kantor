-- ==================================================================
-- DISPOSISI ROLE-BASED ARCHITECTURE - BACKFILL SCRIPT
-- ==================================================================
-- Date: 2026-01-10
-- Purpose: Populate role columns from existing user data
-- Prerequisites: PHASE 1 migration completed successfully
--
-- IMPORTANT: This backfills from_role and to_role based on user UUID
-- ==================================================================

USE sidiksae_api;

-- ==================================================================
-- PHASE 4: BACKFILL FROM_ROLE
-- ==================================================================

-- Step 4.1: Update from_role by joining with users table
-- Maps created_by (UUID) → user.role → from_role
-- FIX: Added COLLATE to handle collation mismatch

UPDATE disposisi d
INNER JOIN users u ON d.created_by COLLATE utf8mb4_unicode_ci = u.uuid_user COLLATE utf8mb4_unicode_ci
SET d.from_role = u.role
WHERE d.from_role IS NULL
  AND d.created_by IS NOT NULL;

-- Verification
SELECT 
    COUNT(*) as total,
    COUNT(from_role) as has_from_role,
    COUNT(*) - COUNT(from_role) as missing_from_role
FROM disposisi;

-- ==================================================================
-- PHASE 5: BACKFILL TO_ROLE
-- ==================================================================

-- Step 5.1: Update to_role from disposisi_penerima
-- Get most common recipient role for each disposisi
-- FIX: Added COLLATE to handle collation mismatch

UPDATE disposisi d
SET d.to_role = (
    SELECT u.role
    FROM disposisi_penerima dp
    INNER JOIN users u ON dp.user_id COLLATE utf8mb4_unicode_ci = u.uuid_user COLLATE utf8mb4_unicode_ci
    WHERE dp.disposisi_uuid COLLATE utf8mb4_unicode_ci = d.uuid COLLATE utf8mb4_unicode_ci
    LIMIT 1
)
WHERE d.to_role IS NULL;

-- Verification
SELECT 
    COUNT(*) as total,
    COUNT(to_role) as has_to_role,
    COUNT(*) - COUNT(to_role) as missing_to_role
FROM disposisi;

-- ==================================================================
-- PHASE 6: HANDLE EDGE CASES
-- ==================================================================

-- Step 6.1: Find disposisi with missing creator (orphaned)
SELECT 
    uuid,
    uuid_surat,
    created_by,
    created_at
FROM disposisi
WHERE from_role IS NULL
ORDER BY created_at DESC
LIMIT 10;

-- Step 6.2: Find disposisi with missing recipients
SELECT 
    d.uuid,
    d.uuid_surat,
    d.created_by,
    d.created_at
FROM disposisi d
LEFT JOIN disposisi_penerima dp ON d.uuid = dp.disposisi_uuid
WHERE d.to_role IS NULL
  AND dp.id IS NULL
ORDER BY d.created_at DESC
LIMIT 10;

-- Step 6.3: Default handling for orphaned records (optional)
-- Set default role if creator not found
UPDATE disposisi
SET from_role = 'pimpinan'
WHERE from_role IS NULL
  AND created_by IS NOT NULL;

-- Set default role if no recipients found
UPDATE disposisi
SET to_role = 'sekcam'
WHERE to_role IS NULL;

-- ==================================================================
-- PHASE 7: FINAL VERIFICATION
-- ==================================================================

-- Distribution by from_role
SELECT 
    from_role,
    COUNT(*) as total,
    GROUP_CONCAT(DISTINCT to_role) as sends_to_roles
FROM disposisi
WHERE from_role IS NOT NULL
GROUP BY from_role
ORDER BY total DESC;

-- Distribution by to_role
SELECT 
    to_role,
    COUNT(*) as total
FROM disposisi
WHERE to_role IS NOT NULL
GROUP BY to_role
ORDER BY total DESC;

-- Flow patterns (from → to)
SELECT 
    from_role,
    to_role,
    COUNT(*) as flow_count
FROM disposisi
WHERE from_role IS NOT NULL AND to_role IS NOT NULL
GROUP BY from_role, to_role
ORDER BY flow_count DESC;

-- Completeness check
SELECT 
    COUNT(*) as total_disposisi,
    SUM(CASE WHEN from_role IS NULL THEN 1 ELSE 0 END) as missing_from_role,
    SUM(CASE WHEN to_role IS NULL THEN 1 ELSE 0 END) as missing_to_role,
    SUM(CASE WHEN from_role IS NOT NULL AND to_role IS NOT NULL THEN 1 ELSE 0 END) as complete
FROM disposisi;

-- ==================================================================
-- NOTES
-- ==================================================================
-- Expected results:
-- 1. All disposisi should have from_role and to_role populated
-- 2. Common flows: pimpinan→sekcam, sekcam→kasi, kasi→staff
-- 3. Missing values indicate orphaned/corrupted data
-- 4. After verification, proceed to PHASE 8 (enforce NOT NULL)
-- ==================================================================

-- ==================================================================
-- DISPOSISI BACKFILL VERIFICATION
-- ==================================================================
-- Run this to verify Phase 2 backfill completed successfully
-- ==================================================================

USE sidiksae_api;

-- Check 1: Completeness
SELECT 
    COUNT(*) as total_disposisi,
    SUM(CASE WHEN from_role IS NULL THEN 1 ELSE 0 END) as missing_from_role,
    SUM(CASE WHEN to_role IS NULL THEN 1 ELSE 0 END) as missing_to_role,
    SUM(CASE WHEN from_role IS NOT NULL AND to_role IS NOT NULL THEN 1 ELSE 0 END) as complete_records,
    ROUND(SUM(CASE WHEN from_role IS NOT NULL AND to_role IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as completeness_pct
FROM disposisi;

-- Check 2: Distribution by from_role
SELECT 
    from_role,
    COUNT(*) as total_sent
FROM disposisi
WHERE from_role IS NOT NULL
GROUP BY from_role
ORDER BY total_sent DESC;

-- Check 3: Distribution by to_role
SELECT 
    to_role,
    COUNT(*) as total_received
FROM disposisi
WHERE to_role IS NOT NULL
GROUP BY to_role
ORDER BY total_received DESC;

-- Check 4: Flow patterns (from → to)
SELECT 
    from_role,
    to_role,
    COUNT(*) as flow_count
FROM disposisi
WHERE from_role IS NOT NULL AND to_role IS NOT NULL
GROUP BY from_role, to_role
ORDER BY flow_count DESC;

-- Check 5: Sample records
SELECT 
    uuid,
    from_role,
    to_role,
    status,
    created_at
FROM disposisi
ORDER BY created_at DESC
LIMIT 5;

-- ==================================================================
-- EXPECTED RESULTS:
-- ==================================================================
-- ✅ completeness_pct should be 100% or close to it
-- ✅ Common roles: pimpinan, sekcam, kasi, staff
-- ✅ Common flows: pimpinan→sekcam, sekcam→kasi, kasi→staff
-- ❌ If missing_from_role or missing_to_role > 0, investigate orphaned records
-- ==================================================================

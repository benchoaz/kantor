-- ============================================
-- PHASE 1 SAFE MIGRATION - STEP 3
-- Legacy DB Mapping: Add uuid_user columns
-- ============================================
-- 
-- CRITICAL RULES:
-- ❌ DO NOT delete any existing data
-- ❌ DO NOT drop any tables
-- ❌ DO NOT modify existing password columns
-- ✅ ADD columns as NULL (safe rollback)
-- ✅ CREATE indexes for performance
-- ✅ SYNC usernames to Identity Module UUIDs
--
-- Target: sidiksae_api (shared by API, Docku, possibly SuratQu)
-- ============================================

USE sidiksae_api;

-- Check if uuid_user column already exists (idempotent)
SET @col_exists = (SELECT COUNT(*) 
                   FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'sidiksae_api' 
                   AND TABLE_NAME = 'users' 
                   AND COLUMN_NAME = 'uuid_user');

-- Add uuid_user column if not exists
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN uuid_user CHAR(36) NULL COMMENT ''UUID from Identity Module'' AFTER id',
    'SELECT ''uuid_user column already exists'' AS status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for uuid_user lookups
SET @idx_exists = (SELECT COUNT(*) 
                   FROM INFORMATION_SCHEMA.STATISTICS 
                   WHERE TABLE_SCHEMA = 'sidiksae_api' 
                   AND TABLE_NAME = 'users' 
                   AND INDEX_NAME = 'idx_uuid_user');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE users ADD INDEX idx_uuid_user (uuid_user)',
    'SELECT ''idx_uuid_user already exists'' AS status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Create user_identity_mapping table
-- For tracking sync between legacy and Identity
-- ============================================

CREATE TABLE IF NOT EXISTS user_identity_mapping (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    local_user_id INT UNSIGNED NOT NULL COMMENT 'From users.id',
    uuid_user CHAR(36) NOT NULL COMMENT 'From Identity Module',
    username VARCHAR(50) NOT NULL COMMENT 'For reference',
    sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_local_user (local_user_id),
    UNIQUE KEY unique_uuid (uuid_user),
    INDEX idx_username (username),
    INDEX idx_status (sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='PHASE 1: Mapping legacy users to Identity Module UUIDs';

-- ============================================
-- Sync existing users (using UUID v5 generation)
-- ============================================
-- NOTE: This uses placeholder logic
-- Real sync should call Identity Module API or use UuidHelper
-- ============================================

-- For now, mark all users as needing sync
INSERT IGNORE INTO user_identity_mapping (local_user_id, uuid_user, username, sync_status)
SELECT 
    id,
    CONCAT('pending-', id) AS uuid_user, -- Placeholder
    username,
    'pending'
FROM users
WHERE username IS NOT NULL;

-- ============================================
-- Add sync metadata columns
-- ============================================

SET @col_exists = (SELECT COUNT(*) 
                   FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'sidiksae_api' 
                   AND TABLE_NAME = 'users' 
                   AND COLUMN_NAME = 'identity_sync_at');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN identity_sync_at TIMESTAMP NULL COMMENT ''Last sync with Identity Module''',
    'SELECT ''identity_sync_at already exists'' AS status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Final verification
-- ============================================

SELECT 
    'Migration Complete' AS status,
    COUNT(*) AS total_users,
    SUM(CASE WHEN uuid_user IS NOT NULL THEN 1 ELSE 0 END) AS users_with_uuid,
    SUM(CASE WHEN uuid_user IS NULL THEN 1 ELSE 0 END) AS users_pending_uuid
FROM users;

SELECT 
    'Mapping Table Status' AS status,
    COUNT(*) AS total_mappings,
    SUM(CASE WHEN sync_status = 'pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN sync_status = 'synced' THEN 1 ELSE 0 END) AS synced,
    SUM(CASE WHEN sync_status = 'failed' THEN 1 ELSE 0 END) AS failed
FROM user_identity_mapping;

-- ============================================
-- ROLLBACK PLAN (if needed)
-- ============================================
-- ALTER TABLE users DROP COLUMN uuid_user;
-- DROP TABLE user_identity_mapping;
-- ============================================

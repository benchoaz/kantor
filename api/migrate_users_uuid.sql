-- Migration: Add UUID to Users Table
-- Date: 2026-01-08
-- Purpose: Enable UUID-based user identification for API endpoints

-- 1. Add UUID column
ALTER TABLE users ADD COLUMN uuid CHAR(36) AFTER id;

-- 2. Populate UUIDs for existing users
UPDATE users SET uuid = UUID() WHERE uuid IS NULL OR uuid = '';

-- 3. Make UUID required and unique
ALTER TABLE users MODIFY uuid CHAR(36) NOT NULL;
CREATE UNIQUE INDEX idx_users_uuid ON users(uuid);

-- 4. Verify migration
SELECT 'Migration Complete' as status, 
       COUNT(*) as total_users,
       COUNT(uuid) as users_with_uuid
FROM users;

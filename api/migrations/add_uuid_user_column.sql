-- Add uuid_user column to API users table
-- Since the column doesn't exist, we need to ADD it (not RENAME)

USE sidiksae_api;

-- Add uuid_user column
ALTER TABLE users ADD COLUMN uuid_user VARCHAR(36) NULL AFTER id;

-- Optional: Create index for performance
CREATE INDEX idx_uuid_user ON users(uuid_user);

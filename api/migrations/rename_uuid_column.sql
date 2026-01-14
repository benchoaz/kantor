-- Standardize UUID Column Name in API Database
-- Rename 'uuid' to 'uuid_user' for consistency with Identity Module

USE sidiksae_api;

-- Rename column in users table
ALTER TABLE users CHANGE COLUMN uuid uuid_user VARCHAR(36) NOT NULL;

-- Optional: Verify the change
SHOW COLUMNS FROM users LIKE 'uuid%';

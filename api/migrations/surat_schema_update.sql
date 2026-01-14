-- =====================================================
-- SURAT TABLE SCHEMA UPDATE
-- Add missing columns for API integration
-- =====================================================

USE sidiksae_api;

-- Add source_app column if not exists
ALTER TABLE surat 
ADD COLUMN IF NOT EXISTS source_app VARCHAR(50) DEFAULT 'suratqu' AFTER file_path,
ADD COLUMN IF NOT EXISTS external_id VARCHAR(100) NULL AFTER source_app,
ADD COLUMN IF NOT EXISTS metadata JSON NULL AFTER external_id;

-- Update existing records to set source_app
UPDATE surat SET source_app = 'suratqu' WHERE source_app IS NULL;

-- Verify columns added
SHOW COLUMNS FROM surat LIKE '%source%';
SHOW COLUMNS FROM surat LIKE '%metadata%';

SELECT 'Schema updated successfully' as status;

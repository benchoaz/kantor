-- Migration: Add metadata columns to surat table
-- Run this via phpMyAdmin

-- Add new columns
ALTER TABLE `surat` 
ADD COLUMN `source_app` VARCHAR(50) DEFAULT 'suratqu' AFTER `origin_app`,
ADD COLUMN `external_id` INT(11) NULL AFTER `source_app`,
ADD COLUMN `metadata` JSON NULL AFTER `external_id`,
ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`;

-- Update existing rows - migrate data to metadata JSON
UPDATE `surat` 
SET `metadata` = JSON_OBJECT(
    'file_hash', file_hash,
    'file_size', file_size,
    'mime_type', mime_type
),
`source_app` = COALESCE(origin_app, 'suratqu'),
`updated_at` = created_at
WHERE `metadata` IS NULL;

-- Create index for source_app
CREATE INDEX idx_source_app ON `surat`(source_app);

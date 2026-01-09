-- Migration: Fix constraints for Surat Registration Logging
-- Run this in sidiksae_suratqu database (SuratQu DB)

-- 1. Make disposisi_id nullable in integrasi_docku_log
-- This allows logging registration before a disposition exists
ALTER TABLE `integrasi_docku_log` MODIFY COLUMN `disposisi_id` INT(11) NULL;

-- 2. Ensure status column in surat_masuk can accept 'terdaftar'
-- If it's a VARCHAR, this is just a safety check. If it's an ENUM, it adds the value.
-- We'll try to detect if it's an ENUM or VARCHAR by just modifying it to VARCHAR for maximum flexibility.
ALTER TABLE `surat_masuk` MODIFY COLUMN `status` VARCHAR(50) DEFAULT 'draft';

-- 3. Add index to uuid in surat_masuk for faster API status lookups
ALTER TABLE `surat_masuk` ADD INDEX IF NOT EXISTS `idx_surat_uuid` (`uuid`);

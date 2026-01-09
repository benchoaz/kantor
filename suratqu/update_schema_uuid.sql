ALTER TABLE `surat_masuk` 
ADD COLUMN `uuid` CHAR(36) NOT NULL AFTER `id_sm`,
ADD INDEX `idx_uuid` (`uuid`);

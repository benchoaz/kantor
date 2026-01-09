-- Table to store Strict File Metadata
-- Single Source of Truth for File Integrity
CREATE TABLE IF NOT EXISTS `surat` (
  `uuid` CHAR(36) NOT NULL,
  `file_hash` CHAR(64) NOT NULL COMMENT 'SHA256 Hash of original PDF',
  `file_path` VARCHAR(255) NOT NULL COMMENT 'Relative storage path in SuratQu',
  `file_size` BIGINT UNSIGNED NOT NULL,
  `mime_type` VARCHAR(100) DEFAULT 'application/pdf',
  `origin_app` VARCHAR(50) DEFAULT 'SuratQu',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uuid`),
  INDEX `idx_hash` (`file_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

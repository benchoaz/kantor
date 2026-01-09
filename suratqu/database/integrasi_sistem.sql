-- SidikSae API Integration - Database Schema Update
-- Menambahkan kolom untuk mendukung centralized API logging

-- Create table if not exists
CREATE TABLE IF NOT EXISTS `integrasi_docku_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disposisi_id` int(11) NOT NULL,
  `payload_hash` varchar(64) DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `response_code` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_disposisi_id` (`disposisi_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update existing table structure (backward compatible)
-- No need for individual ADD COLUMN if the table is created new,
-- but adding for safety if table already exists without these columns.
ALTER TABLE integrasi_docku_log 
  ADD COLUMN IF NOT EXISTS payload TEXT AFTER payload_hash,
  ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER response_body,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Create index for better query performance
CREATE INDEX IF NOT EXISTS idx_status ON integrasi_docku_log(status);
CREATE INDEX IF NOT EXISTS idx_disposisi_id ON integrasi_docku_log(disposisi_id);
CREATE INDEX IF NOT EXISTS idx_created_at ON integrasi_docku_log(created_at DESC);

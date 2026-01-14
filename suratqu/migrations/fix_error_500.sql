-- =====================================================
-- FIX SURATQU ERROR 500 - Missing Table
-- =====================================================
-- File: suratqu_fix_error_500.sql
-- Date: 2026-01-10
-- Problem: integrasi_docku_log table doesn't exist
-- =====================================================

USE sidiksae_suratqu;

-- Create missing log table
CREATE TABLE IF NOT EXISTS integrasi_docku_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    disposisi_id INT NULL COMMENT 'Reference to disposisi if applicable',
    payload_hash VARCHAR(64) NOT NULL COMMENT 'SHA256 hash of payload',
    payload TEXT NOT NULL COMMENT 'JSON payload sent to API',
    response_code INT NOT NULL COMMENT 'HTTP response code',
    response_body TEXT COMMENT 'API response body',
    status VARCHAR(20) NOT NULL COMMENT 'success or failed',
    created_at DATETIME NOT NULL,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log for SuratQu → API integration transmissions';

-- Verify table created
SELECT 'Table created successfully' as status, COUNT(*) as row_count 
FROM integrasi_docku_log;

-- =====================================================
-- NOTES:
-- This table logs all API transmissions from SuratQu
-- to the central API for file registration
-- =====================================================

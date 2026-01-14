-- =====================================================
-- STEP C1: API SURAT SCHEMA
-- Master Event Store for Surat
-- =====================================================

USE sidiksae_api;

-- Create surat table if not exists
CREATE TABLE IF NOT EXISTS surat (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE COMMENT 'UUID from source app',
    nomor_surat VARCHAR(100) NULL,
    tanggal_surat DATE NULL,
    pengirim VARCHAR(200) NULL COMMENT 'Asal surat / pengirim',
    perihal TEXT NULL,
    scan_surat VARCHAR(500) NOT NULL COMMENT 'URL to PDF file (wajib)',
    is_final TINYINT(1) DEFAULT 1 COMMENT '1 = final, ready for disposition',
    status VARCHAR(50) DEFAULT 'FINAL',
    source_app VARCHAR(50) DEFAULT 'suratqu' COMMENT 'Source: suratqu, camat, docku',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_uuid (uuid),
    INDEX idx_nomor (nomor_surat),
    INDEX idx_is_final (is_final),
    INDEX idx_source_app (source_app),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Master surat event store';

-- Verify structure
SHOW COLUMNS FROM surat;

SELECT 'Surat table ready for Step C1' as status;

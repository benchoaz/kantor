-- STEP C4: DISPOSISI SCHEMA (Master Event Store)

USE sidiksae_api;

-- 1. Tabel Disposisi (Header)
CREATE TABLE IF NOT EXISTS disposisi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    uuid_surat CHAR(36) NOT NULL,
    from_role VARCHAR(50) NOT NULL COMMENT 'Role pengirim (e.g. camat)',
    to_role VARCHAR(50) NOT NULL COMMENT 'Role penerima (e.g. sekcam)',
    sifat VARCHAR(50) DEFAULT 'BIASA',
    catatan TEXT,
    deadline DATE,
    status VARCHAR(50) DEFAULT 'BARU',
    created_by BIGINT NULL COMMENT 'User ID Pengirim (Local ID reference)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_uuid_surat (uuid_surat),
    INDEX idx_from_role (from_role),
    INDEX idx_to_role (to_role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Disposisi Penerima (Detail Penerima)
CREATE TABLE IF NOT EXISTS disposisi_penerima (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    disposisi_uuid CHAR(36) NOT NULL,
    user_id BIGINT NOT NULL COMMENT 'Target User ID (Local)',
    tipe_penerima VARCHAR(50) DEFAULT 'TINDAK_LANJUT',
    status VARCHAR(50) DEFAULT 'BARU',
    laporan TEXT NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_disp_uuid (disposisi_uuid),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Instruksi (List Instruksi)
CREATE TABLE IF NOT EXISTS instruksi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    disposisi_uuid CHAR(36) NOT NULL,
    isi TEXT NOT NULL,
    target_selesai DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_disp_uuid (disposisi_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Instruksi Penerima (Mapping Status per Instruksi)
CREATE TABLE IF NOT EXISTS instruksi_penerima (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    instruksi_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    status VARCHAR(50) DEFAULT 'BARU',
    updated_at TIMESTAMP NULL,
    
    INDEX idx_instruksi (instruksi_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Disposisi Tables Created' as status;

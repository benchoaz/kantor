-- ============================================
-- Identity Module - OPD Table
-- ============================================
-- Stores organizational units (Kecamatan, Dinas, Badan, UPTD)

CREATE TABLE IF NOT EXISTS opd (
    id_opd INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Identification
    kode_opd VARCHAR(20) UNIQUE NOT NULL COMMENT 'e.g., KECBESUK, DINKES',
    nama_opd VARCHAR(255) NOT NULL COMMENT 'Full organization name',
    jenis_opd ENUM('kecamatan', 'dinas', 'badan', 'kantor', 'uptd', 'lainnya') NOT NULL,
    
    -- Contact Information
    alamat TEXT,
    telepon VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(100),
    
    -- Hierarchy (for sub-units)
    parent_id INT UNSIGNED NULL COMMENT 'Parent OPD for UPTD or sub-units',
    
    -- Settings
    logo_url VARCHAR(255),
    keterangan TEXT,
    is_active TINYINT(1) DEFAULT 1,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    
    -- Indexes
    INDEX idx_kode (kode_opd),
    INDEX idx_jenis (jenis_opd),
    INDEX idx_active (is_active),
    INDEX idx_parent (parent_id),
    
    -- Foreign Keys
    FOREIGN KEY (parent_id) REFERENCES opd(id_opd) ON DELETE SET NULL
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Organizational units registry for multi-tenant support';

-- ============================================
-- Insert Default OPD
-- ============================================
INSERT INTO opd (kode_opd, nama_opd, jenis_opd, alamat, is_active) VALUES
('KECBESUK', 'Kecamatan Besuk', 'kecamatan', 'Jl. Raya Besuk No. 1, Probolinggo', 1);

-- ============================================
-- Identity Module - Jabatan Table
-- ============================================
-- Stores organizational positions/roles per OPD

CREATE TABLE IF NOT EXISTS jabatan (
    id_jabatan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- OPD Association (Multi-tenant isolation)
    id_opd INT UNSIGNED NOT NULL COMMENT 'Which organization this position belongs to',
    
    -- Position Information
    nama_jabatan VARCHAR(100) NOT NULL COMMENT 'Position title',
    kode_jabatan VARCHAR(20) COMMENT 'Position code (e.g., CAMAT, KASI_PEM)',
    deskripsi TEXT COMMENT 'Position description/responsibilities',
    
    -- Organizational Hierarchy
    level_hierarki INT NOT NULL DEFAULT 3 COMMENT '1=Top (Camat/Kadis), 2=Middle (Sekcam/Kabid), 3=Staff, 4=UPTD',
    parent_id INT UNSIGNED NULL COMMENT 'Reports to (direct supervisor position)',
    
    -- Permissions (SuratQu-style for letter management)
    can_buat_surat TINYINT(1) DEFAULT 0 COMMENT 'Can create official letters',
    can_disposisi TINYINT(1) DEFAULT 0 COMMENT 'Can create dispositions',
    can_verifikasi TINYINT(1) DEFAULT 0 COMMENT 'Can verify documents',
    can_tanda_tangan TINYINT(1) DEFAULT 0 COMMENT 'Can sign documents',
    
    -- System Role Mapping (for authorization)
    system_role ENUM('admin', 'pimpinan', 'operator', 'staff', 'camat', 'sekcam') DEFAULT 'staff'
        COMMENT 'Maps to role-based access control',
    
    -- Status
    is_active TINYINT(1) DEFAULT 1,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    
    -- Indexes
    INDEX idx_opd (id_opd),
    INDEX idx_level (level_hierarki),
    INDEX idx_parent (parent_id),
    INDEX idx_system_role (system_role),
    INDEX idx_active (is_active),
    UNIQUE KEY unique_kode_per_opd (id_opd, kode_jabatan),
    
    -- Foreign Keys
    FOREIGN KEY (id_opd) REFERENCES opd(id_opd) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES jabatan(id_jabatan) ON DELETE SET NULL
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Organizational positions/roles with hierarchy and permissions';

-- ============================================
-- Default Jabatan for Kecamatan Besuk
-- ============================================
SET @opd_id = (SELECT id_opd FROM opd WHERE kode_opd = 'KECBESUK' LIMIT 1);

-- Insert Level 1 first
INSERT INTO jabatan (id_opd, nama_jabatan, kode_jabatan, level_hierarki, parent_id, 
                     can_buat_surat, can_disposisi, can_verifikasi, can_tanda_tangan, system_role) VALUES
(@opd_id, 'Camat', 'CAMAT', 1, NULL, 1, 1, 1, 1, 'camat');

-- Get Camat ID for parent references
SET @camat_id = LAST_INSERT_ID();

-- Insert Level 2
INSERT INTO jabatan (id_opd, nama_jabatan, kode_jabatan, level_hierarki, parent_id, 
                     can_buat_surat, can_disposisi, can_verifikasi, can_tanda_tangan, system_role) VALUES
(@opd_id, 'Sekretaris Camat', 'SEKCAM', 2, @camat_id, 1, 1, 1, 1, 'pimpinan'),
(@opd_id, 'Kasi Pemerintahan', 'KASI_PEM', 2, @camat_id, 1, 1, 1, 0, 'pimpinan'),
(@opd_id, 'Kasi Ekonomi dan Pembangunan', 'KASI_EKBANG', 2, @camat_id, 1, 1, 1, 0, 'pimpinan');

-- Get Kasi IDs for staff parent references  
SET @kasi_pem_id = (SELECT id_jabatan FROM jabatan WHERE kode_jabatan='KASI_PEM' AND id_opd=@opd_id LIMIT 1);
SET @kasi_eko_id = (SELECT id_jabatan FROM jabatan WHERE kode_jabatan='KASI_EKBANG' AND id_opd=@opd_id LIMIT 1);

-- Insert Level 3
INSERT INTO jabatan (id_opd, nama_jabatan, kode_jabatan, level_hierarki, parent_id, 
                     can_buat_surat, can_disposisi, can_verifikasi, can_tanda_tangan, system_role) VALUES
(@opd_id, 'Staff Pemerintahan', 'STAFF_PEM', 3, @kasi_pem_id, 0, 0, 0, 0, 'operator'),
(@opd_id, 'Staff Ekonomi', 'STAFF_EKO', 3, @kasi_eko_id, 0, 0, 0, 0, 'operator');

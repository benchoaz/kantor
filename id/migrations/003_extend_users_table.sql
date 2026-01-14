-- ============================================
-- Identity Module - Extend Users Table
-- ============================================
-- Add organizational context to users

-- Add new columns (check if exists first to avoid errors on re-run)
ALTER TABLE users
ADD COLUMN id_opd INT UNSIGNED NULL COMMENT 'Organization membership' AFTER uuid_user,
ADD COLUMN id_jabatan INT UNSIGNED NULL COMMENT 'Current position/role' AFTER nama,
ADD COLUMN nip VARCHAR(20) NULL COMMENT 'Employee ID number' AFTER id_jabatan,
ADD COLUMN no_telepon VARCHAR(20) NULL COMMENT 'Phone number (+62 format)' AFTER nip,
ADD COLUMN telegram_id BIGINT NULL COMMENT 'Telegram ID for notifications' AFTER no_telepon,
ADD COLUMN foto_profil VARCHAR(255) NULL COMMENT 'Profile photo URL' AFTER telegram_id,
ADD COLUMN bidang_id INT UNSIGNED NULL COMMENT 'Department/division (optional)' AFTER id_jabatan;

-- Add indexes (ignore errors if already exist)
ALTER TABLE users
ADD INDEX idx_opd (id_opd),
ADD INDEX idx_jabatan (id_jabatan),
ADD INDEX idx_nip (nip);

-- Add foreign keys
ALTER TABLE users
ADD CONSTRAINT fk_users_opd 
    FOREIGN KEY (id_opd) REFERENCES opd(id_opd) ON DELETE RESTRICT,
ADD CONSTRAINT fk_users_jabatan 
    FOREIGN KEY (id_jabatan) REFERENCES jabatan(id_jabatan) ON DELETE SET NULL;

-- ============================================
-- Update existing users with default OPD
-- ============================================
SET @default_opd = (SELECT id_opd FROM opd WHERE kode_opd = 'KECBESUK' LIMIT 1);

UPDATE users 
SET id_opd = @default_opd 
WHERE id_opd IS NULL;

-- ============================================
-- Create view for easy user info retrieval
-- ============================================
CREATE OR REPLACE VIEW v_users_full AS
SELECT 
    u.id,
    u.uuid_user,
    u.username,
    u.nama,
    u.nip,
    u.no_telepon,
    u.telegram_id,
    u.foto_profil,
    u.role,
    u.is_active,
    u.id_opd,
    o.kode_opd,
    o.nama_opd,
    o.jenis_opd,
    u.id_jabatan,
    j.nama_jabatan,
    j.kode_jabatan,
    j.level_hierarki,
    j.system_role,
    j.can_buat_surat,
    j.can_disposisi,
    j.can_verifikasi,
    j.can_tanda_tangan,
    u.created_at,
    u.updated_at
FROM users u
LEFT JOIN opd o ON u.id_opd = o.id_opd
LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan
WHERE u.is_active = 1;

-- ============================================
-- Add comments
-- ============================================
ALTER TABLE users 
MODIFY COLUMN role VARCHAR(50) DEFAULT 'staff' COMMENT 'Derived from jabatan.system_role - for backward compatibility';

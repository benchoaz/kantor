-- migrate_role_based_disposisi.sql
USE sidiksae_api;

-- 1. Tambahkan kolom role ke tabel disposisi jika belum ada
ALTER TABLE disposisi 
ADD COLUMN IF NOT EXISTS from_role VARCHAR(50) AFTER uuid_surat,
ADD COLUMN IF NOT EXISTS to_role VARCHAR(50) AFTER from_role;

-- 2. Tambahkan index untuk mempercepat pencarian/sinkronisasi
CREATE INDEX IF NOT EXISTS idx_disposisi_to_role ON disposisi(to_role);
CREATE INDEX IF NOT EXISTS idx_disposisi_status ON disposisi(status);

-- 3. Update data lama jika memungkinkan (opsional, untuk konsistensi)
-- UPDATE disposisi SET from_role = 'camat' WHERE from_role IS NULL;

-- 4. Tambahkan kolom to_role di disposisi_penerima juga jika ingin granular
ALTER TABLE disposisi_penerima 
ADD COLUMN IF NOT EXISTS to_role VARCHAR(50) AFTER user_id;
CREATE INDEX IF NOT EXISTS idx_dp_to_role ON disposisi_penerima(to_role);

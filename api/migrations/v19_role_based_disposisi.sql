-- MIGRASI DATABASE API PUSAT (v19 - Role Based Disposisi)
-- Database: sidiksae_api

-- 1. Standardisasi Kolasi Tabel Utama (PENTING: Menyamakan dengan format sistem)
ALTER TABLE disposisi CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE disposisi_penerima CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE surat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Penambahan Kolom Role pada Tabel Disposisi
ALTER TABLE disposisi 
ADD COLUMN IF NOT EXISTS from_role VARCHAR(50) AFTER uuid_surat,
ADD COLUMN IF NOT EXISTS to_role VARCHAR(50) AFTER from_role;

-- 3. Penambahan Kolom Role pada Tabel Disposisi Penerima
ALTER TABLE disposisi_penerima 
ADD COLUMN IF NOT EXISTS to_role VARCHAR(50) AFTER user_id;

-- 4. Optimasi Indexing untuk Pencarian Jabatan
CREATE INDEX IF NOT EXISTS idx_disposisi_to_role ON disposisi(to_role);
CREATE INDEX IF NOT EXISTS idx_dp_to_role ON disposisi_penerima(to_role);

-- 5. Sinkronisasi Data Lama (Opsional: Memasukkan data ke role_slug jika kosong)
-- Catatan: Data baru otomatis terisi oleh controller v19.

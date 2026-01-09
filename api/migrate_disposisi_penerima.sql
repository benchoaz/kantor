-- Migration for Disposisi Penerima
-- 1. Add new UUID column
ALTER TABLE disposisi_penerima ADD COLUMN disposisi_uuid CHAR(36) AFTER id;
ALTER TABLE disposisi_penerima ADD COLUMN laporan TEXT AFTER status;
ALTER TABLE disposisi_penerima ADD COLUMN kegiatan_id INT UNSIGNED AFTER laporan;
ALTER TABLE disposisi_penerima ADD COLUMN tgl_dibaca DATETIME NULL;
ALTER TABLE disposisi_penerima ADD COLUMN tgl_dilaksanakan DATETIME NULL;

-- 2. Update Status Enum (This might need a temporary column if strict mode is on, but usually modifying ENUM appends)
-- We will change the column definition to include ALL new and old values first, then map, then restrict.
ALTER TABLE disposisi_penerima MODIFY COLUMN status ENUM('belum_dibaca','dibaca','selesai','baru','dilaksanakan') DEFAULT 'baru';

-- 3. Data Migration (Best Effort map old int ID to nothing, as parent likely doesn't exist or is UUID)
-- We assume existing data is invalid or we wipe it. Let's WIPE for consistency if it's junk.
-- TRUNCATE TABLE disposisi_penerima; 
-- User didn't say wipe, but broken FK means data is useless. 
-- Let's try to keep it but user has to fix manually if needed.
UPDATE disposisi_penerima SET status = 'baru' WHERE status = 'belum_dibaca';
UPDATE disposisi_penerima SET status = 'dilaksanakan' WHERE status = 'selesai';

-- 4. Finalize Enum
ALTER TABLE disposisi_penerima MODIFY COLUMN status ENUM('baru','dibaca','dilaksanakan') DEFAULT 'baru';

-- 5. Drop old ID
-- ALTER TABLE disposisi_penerima DROP COLUMN disposisi_id; 
-- Keep it for a moment just in case, but drop index.
DROP INDEX idx_disposisi ON disposisi_penerima;
-- ALTER TABLE disposisi_penerima DROP COLUMN disposisi_id; 

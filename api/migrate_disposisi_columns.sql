-- Add remaining ERD columns to disposisi
ALTER TABLE disposisi ADD COLUMN sifat ENUM('BIASA','PENTING','SEGERA','RAHASIA') DEFAULT 'BIASA' AFTER uuid_surat;
ALTER TABLE disposisi ADD COLUMN catatan TEXT AFTER sifat;
ALTER TABLE disposisi ADD COLUMN deadline DATE AFTER catatan;
ALTER TABLE disposisi ADD COLUMN status ENUM('BARU','PROSES','SELESAI') DEFAULT 'BARU' AFTER deadline;

-- Ensure created_by is correct type (it was int unsigned in describe output, correct)
-- Ensure updated_at exists (it was there)

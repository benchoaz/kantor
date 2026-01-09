-- Rename columns to match Controller
ALTER TABLE instruksi CHANGE COLUMN isi_instruksi isi TEXT;
ALTER TABLE instruksi CHANGE COLUMN deadline target_selesai DATE;

-- Relax constraints on legacy columns
ALTER TABLE instruksi MODIFY COLUMN judul_instruksi VARCHAR(255) NULL;
ALTER TABLE instruksi MODIFY COLUMN pimpinan_id INT UNSIGNED NULL;

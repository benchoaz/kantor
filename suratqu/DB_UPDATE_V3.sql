-- Add columns to disposisi table
ALTER TABLE disposisi 
ADD COLUMN status_baca ENUM('belum', 'sudah') DEFAULT 'belum',
ADD COLUMN tanggal_baca DATETIME NULL,
ADD COLUMN status_pengerjaan ENUM('menunggu', 'proses', 'selesai') DEFAULT 'menunggu',
ADD COLUMN tanggal_selesai DATETIME NULL,
ADD COLUMN catatan_hasil TEXT NULL,
ADD COLUMN file_hasil VARCHAR(255) NULL;

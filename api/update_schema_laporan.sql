-- Add 'laporan' column for capturing Docku reports
ALTER TABLE `disposisi_penerima` 
ADD COLUMN `laporan` TEXT NULL AFTER `status`;

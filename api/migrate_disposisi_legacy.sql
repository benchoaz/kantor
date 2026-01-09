-- Make legacy columns nullable to support new UUID workflow
ALTER TABLE disposisi MODIFY COLUMN nomor_surat VARCHAR(100) NULL;
ALTER TABLE disposisi MODIFY COLUMN nomor_agenda VARCHAR(100) NULL;
ALTER TABLE disposisi MODIFY COLUMN perihal TEXT NULL;
ALTER TABLE disposisi MODIFY COLUMN tgl_surat DATE NULL;
ALTER TABLE disposisi MODIFY COLUMN tgl_diterima DATE NULL;
ALTER TABLE disposisi MODIFY COLUMN asal_surat VARCHAR(255) NULL;
/* 
   We suspect 'surat_id' is also legacy and might be problematic if not null, 
   but we didn't get error for it yet (maybe it has default 0 or we didn't hit it).
   Let's ensure it's nullable too just in case.
*/
ALTER TABLE disposisi MODIFY COLUMN surat_id INT UNSIGNED NULL;

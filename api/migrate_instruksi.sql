-- Add disposisi_uuid to instruksi
ALTER TABLE instruksi ADD COLUMN disposisi_uuid CHAR(36) NOT NULL AFTER id;
-- Make legacy disposisi_id nullable
ALTER TABLE instruksi MODIFY COLUMN disposisi_id INT UNSIGNED NULL;
-- Index
CREATE INDEX idx_inst_disposisi_uuid ON instruksi(disposisi_uuid);

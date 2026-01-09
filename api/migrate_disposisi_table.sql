-- Add UUID columns
ALTER TABLE disposisi ADD COLUMN uuid CHAR(36) AFTER id;
ALTER TABLE disposisi ADD COLUMN uuid_surat CHAR(36) AFTER uuid;

-- Populate UUID for existing rows
UPDATE disposisi SET uuid = uuid() WHERE uuid IS NULL;

-- Make UUID unique and indexed
CREATE UNIQUE INDEX idx_disposisi_uuid ON disposisi(uuid);
CREATE INDEX idx_disposisi_uuid_surat ON disposisi(uuid_surat);

-- Optional: Drop legacy columns if desired, but we keep them for safety
-- ALTER TABLE disposisi DROP COLUMN surat_id;

-- Database Fix for Integration Logs
-- Increase capacity to handle large payloads and response bodies
ALTER TABLE `integrasi_docku_log` 
MODIFY COLUMN `payload` LONGTEXT,
MODIFY COLUMN `response_body` LONGTEXT;

-- Add index for performance if not exists
CREATE INDEX IF NOT EXISTS idx_status ON integrasi_docku_log(status);
CREATE INDEX IF NOT EXISTS idx_disposisi_id ON integrasi_docku_log(disposisi_id);

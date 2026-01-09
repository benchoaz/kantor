# Quick Migration Guide - Surat Table

## Problem
Tabel `surat` di production belum punya kolom: `source_app`, `external_id`, `metadata`, `updated_at`

## Solution
Run migration SQL via phpMyAdmin

## Steps

### 1. Access phpMyAdmin
```
https://your-cpanel.com/phpMyAdmin
```

### 2. Select Database
Pilih database: `sidiksae_api` (atau sesuai nama database production)

### 3. Run Migration
Copy-paste SQL berikut di tab SQL:

```sql
-- Add new columns
ALTER TABLE `surat` 
ADD COLUMN `source_app` VARCHAR(50) DEFAULT 'suratqu' AFTER `origin_app`,
ADD COLUMN `external_id` INT(11) NULL AFTER `source_app`,
ADD COLUMN `metadata` JSON NULL AFTER `external_id`,
ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`;

-- Update existing rows
UPDATE `surat` 
SET `metadata` = JSON_OBJECT(
    'file_hash', file_hash,
    'file_size', file_size,
    'mime_type', mime_type
),
`source_app` = COALESCE(origin_app, 'suratqu'),
`updated_at` = created_at
WHERE `metadata` IS NULL;

-- Create index
CREATE INDEX idx_source_app ON `surat`(source_app);
```

### 4. Verify
```sql
SHOW COLUMNS FROM surat;
```

Expected columns:
- ✅ uuid
- ✅ file_hash
- ✅ file_path
- ✅ file_size
- ✅ mime_type
- ✅ origin_app
- ✅ **source_app** (new)
- ✅ **external_id** (new)
- ✅ **metadata** (new)
- ✅ created_at
- ✅ **updated_at** (new)

### 5. Test Again
```
https://suratqu.sidiksae.my.id/test_api_register.php
```

Expected: ✅ SUCCESS!

## Rollback (if needed)
```sql
ALTER TABLE `surat` 
DROP COLUMN `source_app`,
DROP COLUMN `external_id`,
DROP COLUMN `metadata`,
DROP COLUMN `updated_at`;
```

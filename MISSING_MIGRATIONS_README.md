# Missing Database Migrations

## Problem
API mencari kolom `d.uuid` di tabel `disposisi`, tapi kolom belum ada di production database.

## Solution: Run Disposisi Migration

### File yang perlu dijalankan:
`api/migrate_disposisi_table.sql`

### Cara Jalankan (via phpMyAdmin):

1. Login ke **phpMyAdmin**
2. Pilih database **`sidiksae_api`**
3. Klik tab **"SQL"**
4. Copy-paste SQL berikut:

```sql
-- Add UUID columns to disposisi table
ALTER TABLE disposisi ADD COLUMN uuid CHAR(36) AFTER id;
ALTER TABLE disposisi ADD COLUMN uuid_surat CHAR(36) AFTER uuid;

-- Populate UUID for existing rows
UPDATE disposisi SET uuid = UUID() WHERE uuid IS NULL;

-- Make UUID unique and indexed
CREATE UNIQUE INDEX idx_disposisi_uuid ON disposisi(uuid);
CREATE INDEX idx_disposisi_uuid_surat ON disposisi(uuid_surat);

-- Verify
SELECT 'Migration Complete' as status, 
       COUNT(*) as total_disposisi,
       COUNT(uuid) as has_uuid
FROM disposisi;
```

5. Klik **"Go"**

### Verify

Expected output:
```
Migration Complete | total_disposisi | has_uuid
-------------------+-----------------+----------
                   |       X         |    X
```

`total_disposisi` harus sama dengan `has_uuid` ✅

### After Migration

Test endpoint lagi:
```bash
curl "https://api.sidiksae.my.id/api/disposisi/penerima/0f720f54-ec89-11f0-9d22-ea2d3165cda0" \
  -H "X-API-KEY: sk_live_docku_x9y8z7w6v5u4t3s2"
```

Expected: HTTP 200 ✅

# Deployment Update V2 - Fix User Sync (WITH USERNAME)

**Tanggal**: 2026-01-06
**Versi**: sync_fix_v2

## Changelog dari V1

### V1 Issues Found:
❌ Missing `username` field causing API error:
```
SQLSTATE[23000]: Integrity constraint violation: 1062 
Duplicate entry '' for key 'username'
```

### V2 Fixes:
✅ Added `username` field to sync payload
✅ All required fields now included: id, username, nama, jabatan, role

---

## File yang Diupdate

1. `includes/integration_helper.php` - Fixed sync function with username field

---

## Cara Deploy ke cPanel

### 1. Upload File
- Upload `docku_sync_fix_v2_20260106.tar.gz` ke cPanel File Manager
- Extract di root directory Docku
- **PENTING**: Gunakan V2, BUKAN V1!

### 2. Verifikasi File
Pastikan file berikut ter-overwrite:
```
includes/integration_helper.php
```

### 3. Testing
1. Login ke Docku
2. Buka halaman Users
3. Klik "Sinkron Ke Pimpinan"
4. **Expected result**: Success message tanpa error

---

## Payload Format (Updated)

```json
{
    "users": [
        {
            "id": 4,
            "username": "sekcam",
            "nama": "Sekretaris Camat",
            "jabatan": "Sekretaris Camat",
            "role": "pimpinan"
        }
    ]
}
```

**New field**: `username` - required by API to prevent duplicate entry errors

---

## Perubahan yang Dilakukan

✅ Fixed API endpoint URL: `https://api.sidiksae.my.id/api/v1/users/sync`
✅ Set correct API key: `sk_live_docku_x9y8z7w6v5u4t3s2`
✅ Added role filtering (exclude: admin, operator, staff, camat)
✅ Include `id` field in payload
✅ **[NEW] Include `username` field in payload**
✅ Enhanced logging

---

## Expected Result

- 6 structural users akan disinkronkan
- Data sampai ke API pusat (**HTTP 200**, bukan 500)
- Muncul di dropdown "Diteruskan Kepada" aplikasi Camat
- **NO MORE** "Duplicate entry" errors

---

## Troubleshooting

### Jika masih error:
1. Cek `logs/integration.log`
2. Pastikan semua user punya username yang unique
3. Lihat response dari API di log

### Cek username di database:
```sql
SELECT id, username, nama, role 
FROM users 
WHERE role NOT IN ('admin', 'operator', 'staff', 'camat');
```

Pastikan tidak ada username yang NULL atau duplikat.

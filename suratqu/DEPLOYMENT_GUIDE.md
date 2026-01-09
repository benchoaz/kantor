# 📦 PANDUAN DEPLOYMENT KE CPANEL
**SuratQu - Integrasi SidikSae**

---

## 📋 File yang Sudah Dipackage

Archive: **`SuratQu_SidikSae_Integration.tar.gz`** (14KB)

Berisi 11 file:

### ✅ File Baru (5 files)
1. `includes/sidiksae_api_client.php` - API client class
2. `tests/test_sidiksae_api.php` - Test script
3. `migrate_sidiksae.sh` - Database migration script
4. `INTEGRASI_SIDIKSAE.md` - Dokumentasi lengkap
5. `storage/.gitkeep` - Placeholder untuk storage directory

### 🔄 File Update (6 files)
1. `config/integration.php` - Konfigurasi SidikSae
2. `includes/integrasi_sistem_handler.php` - Integration logic
3. `integrasi_pengaturan.php` - Settings UI
4. `integrasi_sistem.php` - Monitoring dashboard
5. `disposisi_proses.php` - Disposisi processing
6. `database/integrasi_sistem.sql` - Database migration SQL

---

## 🚀 LANGKAH DEPLOYMENT

### Step 1: Upload Archive ke cPanel

1. **Login ke cPanel** suratqu.sidiksae.my.id
2. Buka **File Manager**
3. Navigate ke folder **public_html** (atau root directory SuratQu)
4. Klik **Upload**
5. Upload file `SuratQu_SidikSae_Integration.tar.gz`
6. Tunggu sampai upload selesai (100%)

### Step 2: Extract Archive

1. Di File Manager, **klik kanan** pada file `.tar.gz`
2. Pilih **Extract**
3. Confirm extraction
4. **Hapus** file `.tar.gz` setelah di-extract (untuk hemat space)

### Step 3: Set Permissions

Via File Manager atau SSH:

```bash
# Set permission untuk storage directory
chmod 755 storage

# Set permission untuk migration script
chmod +x migrate_sidiksae.sh
```

### Step 4: Apply Database Migration

**Option A: Via SSH**
```bash
cd public_html  # atau path SuratQu Anda
./migrate_sidiksae.sh
```

**Option B: Via phpMyAdmin**
1. Login ke phpMyAdmin
2. Pilih database SuratQu
3. Klik tab **SQL**
4. Copy-paste isi file `database/integrasi_sistem.sql`
5. Klik **Go**
6. Pastikan muncul pesan success (bukan error)

**Expected Result:**
```
✅ Migration berhasil diaplikasikan!

Perubahan:
- Menambahkan kolom 'payload' di tabel integrasi_docku_log
- Menambahkan kolom 'created_at' dan 'updated_at'
- Menambahkan index untuk optimasi performa
```

### Step 5: Verify File Structure

Pastikan struktur directory sudah benar:

```
public_html/
├── config/
│   ├── database.php           (JANGAN GANTI - existing)
│   └── integration.php        (✓ New config)
├── includes/
│   ├── sidiksae_api_client.php        (✓ New)
│   ├── integrasi_sistem_handler.php   (✓ Updated)
│   └── ... (files lain tidak berubah)
├── tests/
│   ├── test_sidiksae_api.php  (✓ New)
│   └── ... (files lain tidak berubah)
├── storage/                    (✓ New directory)
│   └── .gitkeep
├── database/
│   └── integrasi_sistem.sql   (✓ Updated)
├── integrasi_pengaturan.php   (✓ Updated)
├── integrasi_sistem.php       (✓ Updated)
├── disposisi_proses.php       (✓ Updated)
├── migrate_sidiksae.sh        (✓ New)
└── INTEGRASI_SIDIKSAE.md     (✓ New)
```

### Step 6: Test Integrasi

1. **Open browser** dan akses:
   ```
   https://suratqu.sidiksae.my.id/tests/test_sidiksae_api.php
   ```

2. **Expected Output:**
   - ✓ Konfigurasi ditemukan
   - ✓ API Client berhasil diinisialisasi
   - ✓ KONEKSI BERHASIL!
   - ✓ AUTENTIKASI BERHASIL!
   - ✓ PENGIRIMAN BERHASIL! (jika ada data test)

3. **Jika ada error merah**, screenshot dan cek di bawah

### Step 7: Test dari UI

1. **Login** sebagai admin
2. Buka **Monitoring Integrasi Sistem**
3. Klik **Pengaturan**
4. Klik tombol **"Test Koneksi"**
5. **Expected**: Muncul alert hijau "✓ Koneksi Berhasil!"

### Step 8: Test Real Disposisi

1. Buat **disposisi baru** (test)
2. Buka **Monitoring Integrasi Sistem**
3. Cek log terbaru - **Expected**:
   - Status: **Success** (hijau)
   - HTTP Code: **200** atau **201**
4. Klik **View Payload** untuk lihat detail

---

## ⚠️ TROUBLESHOOTING

### Error: "Cannot write to storage directory"

**Fix:**
```bash
chmod 755 storage
chown user:user storage  # ganti 'user' dengan cPanel username
```

### Error: "Failed to authenticate with SidikSae API"

**Cek:**
1. Credentials di `config/integration.php` benar?
2. API sudah terdaftar di SidikSae?
3. Server bisa akses https://api.sidiksae.my.id?

**Test manual:**
```bash
curl -I https://api.sidiksae.my.id
```

### Error: "Migration already applied"

**Normal!** Ini artinya migration sudah pernah dijalankan. Skip saja.

### Permissions Error

Jika ada error permission di cPanel:

```bash
# Reset ownership
chown -R username:username public_html/

# Reset permissions
find public_html/ -type f -exec chmod 644 {} \;
find public_html/ -type d -exec chmod 755 {} \;
chmod +x public_html/migrate_sidiksae.sh
```

---

## ✅ VERIFICATION CHECKLIST

Setelah deployment, cek:

- [ ] File ter-upload semua (11 files)
- [ ] Directory `storage/` ada dan writable
- [ ] Database migration berhasil (tidak ada error)
- [ ] Test script berjalan (`/tests/test_sidiksae_api.php`)
- [ ] Test koneksi dari UI berhasil (✓ hijau)
- [ ] Disposisi test terkirim dan ter-log
- [ ] Status integrasi "Aktif" di dashboard

**Jika semua ✓ → Deployment SUKSES! 🎉**

---

## 🔄 ROLLBACK (Jika Perlu)

Jika ada masalah dan perlu rollback:

1. **Backup current files** dulu sebelum deploy (penting!)
2. Jika sudah terlanjur, restore dari backup cPanel
3. Database migration **tidak bisa di-rollback** otomatis (kolom sudah ditambah)
   - Tapi ini **AMAN** karena hanya tambah kolom (tidak hapus/ubah data)

---

## 📞 SUPPORT

Jika ada masalah:

1. Screenshot error message
2. Check `storage/api_requests.log` untuk detail
3. Baca dokumentasi lengkap di `INTEGRASI_SIDIKSAE.md`
4. Contact developer dengan info:
   - Error message
   - Screenshot
   - PHP version (dari cPanel)
   - MySQL version

---

## 📝 NOTES

- ✅ **config/database.php** TIDAK termasuk dalam package (aman)
- ✅ **uploads/** folder tidak akan tersentuh
- ✅ Migration bisa dijalankan berkali-kali (idempotent)
- ✅ Archive size hanya 14KB (ringan & cepat upload)

**Happy deploying! 🚀**

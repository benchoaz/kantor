# 🚨 TROUBLESHOOTING: Duplicate Username Error

## Masalah
```
API Error (500): Duplicate entry '' for key 'username'
```

**Penyebab:** File di production (cPanel) **BELUM TER-UPDATE** ke versi V2.

## Bukti dari Log
```
Count: 13  ← Seharusnya 6 (berarti filter role belum aktif)
```

Jika file sudah benar, count harus **6 users** (hanya pimpinan), bukan 13.

---

## ✅ Solusi Step-by-Step

### Opsi 1: Manual Check & Re-upload (RECOMMENDED)

**1. Login ke cPanel File Manager**

**2. Navigate ke folder Docku**
   - Biasanya: `public_html/docku` atau `public_html`

**3. Check file `includes/integration_helper.php`**
   - Klik kanan → Edit
   - Cari baris sekitar line 75
   - **Harus ada:** `SELECT id, username, nama, jabatan, role`
   - **Jika masih:** `SELECT nama, jabatan, role` ← FILE LAMA!

**4. Jika file masih lama:**
   - Download fresh: `docku_sync_fix_v2_20260106.tar.gz` dari `/home/beni/projectku/Docku/`
   - Upload ke cPanel
   - Extract (overwrite existing files)
   - **Delete file .tar.gz setelah extract**

**5. Clear PHP Cache (PENTING!)**
   
   Via cPanel:
   - Cari menu "Select PHP Version" atau "MultiPHP Manager"
   - Reset Opcode Cache / Restart PHP
   
   Via SSH (jika ada akses):
   ```bash
   # Restart PHP-FPM
   sudo systemctl restart php-fpm
   
   # Atau clear OPcache via script
   echo '<?php opcache_reset(); echo "Cache cleared"; ?>' > /tmp/clear_cache.php
   php /tmp/clear_cache.php
   ```

**6. Verify File di Production**
   
   Via SSH:
   ```bash
   cd /path/to/docku
   bash verify_production_file.sh
   ```
   
   Atau manual check:
   ```bash
   grep "SELECT id, username, nama" includes/integration_helper.php
   ```
   
   **Output yang benar:**
   ```php
   SELECT id, username, nama, jabatan, role
   ```

**7. Test Sync Lagi**
   - Login ke Docku
   - Click "Sinkron Ke Pimpinan"
   - **Expected:** Success dengan 6 users (bukan 13)

---

### Opsi 2: Via SSH (Faster)

```bash
# 1. Navigate ke folder Docku di production
cd /home/username/public_html/docku  # Sesuaikan path

# 2. Backup file lama
cp includes/integration_helper.php includes/integration_helper.php.backup

# 3. Upload file baru via SCP dari local
# Dari komputer lokal:
scp /home/beni/projectku/Docku/includes/integration_helper.php \
    user@server:/path/to/docku/includes/

# 4. Set permission
chmod 644 includes/integration_helper.php

# 5. Verify
bash verify_production_file.sh

# 6. Clear cache (jika ada OPcache)
sudo systemctl restart php-fpm
# atau
killall -USR2 php-fpm
```

---

## 🔍 Cara Verify File Sudah Benar

### Check 1: Username field exists
```bash
grep "username" includes/integration_helper.php
```
**Harus muncul:** `SELECT id, username, nama, jabatan, role`

### Check 2: Role filter exists
```bash
grep "NOT IN ('admin'" includes/integration_helper.php
```
**Harus muncul:** `WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')`

### Check 3: MD5 checksum
```bash
md5sum includes/integration_helper.php
```
**Expected:** `8cb74a7bbca12f4e941770235dfedc53`

### Check 4: Test sync dan cek log
```bash
tail -f logs/integration.log
```
**Setelah klik sync, harus muncul:**
- `Count: 6` (bukan 13)
- `Code: 200` (bukan 500)
- Response: `{"success":true,...}`

---

## 🎯 Expected Result Setelah Fix

**Before (File Lama):**
```
Count: 13 users
Code: 500
Error: Duplicate entry '' for key 'username'
```

**After (File V2):**
```
Count: 6 users
Code: 200
Response: {"success":true,"message":"Sinkronisasi berhasil",...}
```

---

## ⚠️ Common Mistakes

1. ❌ **Upload file tapi tidak extract**
   - File masih .tar.gz, belum di-extract

2. ❌ **Extract ke folder salah**
   - Pastikan extract di root Docku, bukan di subfolder

3. ❌ **PHP cache tidak di-clear**
   - PHP masih pakai file lama dari memory

4. ❌ **Permission salah**
   - File tidak bisa dibaca oleh webserver
   - Fix: `chmod 644 includes/integration_helper.php`

---

## 📞 Contact

Jika masih error setelah semua langkah:
1. Jalankan `verify_production_file.sh` dan kirim output
2. Cek isi file `logs/integration.log` (5 baris terakhir)
3. Screenshot error yang muncul

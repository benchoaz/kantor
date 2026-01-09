# SOLUSI CEPAT: Manual Copy-Paste File

## Problem: File di production belum ter-update

Jika upload tar.gz tidak berhasil, gunakan cara manual ini:

---

## Cara 1: Via cPanel File Manager (RECOMMENDED)

### Step 1: Buka File Editor
1. Login ke cPanel
2. File Manager → Navigate ke `includes/integration_helper.php`
3. Klik kanan → **Edit**

### Step 2: Cari dan Replace Query
Cari baris ini (sekitar line 74-78):
```php
$stmtUser = $pdo->query("
    SELECT id, nama, jabatan, role 
    FROM users 
    WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')
    ORDER BY id
");
```

**ATAU** versi lama tanpa filter:
```php
$stmtUser = $pdo->query("SELECT nama, jabatan, role FROM users");
```

### Step 3: Replace dengan ini:
```php
$stmtUser = $pdo->query("
    SELECT id, username, nama, jabatan, role 
    FROM users 
    WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')
    ORDER BY id
");
```

**PENTING**: Tambahkan `username` setelah `id`!

### Step 4: Save & Close
- Klik **Save Changes**
- Close editor

### Step 5: Clear Cache
Via cPanel → PHP Selector → Reset Opcode Cache
atau
Via SSH: `sudo systemctl restart php-fpm`

---

## Cara 2: Via SSH (FASTEST)

```bash
# 1. Navigate to production folder
cd /path/to/public_html/docku  # Sesuaikan path

# 2. Backup file lama
cp includes/integration_helper.php includes/integration_helper.php.OLD

# 3. Copy file dari development
cp /home/beni/projectku/Docku/includes/integration_helper.php includes/integration_helper.php

# 4. Set permission
chmod 644 includes/integration_helper.php

# 5. Verify
grep "username" includes/integration_helper.php

# 6. Restart PHP
sudo systemctl restart php-fpm
# atau
killall -USR2 php-fpm
```

---

## Cara 3: Replace Entire File Content

Jika cara 1-2 tidak bisa, download file lengkap:

**Location:** `/home/beni/projectku/Docku/includes/integration_helper.php`

**MD5:** `8cb74a7bbca12f4e941770235dfedc53`

Lalu upload via cPanel File Manager (overwrite existing file).

---

## Verification Checklist

Setelah update, WAJIB cek:

### ✅ Check 1: Username field ada
```bash
grep "SELECT id, username" includes/integration_helper.php
```
**Expected output:** `SELECT id, username, nama, jabatan, role`

### ✅ Check 2: Role filter aktif
```bash
grep "NOT IN ('admin'" includes/integration_helper.php
```
**Expected output:** `WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')`

### ✅ Check 3: Hardcoded URL benar
```bash
grep "sidiksae.my.id" includes/integration_helper.php
```
**Expected output:** `https://api.sidiksae.my.id/api/v1/users/sync`

---

## Test Sync

1. **Clear browser cache** (Ctrl+F5)
2. **Login ke Docku**
3. **Klik "Sinkron Ke Pimpinan"**
4. **Expected result:**
   ```
   ✅ Sinkronisasi berhasil (6 user)
   ```

5. **Check log:**
   ```
   Terakhir sync berhasil: 06 Jan 2026 XX:XX WIB · 6 user
   ```

---

## Jika Masih Error

Berarti ada masalah cache. Lakukan:

### Option A: Clear All Cache (cPanel)
- PHP Selector → **Reset Opcode Cache**
- Restart **PHP-FPM** via WHM/cPanel

### Option B: Force Refresh (SSH)
```bash
# 1. Touch file to update timestamp
touch includes/integration_helper.php

# 2. Clear PHP OPcache
php -r "opcache_reset();"

# 3. Restart web server
sudo systemctl restart apache2
# atau
sudo systemctl restart nginx
```

### Option C: Disable OPcache temporarily
Edit `.htaccess` di root Docku:
```apache
php_flag opcache.enable Off
```

Test sync, lalu hapus baris tersebut setelah berhasil.

---

## Contact Support

Jika semua cara di atas gagal, screenshot:
1. ✅ Hasil `grep "username" includes/integration_helper.php`
2. ✅ Hasil `md5sum includes/integration_helper.php`
3. ✅ Error message lengkap
4. ✅ Content file `logs/integration.log` (5 baris terakhir)

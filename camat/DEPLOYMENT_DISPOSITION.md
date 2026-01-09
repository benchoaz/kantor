# Deployment Instructions - Disposition Notification System

## 📦 Package Contents

File yang ada dalam package ini:

1. **disposisi.php** - Updated form disposisi (mengirim target_user_id)
2. **laporan-disposisi.php** - NEW halaman untuk melihat laporan
3. **includes/navigation.php** - Updated dengan menu LAPORAN
4. **IMPLEMENTATION_API_BACKEND.md** - Guide untuk API backend
5. **IMPLEMENTATION_DOCKU.md** - Guide untuk Docku application
6. **QUICK_START.md** - Quick reference implementation

---

## 🚀 Deployment Steps (cPanel)

### 1. Backup Current Files

```bash
# Backup file yang akan dioverwrite
cp disposisi.php disposisi.php.backup
cp includes/navigation.php includes/navigation.php.backup
```

### 2. Upload Package

1. Login ke **cPanel**
2. Buka **File Manager**
3. Navigate ke direktori `/public_html/camat/` (atau sesuai lokasi)
4. Click **Upload**
5. Upload file `camat_disposition_notification_XXXXXXXXXX.tar.gz`

### 3. Extract Package

Di File Manager cPanel:
1. Right-click pada file `.tar.gz`
2. Select **Extract**
3. Confirm extraction

Atau via SSH:
```bash
cd /home/username/public_html/camat/
tar -xzvf camat_disposition_notification_*.tar.gz
```

### 4. Set Permissions

```bash
# Set file permissions
chmod 644 disposisi.php
chmod 644 laporan-disposisi.php
chmod 644 includes/navigation.php
chmod 644 *.md
```

### 5. Test Changes

1. Buka browser: `https://yourdomain.com/camat/`
2. Login sebagai Camat
3. Test:
   - Form disposisi (pilih target, lihat jabatan muncul)
   - Menu "LAPORAN" di bottom navigation
   - Buka halaman laporan-disposisi.php

---

## ✅ Verification Checklist

- [ ] Form disposisi menampilkan "Nama - Jabatan"
- [ ] Menu "LAPORAN" muncul di bottom navigation
- [ ] Halaman laporan-disposisi.php bisa diakses
- [ ] Tidak ada error PHP
- [ ] Design konsisten dengan theme existing

---

## 🔄 Next Steps After Upload

### Camat Application
✅ **Sudah selesai** setelah upload package ini

### API Backend Implementation
📋 **Perlu dilakukan:**

1. Copy content dari `IMPLEMENTATION_API_BACKEND.md`
2. Akses ke server API (`/home/beni/projectku/api-docksurat`)
3. Run SQL migration
4. Update DisposisiController.php
5. Update routes

**Detail:** Baca file `IMPLEMENTATION_API_BACKEND.md`

### Docku Implementation
📋 **Perlu dilakukan:**

1. Copy content dari `IMPLEMENTATION_DOCKU.md`
2. Akses ke server Docku (`/home/beni/projectku/Docku`)
3. Create inbox.php dan submit-report.php
4. Update header.php

**Detail:** Baca file `IMPLEMENTATION_DOCKU.md`

---

## 🛠️ Rollback (Jika Ada Masalah)

```bash
# Restore dari backup
cp disposisi.php.backup disposisi.php
cp includes/navigation.php.backup includes/navigation.php

# Remove new file
rm laporan-disposisi.php
```

---

## 📞 Support

Jika ada error atau butuh adjustment:
1. Check error log: `/home/username/logs/error_log`
2. Review file QUICK_START.md untuk guide cepat
3. Contact developer

---

## 🎯 Expected Behavior After Deployment

### Form Disposisi
```
Before: Kasi Pemerintahan
After:  Kasi Pemerintahan - Kasi
```

### Bottom Navigation
```
Before: BERANDA | NASKAH | INSTRUKSI | MONITORING | VALIDASI
After:  BERANDA | NASKAH | INSTRUKSI | LAPORAN | MONITORING | VALIDASI
```

### New Page
```
URL: /camat/laporan-disposisi.php
Shows: Completed disposition reports
```

---

## 📊 Database Impact

**Camat Application:** ✅ No database changes required

**API Backend:** ⚠️ Database migration required (see IMPLEMENTATION_API_BACKEND.md)

---

## 🔐 Security Notes

- File permissions tetap 644 (read-only untuk public)
- Tidak ada perubahan .htaccess
- Tidak ada perubahan config/credentials
- Backward compatible dengan existing dispositions

---

**Deployment Date:** 2026-01-06
**Version:** Disposition Notification System v1.0
**Status:** Camat Application Ready ✅

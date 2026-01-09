# Docku User Sync Password Fix - Installation Guide

## 📦 Package Contents

Fix untuk masalah password NULL setelah sync user dari Docku ke API Camat.

```
includes/
└── integration_helper.php   - Safe mode + password field protection

user_edit.php                - Auto-sync removed
user_tambah.php              - Auto-sync removed  
sync_manual.php              - Updated with forceSync parameter
```

## 🚀 Installation Steps

### 1. Backup Files Lama
```bash
cd /path/to/Docku
cp includes/integration_helper.php includes/integration_helper.php.backup
cp user_edit.php user_edit.php.backup
cp user_tambah.php user_tambah.php.backup
cp sync_manual.php sync_manual.php.backup
```

### 2. Extract Archive
```bash
tar -xzf docku-user-sync-fix.tar.gz
```

### 3. Set Permissions
```bash
chmod 644 includes/integration_helper.php
chmod 644 user_edit.php user_tambah.php sync_manual.php
```

### 4. 🔴 CRITICAL - Reset Password di API Database

Jalankan SQL ini di database API/Camat:

```sql
-- Check users dengan password NULL
SELECT username, nama, password IS NULL as needs_reset
FROM users 
WHERE docku_id IS NOT NULL;

-- Reset password ke default "password"
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE password IS NULL 
AND docku_id IS NOT NULL;

-- Verify
SELECT username, nama, password IS NOT NULL as has_password
FROM users 
WHERE docku_id IS NOT NULL;
```

**Default Password:** `password`

⚠️ **PENTING:** Instruksikan user untuk ganti password setelah login!

## ✅ What's Fixed

### Problem
- Password jadi NULL di API setelah sync
- User "Kasi Pemerintahan" tidak bisa login
- Edit user tidak bisa

### Solution
1. **Safe Mode** - Auto-sync dimatikan by default
2. **Password Protection** - Password fields tidak pernah dikirim ke API
3. **Manual Control** - Sync hanya jalan kalau admin explicitly trigger

## 🎯 How to Use After Fix

### Edit User (Sekarang Aman!)
1. Buka User Management
2. Edit user (nama, jabatan, role, etc.)
3. Simpan - ✅ **Tidak auto-sync**

### Add User
1. Tambah user baru
2. Set password
3. Daftarkan - ✅ **Tidak auto-sync**

### Manual Sync (Kalau Diperlukan)
1. Klik tombol **"Sinkron ke Pimpinan"** di User Management
2. Atau akses: `/sync_manual.php`
3. ✅ Sync dengan password protection

## 🧪 Testing Checklist

After deployment:

- [ ] Login ke Docku as admin
- [ ] Edit salah satu user
- [ ] Verify user bisa di-update tanpa error
- [ ] Check password di API database (should NOT be NULL)
- [ ] Test manual sync button
- [ ] Verify login di Camat application

## 🔧 Rollback (If Needed)

```bash
cd /path/to/Docku
cp includes/integration_helper.php.backup includes/integration_helper.php
cp user_edit.php.backup user_edit.php
cp user_tambah.php.backup user_tambah.php
cp sync_manual.php.backup sync_manual.php
```

## 📝 Key Changes

### integration_helper.php
- Added `$forceSync` parameter (default: false)
- Auto-sync skipped unless `forceSync=true`
- Password fields explicitly removed with `unset()`

### user_edit.php & user_tambah.php
- Removed `syncUsersToCamas($pdo)` call
- Added comment explaining the change

### sync_manual.php
- Updated to use `syncUsersToCamas($pdo, true)`
- Force sync enabled for manual operation

## 🆘 Support

**Issue:** User masih tidak bisa login
**Fix:** Run password reset SQL again

**Issue:** Sync button tidak bekerja
**Check:** Verify sync_manual.php updated correctly

**Issue:** Need to enable auto-sync
**Warning:** Not recommended! Akan menyebabkan password NULL lagi

---
**Version:** 1.0  
**Date:** 2026-01-06  
**Priority:** P0 (Critical Fix)

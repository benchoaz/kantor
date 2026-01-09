# SidikSae Integration Update - Deployment Instructions

## 📦 Package Contents

This package contains the following updated files:
- `sidiksae_api_client.php` → Upload to: `/includes/`
- `integration.php` → Upload to: `/config/`
- `test_api_connection.php` → Upload to: root directory

## 🚀 Deployment Steps

### 1. Backup Files (PENTING!)
Before uploading, backup these files from your cPanel:
```
/includes/sidiksae_api_client.php
/config/integration.php
/test_api_connection.php
```

### 2. Upload via cPanel File Manager

**A. Upload sidiksae_api_client.php**
1. Login ke cPanel
2. Buka **File Manager**
3. Navigate ke folder `/includes/`
4. Upload `sidiksae_api_client.php` (overwrite jika ada)

**B. Upload integration.php**
1. Navigate ke folder `/config/`
2. Upload `integration.php` (overwrite jika ada)

**C. Upload test_api_connection.php**
1. Navigate ke root directory (public_html atau sesuai instalasi Anda)
2. Upload `test_api_connection.php` (overwrite jika ada)

### 3. Verify Permissions
Pastikan file permissions correct:
- Files: `644` (rw-r--r--)
- Directories: `755` (rwxr-xr-x)

### 4. Test Integration

**Via Browser:**
```
https://sidiksae.my.id/test_api_connection.php
```

**Expected Results:**
- ✅ Test Health Check: HTTP 200 (SUKSES!)
- ✅ Test Authentication: HTTP 201 (AUTENTIKASI BERHASIL!)
- ✅ JWT Token displayed

### 5. Enable Auto-Sync

1. Login sebagai **admin**
2. Buka menu **"Monitoring Integrasi Sistem"**
3. Klik button **"Pengaturan"**
4. Toggle **"Aktifkan Sinkronisasi Otomatis"** → ON
5. Klik **"Test Koneksi"** (harus muncul ✓)
6. Klik **"Simpan Pengaturan"**

## 🔍 Troubleshooting

### Test koneksi gagal?
1. Periksa apakah file sudah di-upload dengan benar
2. Refresh browser (Ctrl+F5)
3. Cek file permissions

### Error "file not found"?
- Pastikan struktur folder sesuai (`/includes/`, `/config/`)
- Check case sensitivity (Linux case-sensitive!)

## ✅ Verification Checklist

- [ ] Backup files created
- [ ] All 3 files uploaded successfully
- [ ] File permissions correct (644)
- [ ] test_api_connection.php shows all green checkmarks
- [ ] Integration settings page accessible
- [ ] Test koneksi berhasil
- [ ] Auto-sync enabled

## 📝 What Changed?

**Critical Fix:** Added `user_id` parameter to authentication
- API sekarang require numeric `user_id=1`
- Without this, authentication will fail with HTTP 400

**Files Modified:**
1. `sidiksae_api_client.php` - Added user_id to auth payload
2. `integration.php` - Added user_id and client_id config
3. `test_api_connection.php` - Fixed HTTP code check (200→201)

---

**Status:** Ready for Production ✅

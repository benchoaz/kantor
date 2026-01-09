# API Compliance Deployment Package

## 📦 Isi Package

File yang akan di-deploy ke production:
- `includes/sidiksae_api_client.php` (UPDATED)
- `includes/functions.php` (UPDATED)
- `surat_detail_api.php` (NEW)
- `test_api_compliance.php` (NEW)

---

## 🚀 Cara Deploy ke cPanel

### Metode 1: Extract Langsung (Recommended)

1. **Upload package** via cPanel File Manager:
   - Login cPanel → File Manager
   - Navigate ke `/public_html/` (root aplikasi)
   - Klik **Upload**
   - Upload file: `deploy_cpanel_api_compliance.tar.gz`

2. **Extract:**
   - Klik kanan `deploy_cpanel_api_compliance.tar.gz`
   - Pilih **Extract**
   - File akan otomatis overwrite yang lama

3. **Hapus tar.gz:**
   - Delete `deploy_cpanel_api_compliance.tar.gz` (sudah tidak diperlukan)

4. **Test:**
   - Buka: `https://suratqu.sidiksae.my.id/test_api_compliance.php`
   - Atau: `https://suratqu.sidiksae.my.id/surat_detail_api.php?id_surat=15`

---

### Metode 2: Via Terminal/SSH

```bash
# Upload
scp deploy_cpanel_api_compliance.tar.gz user@host:/path/to/public_html/

# SSH ke server
ssh user@host

# Extract
cd /path/to/public_html/
tar -xzf deploy_cpanel_api_compliance.tar.gz

# Cleanup
rm deploy_cpanel_api_compliance.tar.gz

# Test
php test_api_compliance.php
```

---

## ✅ Verifikasi

Setelah extract, cek:
- `/includes/sidiksae_api_client.php` - ada method getSuratDetail()
- `/includes/functions.php` - ada format_jam_wib()
- `/surat_detail_api.php` - file baru
- `/test_api_compliance.php` - file baru

---

## 🔄 Rollback

Jika ada masalah, restore dari backup lokal:
`backup_compliance_20260104_232428/`

---

**Version:** API Compliance v1.0  
**Date:** 4 Januari 2026 23:26 WIB

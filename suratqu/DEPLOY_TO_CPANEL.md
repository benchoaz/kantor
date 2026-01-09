# Deploy ke cPanel Production Server

## 🚀 Quick Deploy

### Metode 1: Otomatis Script (FTP/SSH)

1. **Edit konfigurasi** di `deploy_to_cpanel.sh`:
   ```bash
   CPANEL_HOST="suratqu.sidiksae.my.id"
   CPANEL_USER="your_username"
   CPANEL_PASSWORD=""  # Kosongkan untuk manual input
   REMOTE_DIR="/public_html"
   ```

2. **Jalankan script:**
   ```bash
   chmod +x deploy_to_cpanel.sh
   bash deploy_to_cpanel.sh
   ```

3. **Done!** Script akan:
   - Upload semua file ke server
   - Verifikasi upload
   - Tampilkan link testing

---

### Metode 2: Manual via cPanel File Manager

1. **Login ke cPanel**
   - URL: `https://suratqu.sidiksae.my.id:2083`
   - Atau via hosting panel Anda

2. **Buka File Manager**
   - Masuk ke `public_html/` (atau root aplikasi)

3. **Backup File Lama** (penting!)
   - Klik `includes/sidiksae_api_client.php` → Download
   - Klik `includes/functions.php` → Download

4. **Upload File Baru**
   - Click **Upload** button
   - Upload 4 file ini:
     - `includes/sidiksae_api_client.php` (timpa yang lama)
     - `includes/functions.php` (timpa yang lama)
     - `surat_detail_api.php` (file baru)
     - `test_api_compliance.php` (file baru)

5. **Set Permissions**
   - Klik kanan file → Change Permissions
   - Set to `644` untuk semua file PHP

---

### Metode 3: Upload via FTP Client (FileZilla)

1. **Download FileZilla**
   - https://filezilla-project.org/

2. **Connect ke Server**
   - Host: `suratqu.sidiksae.my.id`
   - Username: `[cpanel_username]`
   - Password: `[cpanel_password]`
   - Port: `21` (FTP) atau `22` (SFTP)

3. **Navigate**
   - Remote: `/public_html/`
   - Local: `/home/beni/projectku/SuratQu/`

4. **Drag & Drop**
   - Upload 4 file (akan otomatis overwrite)

---

## 📋 File yang Harus Diupload

| File | Status | Action |
|------|--------|--------|
| `includes/sidiksae_api_client.php` | UPDATE | Timpa yang lama |
| `includes/functions.php` | UPDATE | Timpa yang lama |
| `surat_detail_api.php` | NEW | Upload baru |
| `test_api_compliance.php` | NEW | Upload baru |

---

## ✅ Verifikasi Setelah Deploy

### 1. Test API Connection
```bash
curl https://suratqu.sidiksae.my.id/test_api_compliance.php
```

### 2. Test Detail Page (Browser)
```
https://suratqu.sidiksae.my.id/surat_detail_api.php?id_surat=15
```

**Expected:**
- Jika endpoint tersedia → Tampil data surat
- Jika endpoint 404 → Tampil error jelas (tidak redirect)

### 3. Test Error Handling
```
https://suratqu.sidiksae.my.id/surat_detail_api.php?id_surat=99999
```

**Expected:**
- Tampil pesan: "Surat tidak ditemukan"
- Ada tombol kembali
- TIDAK ada redirect misterius

### 4. Monitor Logs
```bash
# Via SSH
tail -f /home/[user]/public_html/storage/api_requests.log

# Via cPanel
File Manager → storage/api_requests.log → View
```

**Check for:**
- Header `X-CLIENT-ID: suratqu` ada di setiap request
- Response dari API tercatat lengkap

---

## 🔄 Rollback (Jika Ada Masalah)

### Via cPanel:
1. File Manager → Navigate ke `includes/`
2. Upload kembali file backup:
   - `sidiksae_api_client.php.bak` → rename ke `sidiksae_api_client.php`
   - `functions.php.bak` → rename ke `functions.php`

### Via Script (jika backup lokal):
```bash
# Upload file backup ke server
scp backup_compliance_*/sidiksae_api_client.php.bak user@host:/path/to/includes/sidiksae_api_client.php
scp backup_compliance_*/functions.php.bak user@host:/path/to/includes/functions.php
```

---

## ⚠️ Troubleshooting

### Error: "Permission denied"
```bash
# Set permissions via SSH
chmod 644 includes/*.php
chmod 644 *.php
```

### Error: "File not found"
- Pastikan path `/public_html/` benar
- Bisa jadi `/htdocs/` atau `/www/` tergantung hosting

### FTP Connection Failed
- Cek firewall
- Gunakan passive mode
- Coba port 22 (SFTP) jika port 21 (FTP) blocked

---

## 📞 Support

Jika ada masalah:
1. Cek backup lokal: `backup_compliance_20260104_232428/`
2. Test lokal dulu: `php test_api_compliance.php`
3. Deploy ulang jika perlu

---

**Last Updated:** 4 Januari 2026 23:26 WIB

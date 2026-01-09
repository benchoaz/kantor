# 🧪 Panduan Testing Setelah Deploy

## Quick Test (10 Menit)

### 1️⃣ Test di Browser (Paling Mudah)

#### A. Test Detail Page - ID Valid
```
https://suratqu.sidiksae.my.id/surat_detail_api.php?id_surat=15
```

**✅ Yang Harus Muncul:**
- Data surat lengkap (nomor, asal, perihal, dll)
- Format tanggal: "31 Desember 2025" (Indonesia)
- Badge hijau: "Data dari API Pusat"
- Button "Buka PDF" jika ada scan_surat

**❌ Yang TIDAK Boleh Muncul:**
- Form kosong
- Redirect ke halaman lain
- Error tanpa pesan jelas

---

#### B. Test Error Handling - ID Tidak Valid
```
https://suratqu.sidiksae.my.id/surat_detail_api.php?id_surat=99999
```

**✅ Yang Harus Muncul:**
- Pesan error jelas: "Surat tidak ditemukan" atau "Endpoint not found"
- Alert merah/warning dengan icon
- Button "Kembali ke Daftar Surat"
- Status HTTP dan ID surat ditampilkan

**❌ Yang TIDAK Boleh Terjadi:**
- Redirect misterius ke dashboard
- Form kosong tanpa pesan
- Halaman blank/error 500

---

#### C. Test Parameter Kosong
```
https://suratqu.sidiksae.my.id/surat_detail_api.php
```

**✅ Yang Harus Muncul:**
- Alert warning: "Parameter tidak ditemukan di URL"
- Pesan jelas tentang missing `id_surat`
- Button kembali

---

### 2️⃣ Test Script Otomatis (Via SSH/Terminal)

```bash
# SSH ke server
ssh user@suratqu.sidiksae.my.id

# Masuk ke direktori
cd /home/[user]/public_html

# Jalankan test
php test_api_compliance.php
```

**Output yang diharapkan:**
```
✅ Autentikasi berhasil
✅ Format tanggal sesuai standar
⚠️  Endpoint not found (jika endpoint belum tersedia)
```

---

### 3️⃣ Test dari Local

```bash
# Jalankan script test
bash test_after_deploy.sh
```

Script akan otomatis test:
- File accessibility
- Detail page dengan ID valid
- Error handling dengan ID invalid
- Missing parameter handling

---

## Advanced Testing

### Test 1: Cek Header X-CLIENT-ID di Log

**Via cPanel:**
1. File Manager → `storage/api_requests.log`
2. View file
3. Cari baris terbaru
4. Cek ada: `"X-CLIENT-ID: suratqu"`

**Via SSH:**
```bash
tail -20 storage/api_requests.log | grep "X-CLIENT-ID"
```

**Expected:**
```json
"headers": [
  "X-API-KEY: sk_live_suratqu_surat2026",
  "X-CLIENT-ID: suratqu",
  "Accept: application/json"
]
```

---

### Test 2: Cek Response API

```bash
# Via browser Console (F12)
# Network tab → XHR → Lihat request ke API
# Headers harus ada X-CLIENT-ID
```

---

### Test 3: Format Tanggal

Buka halaman detail, cek format:
- ✅ "31 Desember 2025" (bukan "2025-12-31")
- ✅ "09:30 WIB" (bukan "09:30:00")

---

## Checklist Testing

```
[ ] File ter-upload dengan benar
[ ] Halaman detail bisa dibuka
[ ] Data surat ditampilkan lengkap
[ ] Format tanggal Indonesia (bukan format MySQL)
[ ] Error 404 ditangani dengan jelas
[ ] Tidak ada redirect misterius
[ ] Parameter kosong ditangani
[ ] Header X-CLIENT-ID terkirim
[ ] Logs tercatat di storage/api_requests.log
[ ] Tidak ada error 500
```

---

## Troubleshooting

### Masalah: "Permission denied"
```bash
chmod 644 includes/*.php
chmod 644 *.php
```

### Masalah: "File not found"
- Cek path upload: harus di `/public_html/` atau root aplikasi
- Pastikan extract tar.gz berhasil

### Masalah: "Error 500"
- Cek PHP error log di cPanel
- Pastikan semua file ter-upload lengkap
- Cek config/integration.php ada dan valid

### Masalah: "Halaman blank"
- Enable error display sementara:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- Cek PHP version (min 7.4)

---

## Expected Results Summary

| Test | Expected Result |
|------|----------------|
| Valid ID | Data surat tampil dengan format Indonesia |
| Invalid ID | Error message jelas, tidak redirect |
| No param | "Parameter tidak valid" message |
| Format tanggal | "31 Desember 2025" bukan "2025-12-31" |
| Format jam | "09:30 WIB" |
| Header | X-CLIENT-ID terkirim di semua request |
| Logs | Semua request tercatat |

---

## Next Steps Setelah Test Berhasil

1. Update link di `surat_masuk.php`
2. Monitor selama 24 jam
3. Hapus file lama jika semua OK
4. Dokumentasi untuk user

---

**Need Help?**
- Backup ada di: `backup_compliance_20260104_232428/`
- Rollback: restore file dari backup
- Contact: [your support]

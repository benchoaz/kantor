# Deployment Guide: API Integration Compliance

## 📦 File yang Diubah/Ditambahkan

### Modified Files
1. **includes/sidiksae_api_client.php**
   - ✅ Tambah header `X-CLIENT-ID` (line 238)
   - ✅ Tambah method `getSuratDetail()` (line 194-233)

2. **includes/functions.php**
   - ✅ Tambah `format_jam_wib()` 
   - ✅ Tambah `format_tgl_jam_wib()`

### New Files
3. **surat_detail_api.php**
   - ✅ Halaman detail surat yang konsumsi API
   - ✅ Error handling tanpa redirect
   - ✅ Pesan error yang jelas

4. **test_api_compliance.php**
   - ✅ Script testing kepatuhan API

---

## 🚀 Cara Deploy

### Step 1: Upload File ke Server

```bash
# Upload 3 file yang diubah
scp includes/sidiksae_api_client.php user@suratqu.sidiksae.my.id:/var/www/html/includes/
scp includes/functions.php user@suratqu.sidiksae.my.id:/var/www/html/includes/
scp surat_detail_api.php user@suratqu.sidiksae.my.id:/var/www/html/
scp test_api_compliance.php user@suratqu.sidiksae.my.id:/var/www/html/
```

### Step 2: Test di Server

```bash
# SSH ke server
ssh user@suratqu.sidiksae.my.id

# Jalankan test
cd /var/www/html
php test_api_compliance.php
```

### Step 3: Verifikasi Browser

1. **Test dengan ID valid:**
   ```
   https://suratqu.sidiksae.my.id/surat_detail_api.php?id_surat=15
   ```
   - Harus menampilkan data surat
   - Format tanggal harus Indonesia

2. **Test dengan ID tidak valid:**
   ```
   https://suratqu.sidiksae.my.id/surat_detail_api.php?id_surat=99999
   ```
   - Harus menampilkan pesan error
   - TIDAK boleh redirect ke halaman lain
   - Pesan error harus jelas dari API

3. **Test tanpa parameter:**
   ```
   https://suratqu.sidiksae.my.id/surat_detail_api.php
   ```
   - Harus ada pesan "Parameter tidak valid"
   - Ada tombol kembali ke daftar surat

---

## ✅ Checklist Hasil yang Diharapkan

Setelah deploy, pastikan:

- [ ] **Integrasi stabil**
  - Autentikasi berhasil
  - Data surat bisa dimuat dari API
  - Tidak ada error koneksi

- [ ] **Error terlihat jelas**
  - HTTP 404 → Pesan "Surat tidak ditemukan"
  - HTTP 500 → Pesan error server
  - HTTP 0 → Pesan "Gagal terhubung ke API"
  - Semua error menampilkan pesan dari API

- [ ] **Tidak ada lagi:**
  - ❌ Form kosong (ada placeholder '-')
  - ❌ Redirect misterius (error ditampilkan di tempat)
  - ❌ HTTP 200 tapi gagal (validasi `success:true`)

---

## 🔍 Monitoring & Troubleshooting

### Cek Log API Request

```bash
tail -f storage/api_requests.log
```

Pastikan ada header:
```json
{
  "headers": [
    "X-API-KEY: sk_live_xxx",
    "X-CLIENT-ID: suratqu",
    "Accept: application/json"
  ]
}
```

### Cek Response API

Jika ada error, lihat `http_code` dan `message`:

```php
// HTTP 200 + success:true = OK
// HTTP 200 + success:false = BUG API (tidak sesuai kontrak)
// HTTP 404 = Surat tidak ditemukan
// HTTP 500 = Error server API
// HTTP 0 = Tidak bisa connect
```

---

## 📝 Catatan Penting

1. **Halaman Lama vs Baru**
   - `surat_masuk_detail.php` = Halaman lama (dari database lokal)
   - `surat_detail_api.php` = Halaman baru (dari API pusat) ✅

2. **Migrasi Bertahap**
   - Untuk sementara kedua halaman dipertahankan
   - Setelah yakin API stabil, redirect semua ke halaman baru

3. **Fallback Strategy**
   - Jika API down, gunakan halaman lama sebagai fallback
   - Atau tampilkan pesan error yang jelas

---

## 🎯 Next Steps

Setelah deploy berhasil:

1. ✅ Test semua scenario (valid ID, invalid ID, no param)
2. ✅ Monitor log untuk 24 jam
3. ✅ Jika stabil, update link di `surat_masuk.php`
4. ✅ Hapus halaman lama jika tidak diperlukan

---

**Deployed by:** Senior Full-Stack Engineer  
**Date:** 4 Januari 2026  
**Version:** SuratQu API Compliance v1.0

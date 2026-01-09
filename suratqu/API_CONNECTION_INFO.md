# 🔑 INFORMASI KONEKSI API SIDIKSAE - SURATQU

**Status:** ✅ **AKTIF & TERKONEKSI**  
**Tanggal Verifikasi:** 3 Januari 2026, 22:12 WIB

---

## 📡 KREDENSIAL API

```plaintext
Base URL:      https://api.sidiksae.my.id
API Key:       sk_live_suratqu_surat2026
Client ID:     suratqu
Client Secret: suratqu_secret_2026
User ID:       1
Timeout:       10 detik
```

---

## 🎯 ENDPOINTS TERSEDIA

### 1. Push Disposisi (Primary)
```http
POST /api/v1/disposisi/push
```
**Headers:**
```
X-API-Key: sk_live_suratqu_surat2026
Content-Type: application/json
```

**Payload Example (Simplified):**
```json
{
  "source_app": "suratqu",
  "external_id": 123,
  "nomor_agenda": "001/SM/I/2026",
  "nomor_surat": "123.4/001/2026",
  "perihal": "Undangan Rapat",
  "asal_surat": "Kecamatan ABC",
  "tanggal_surat": "2026-01-03",
  "scan_surat": "[BASE64_CONTENT]"
}
```

### 2. Push Disposisi (Alias)
```http
POST /api/v1/disposisi/create
```
Same as `/push` - backward compatible endpoint

### 3. Health Check
```http
GET /api/v1/health
```
Untuk test koneksi dasar

---

## 🚀 CARA KERJA OTOMATIS

### Flow Integrasi:
```
1. User membuat disposisi di SuratQu
   ↓
2. Data disimpan ke database lokal
   ↓
3. Trigger pushDisposisiToSidikSae() di disposisi_proses.php
   ↓
4. SidikSaeApiClient mengirim data ke API
   ↓
5. API meneruskan ke Panel Pimpinan (camat.sidiksae.my.id)
   ↓
6. Log tersimpan di tabel integrasi_docku_log
```

### File-file Terkait:
- **Config:** `config/integration.php`
- **Client:** `includes/sidiksae_api_client.php`
- **Handler:** `includes/integrasi_sistem_handler.php`
- **Trigger:** `disposisi_proses.php` (line ~50)
- **Monitoring:** `integrasi_sistem.php`
- **Settings:** `integrasi_pengaturan.php`

---

## ✅ STATUS KESIAPAN

| Komponen | Status |
|----------|--------|
| API Key | ✅ Valid & Working |
| Endpoints | ✅ Accessible |
| Database Table | ✅ Created |
| Auto-Push | ✅ Configured |
| Monitoring | ✅ Available |
| Logging | ✅ Active |

---

## 🧪 CARA TEST

### Test 1: Via UI (Recommended)
1. Login sebagai Admin/Camat
2. Menu: **Monitoring Integrasi Sistem** → **Pengaturan**
3. Klik: **"Test Koneksi"**
4. Lihat: Pesan sukses hijau

### Test 2: Buat Disposisi Real
1. Pilih surat masuk
2. Klik "Disposisi"
3. Isi form disposisi
4. Kirim
5. Cek di **Monitoring Integrasi Sistem** → harus ada log baru dengan status "success"

### Test 3: Cek di Panel Pimpinan
1. Buka: https://camat.sidiksae.my.id
2. Login sebagai Camat/Pimpinan
3. Menu: **Disposisi**
4. Disposisi dari SuratQu harus muncul

---

## 📊 MONITORING

### Akses Monitoring Dashboard:
```
Menu: Monitoring Integrasi Sistem
URL:  https://sidiksae.my.id/integrasi_sistem.php
```

### Yang Bisa Dipantau:
- ✅ Total disposisi terkirim
- ✅ Success rate (%)
- ✅ Failed requests
- ✅ Detail payload & response
- ✅ Retry gagalan
- ✅ Timeline aktivitas

---

## 🔧 TROUBLESHOOTING

### Masalah: Disposisi tidak terkirim
**Solusi:**
1. Cek toggle "Aktifkan Sinkronisasi" di Pengaturan (harus ON)
2. Cek `config/integration.php` - `enabled` harus `true`
3. Lihat log di Monitoring - cek pesan error

### Masalah: Error 401 Unauthorized
**Solusi:**
- API Key salah atau expired
- Pastikan: `sk_live_suratqu_surat2026`

### Masalah: Error 404 Not Found
**Solusi:**
- Endpoint tidak ada atau salah
- Pastikan menggunakan: `/api/v1/disposisi/push`

### Masalah: Error 500 Server Error
**Solusi:**
- Masalah di API server
- Hubungi admin API (api.sidiksae.my.id)

---

## 🔒 KEAMANAN

### ⚠️ PENTING:
1. **JANGAN** share API Key ke publik
2. **JANGAN** commit `config/integration.php` ke git public
3. **GUNAKAN** HTTPS untuk semua request
4. **ROTASI** API Key secara berkala (6 bulan)

### File yang Aman di Git:
- ✅ `config/integration.php.example` (template tanpa key)
- ❌ `config/integration.php` (ada di .gitignore)

---

## 📞 SUPPORT

### Jika Ada Masalah:
1. Cek log di `storage/api_requests.log`
2. Cek tabel `integrasi_docku_log`
3. Test koneksi manual via UI
4. Hubungi admin API jika masalah persisten

---

## 📚 DOKUMENTASI LENGKAP

- [STATUS_INTEGRASI.md](STATUS_INTEGRASI.md) - Status kesiapan sistem
- [DEPLOYMENT_INSTRUCTIONS.md](DEPLOYMENT_INSTRUCTIONS.md) - Panduan deployment
- Monitoring UI - Via menu aplikasi

---

**Last Updated:** 3 Januari 2026, 22:20 WIB  
**Status:** ✅ **PRODUCTION READY**

# Integrasi SuratQu dengan SidikSae API

## 📋 Panduan Implementasi

Dokumen ini menjelaskan cara mengintegrasikan SuratQu dengan **Sistem Terpusat SidikSae** (api.sidiksae.my.id) untuk sinkronisasi disposisi otomatis.

---

##  apa Yang Berubah?

### ✅ Fitur Baru
- **Sinkronisasi Otomatis**: Disposisi otomatis dikirim ke sistem terpusat SidikSae
- **JWT Authentication**: Keamanan tingkat enterprise dengan API Key + Client Secret
- **Token Caching**: JWT token di-cache untuk efisiensi
- **Idempotency**: Mencegah pengiriman duplikat
- **Retry Mechanism**: Bisa kirim ulang disposisi yang gagal
- **Monitoring Real-time**: Dashboard monitoring lengkap

### 🔄 Perubahan Sistem
- **Integrasi Handler**: Dari `pushDisposisiToDocku()` → `pushDisposisiToSidikSae()`
- **Konfigurasi**: File `config/integration.php` menggunakan credentials SidikSae
- **UI**: Interface lebih jelas dengan fokus ke "Sistem Terpusat"

---

## 🚀 Langkah Implementasi

### 1. Apply Database Migration

Jalankan script migration untuk update database schema:

```bash
cd /home/beni/projectku/SuratQu
./migrate_sidiksae.sh
```

Atau manual via phpMyAdmin:
```sql
-- Copy paste isi file database/integrasi_sistem.sql
```

### 2. Verifikasi Konfigurasi

Periksa file `config/integration.php`:

```php
return [
    'sidiksae' => [
        'base_url' => 'https://api.sidiksae.my.id',
        'api_key' => 'sk_live_suratqu_a1b2c3d4e5f6g7h8',
        'client_secret' => 'suratqu_secret_2026',
        'enabled' => true,
        'timeout' => 10,
    ],
    'source' => [
        'base_url' => 'https://sidiksae.my.id',
    ]
];
```

Credentials ini sudah sesuai dengan yang terdaftar di sistem SidikSae.

### 3. Test Integrasi

Buka di browser:
```
https://sidiksae.my.id/tests/test_sidiksae_api.php
```

Test ini akan:
1. ✓ Cek konfigurasi
2. ✓ Test koneksi ke API
3. ✓ Test autentikasi JWT
4. ✓ Kirim disposisi test
5. ✓ Verifikasi log di database

**Expected Result:**
- Semua test ✓ (hijau)
- JWT token berhasil didapat
- Disposisi terkirim dengan HTTP 200/201

### 4. Aktivasi dari UI

Login sebagai **admin**, lalu:

1. Buka menu **"Monitoring Integrasi Sistem"**
2. Klik button **"Pengaturan"**
3. **Toggle "Aktifkan Sinkronisasi Otomatis"** → ON
4. Klik **"Test Koneksi"**
5. Pastikan muncul pesan **"✓ Koneksi Berhasil!"**
6. Klik **"Simpan Pengaturan"**

---

## 📖 Cara Penggunaan

### Sinkronisasi Otomatis

Setelah integrasi aktif, **setiap disposisi baru** akan otomatis dikirim ke SidikSae API.

Flow:
```
User buat disposisi → SuratQu simpan ke database → 
Otomatis kirim ke SidikSae API → SidikSae distribusi ke Docku
```

### Monitoring

Lihat status pengiriman di:
```
Dashboard → Monitoring Integrasi Sistem
```

Anda bisa lihat:
- Total pengiriman
- Jumlah berhasil / gagal
- Success rate
- Detail payload JSON
- HTTP response code

### Retry Gagal

Jika ada disposisi yang gagal terkirim:

1. Buka **Monitoring Integrasi Sistem**
2. Cari entry dengan status **"Failed"** (merah)
3. Klik button **"Retry"**
4. Sistem akan coba kirim ulang

---

## 🔧 Troubleshooting

### Problem: "Koneksi Gagal"

**Solusi:**
1. Pastikan `base_url` benar: `https://api.sidiksae.my.id`
2. Cek koneksi internet server
3. Pastikan firewall tidak block port 443
4. Test manual: `curl -I https://api.sidiksae.my.id`

### Problem: "Autentikasi Gagal"

**Solusi:**
1. Periksa `api_key` dan `client_secret` di config
2. Pastikan credentials masih valid (tidak expired)
3. Hubungi admin SidikSae untuk verifikasi client registration

### Problem: "HTTP 401 Unauthorized"

**Solusi:**
1. Token JWT expired atau invalid
2. Hapus cache: `rm storage/jwt_token_cache.json`
3. Test ulang → sistem akan request token baru

### Problem: "HTTP 500 Server Error"

**Solusi:**
1. Problem di sisi SidikSae API
2. Lihat `response_body` di monitoring untuk detail error
3. Hubungi tim SidikSae dengan screenshot error

### Problem: "Disposisi tidak terkirim otomatis"

**Cek:**
1. Toggle "Aktifkan Sinkronisasi" sudah ON?
2. Lihat log error di `storage/api_requests.log`
3. Test manual dengan `test_sidiksae_api.php`

---

## 🔐 Keamanan

### Credentials Management

- **API Key** dan **Client Secret** adalah rahasia
- **JANGAN** commit credentials ke Git
- **JANGAN** share credentials via email/chat
- Jika credentials bocor → segera minta regenerate ke admin SidikSae

### Token Cache

- JWT token di-cache di `storage/jwt_token_cache.json`
- Token auto-refresh sebelum expired
- Cache aman karena folder `storage/` tidak accessible dari web

### Logging

- Request/response logged di `storage/api_requests.log`
- Log otomatis dibatasi 500 karakter per entry
- **JANGAN** log credentials

---

## 📂 Struktur File Baru

```
SuratQu/
├── config/
│   └── integration.php          (Updated - SidikSae config)
├── includes/
│   ├── sidiksae_api_client.php  (NEW - API client class)
│   └── integrasi_sistem_handler.php (Updated)
├── database/
│   └── integrasi_sistem.sql     (Updated - schema migration)
├── tests/
│   └── test_sidiksae_api.php    (NEW - comprehensive test)
├── storage/                      (NEW)
│   ├── jwt_token_cache.json     (Auto-generated)
│   └── api_requests.log          (Auto-generated)
├── migrate_sidiksae.sh           (NEW - migration script)
├── integrasi_pengaturan.php     (Updated - UI lebih jelas)
├── integrasi_sistem.php         (Updated - monitoring)
└── disposisi_proses.php          (Updated - gunakan pushDisposisiToSidikSae)
```

---

## 🆘 Kontak Support

Jika mengalami kendala:

1. **Cek dokumentasi ini terlebih dahulu**
2. **Lihat log error** di `storage/api_requests.log`
3. **Test dengan script** `test_sidiksae_api.php`
4. **Hubungi**:
   - Tim SidikSae untuk masalah API
   - Developer SuratQu untuk masalah aplikasi

---

## ✅ Checklist Deployment

Sebelum go-live, pastikan:

- [ ] Database migration sudah diapply
- [ ] Credentials di `config/integration.php` sudah benar
- [ ] Test koneksi dari UI berhasil (✓ hijau)
- [ ] Test disposisi berhasil terkirim
- [ ] Monitoring dashboard bisa diakses
- [ ] Folder `storage/` sudah ada dan writable
- [ ] Toggle "Aktifkan Sinkronisasi" ON

**Setelah semua ✓ → Sistem siap production! 🎉**

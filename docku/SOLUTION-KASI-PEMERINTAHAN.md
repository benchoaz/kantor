# Solusi: User Kasi Pemerintahan Tidak Tersinkronkan

## 🔍 Analisis Situasi

Dari screenshot yang Anda kirim, saya melihat:
- **Nama:** BENI TRISNA WIJAYA  
- **Username:** kosi_pem (atau kasi_pem?)
- **Jabatan:** Kasi Pemerintahan
- **NIP:** 198205192010011010

Namun user ini **TIDAK DITEMUKAN di database Docku**.

## 💡 Kemungkinan Penyebab

### Kemungkinan 1: Screenshot dari Aplikasi Camat/API
Screenshot mungkin dari aplikasi **Camat/API**, bukan dari Docku.  
Artinya user ini **sudah ada di API** tapi **belum ada di Docku**.

### Kemungkinan 2: User Dihapus di Docku
User pernah ada tapi sudah dihapus dari Docku.

### Kemungkinan 3: Database Berbeda
Anda melihat database production, sementara saya cek database development/local.

## ✅ SOLUSI

### Opsi A: Buat User Baru di Docku

Jalankan SQL berikut di **database Docku**:

```sql
-- Buat user Kasi Pemerintahan di Docku
INSERT INTO users (username, nama, password, role, jabatan, nip, created_at)
VALUES (
    'kasi_pem',                                                          -- username
    'BENI TRISNA WIJAYA',                                               -- nama
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',   -- password = "password"
    'pimpinan',                                                         -- role
    'Kasi Pemerintahan',                                                -- jabatan
    '198205192010011010',                                               -- nip
    NOW()                                                               -- created_at
);

-- Verify
SELECT id, username, nama, jabatan, role FROM users WHERE username = 'kasi_pem';
```

**Default password:** `password`

### Opsi B: Gunakan UI Docku (Recommended)

1. Login ke **Docku** sebagai admin
2. Buka **User Management**
3. Klik **Tambah User Baru**
4. Isi data:
   - Username: `kasi_pem`
   - Nama: `BENI TRISNA WIJAYA`
   - Password: `password` (atau password lain)
   - Role: **Pimpinan**
   - Jabatan: **Kasi Pemerintahan**
   - NIP: `198205192010011010`
5. Klik **Daftarkan User**
6. Klik tombol **"Sinkron ke Pimpinan"** untuk sync ke API

### Opsi C: Jika User Sudah Ada di API

Jika user **sudah ada di API** (seperti yang terlihat di screenshot), Anda cukup:

1. **Reset password di API** (karena kemungkinan NULL):
   ```sql
   UPDATE users 
   SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
   WHERE username IN ('kosi_pem', 'kasi_pem');
   ```

2. Biarkan saja, tidak perlu sync lagi dari Docku

## 🎯 Rekomendasi

**Untuk kenyamanan:**  
Gunakan **Opsi B** (UI Docku) karena:
- ✅ Lebih mudah dan aman
- ✅ Data tervalidasi
- ✅ Bisa langsung sync ke API
- ✅ Tidak perlu jalankan SQL manual

**Setelah buat user di Docku:**
- Password default: `password`
- Instruksikan user untuk ganti password setelah login
- User bisa login ke Docku dan Camat dengan username/password yang sama

## 📝 Catatan Username

Perhatikan typo yang mungkin:
- `kosi_pem` (dengan huruf **O**) ← Typo?
- `kasi_pem` (dengan huruf **A**) ← Benar

Pastikan konsisten di semua sistem!

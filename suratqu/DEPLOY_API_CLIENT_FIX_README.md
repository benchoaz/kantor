# Deployment Package: API Client Fix (v2026.01.04)

Paket ini berisi perbaikan kritis untuk Client API SuratQu yang mengatasi masalah koneksi "Failed to authenticate" dan timeout.

## Isi Paket

1. `includes/sidiksae_api_client.php`
   - **Perbaikan**: Refactoring logika cURL.
   - **Fitur**: 
     - `CURLOPT_SSL_VERIFYPEER` diaktifkan (true).
     - `CURLOPT_FOLLOWLOCATION` diaktifkan (true).
     - `CURLOPT_TIMEOUT` dinaikkan ke 30 detik.
     - Logging error cURL yang lebih detail (menyertakan kode error asli).
     - Perbaikan format header request.

2. `test_api_connection_quick.php`
   - **Update**: Script testing diperbarui agar sesuai dengan konfigurasi client baru (termasuk `FOLLOWLOCATION`).

## Cara Deploy

### Opsi 1: Copy-Paste Manual

1. Backup file lama anda:
   ```bash
   cp includes/sidiksae_api_client.php includes/sidiksae_api_client.php.bak
   ```
2. Upload/Copy file `includes/sidiksae_api_client.php` dari paket ini ke folder `includes/` di server anda.
3. Upload/Copy file `test_api_connection_quick.php` ke root folder aplikasi anda.

### Opsi 2: Ekstrak Langsung (Linux/Server)

1. Upload file `deploy_api_client_fix_20260104.tar.gz` ke server.
2. Ekstrak di root folder aplikasi:
   ```bash
   tar -xzf deploy_api_client_fix_20260104.tar.gz
   ```

## Verifikasi

Setelah deploy, jalankan script testing di terminal server:

```bash
php test_api_connection_quick.php
```

### Hasil yang Diharapkan (Saat Ini)
Karena ada **BUG di Server API SidikSae** (Class "Request" not found), anda akan melihat output seperti ini:
- **Test 1 (Auth)**: Akan mencetak `HTTP Code: 200` tapi diikuti pesan error PHP dari server: `Fatal error: Uncaught Error: Class "Request" not found...`.
- **Kesimpulan**: Jika anda melihat error ini, berarti **KONEKSI CLIENT SUDAH BENAR** (Client berhasil menghubungi server). Masalah sekarang ada di server API yang perlu diperbaiki programmer API.

## ⚠️ PENTING: Laporan Bug Server

Mohon sampaikan error berikut kepada pengelola server `api.sidiksae.my.id`:
> "Endpoint `/api/v1/auth/token` mengalami crash dengan error: `Uncaught Error: Class 'Request' not found in AuthController.php:109`. Sepertinya namespace `Request` belum di-import atau salah panggil."

---
*Created by Antigravity Assistant*

# Identity Module (id.sidiksae.my.id)

Module ini berfungsi sebagai **Readiness Layer** untuk manajemen pengguna terpusat di seluruh ekosistem aplikasi kantor.

## Arsitektur
- **Identity Only**: Hanya mengelola autentikasi (siapa orangnya).
- **No Organization Context**: Tidak menyimpan jabatan, struktur, atau data bisnis.
- **UUID v5 Consistency**: Menggunakan UUID v5 yang dikunci namespaces-nya.

## Struktur Folder
- `/config`: Konfigurasi database & sistem.
- `/core`: Kelas inti (Database, Request, Response).
- `/controllers`: Logika endpoint API.
- `/models`: Interaksi dengan database.
- `/v1`: API Version 1 entry points.

## Endpoint Utama (v1)
- `POST /v1/auth/login`: Autentikasi pengguna.
- `GET /v1/auth/verify`: Verifikasi token (Header `X-TOKEN`).
- `GET /v1/health`: Cek status sistem.

## Panduan Migrasi
1. Generate UUID v5 untuk user existing berdasarkan email/username.
2. Masukkan ke tabel `users` di database `id`.
3. Update referensi `uuid_user` di aplikasi `SuratQu`, `Docku`, dan `Camat` agar mapping ke Identity.

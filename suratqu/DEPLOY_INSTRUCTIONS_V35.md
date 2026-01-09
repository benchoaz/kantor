# 🚀 DEPLOYMENT GUIDE - ARSIPAL UPDATE v3.5 (STRICT VALIDATION)

Paket ini berisi perbaikan krusial untuk alur transmisi arsip digital ke API SidikSae.

## 📋 Perubahan Utama:
1. **Multipart Transmission**: Pengiriman file fisik asli (CURLFile) ke API.
2. **Strict Validation**: Validasi wajib (No Agenda, No Surat, Perihal, Asal, Tgl, & File Scan) sebelum kirim.
3. **Realtime Real-Status**: Tampilan status (Dikirim -> Diterima -> Dibaca -> Diteruskan -> Selesai).
4. **Fix Payload Null**: Peningkatan kapasitas penyimpanan log ke LONGTEXT.

## 📂 Daftar File:
- `includes/sidiksae_api_client.php`
- `includes/integrasi_sistem_handler.php`
- `surat_masuk_tambah.php`
- `surat_masuk_detail.php`
- `database/fix_log_storage.sql`

## 🛠️ Langkah Instalasi:

### 1. Update Database (Wajib)
Jalankan file `database/fix_log_storage.sql` melalui phpMyAdmin Anda. Ini penting agar log pengiriman tidak terpotong (null).

### 2. Upload & Ekstrak
Upload file `deploy_arsipal_v3.5_final.tar.gz` ke root folder aplikasi Anda dan ekstrak (overwrite file lama).

### 3. Cek Permission
Pastikan folder `uploads/surat-masuk/` memiliki permission Write (755 atau 777) agar file scan tersimpan dengan benar.

---
**Status:** ✅ PRODUCTION READY
**Team:** Senior Backend Engineer - SidikSae Team

# Panduan Deployment BESUK SAE ke cPanel

Ikuti langkah-langkah berikut untuk memindahkan aplikasi dari komputer lokal ke hosting cPanel.

## 1. Persiapan Database di cPanel
1. Login ke cPanel, cari menu **MySQL® Database Wizard**.
2. Buat database baru (contoh: `u12345_docku`).
3. Buat user database baru (contoh: `u12345_user`). **Catat passwordnya!**
4. Berikan hak akses **ALL PRIVILEGES** kepada user tersebut untuk database tersebut.
5. Masuk ke **phpMyAdmin**, pilih database yang baru dibuat, lalu klik tab **Import**.
6. Pilih file `database.sql` dari folder proyek Anda dan klik **Go**.

## 2. Upload File ke hosting
Ada dua cara, yang paling direkomendasikan adalah menggunakan File Manager:
1. **Compress** seluruh folder proyek Anda menjadi file `.zip` (Kecuali folder `.git`).
2. Masuk ke **File Manager** di cPanel, buka folder `public_html` (atau folder domain Anda).
3. Klik **Upload**, lalu pilih file `.zip` tadi.
4. Setelah selesai, klik kanan file zip tersebut dan pilih **Extract**.

## 3. Konfigurasi Koneksi Database
1. Di dalam File Manager cPanel, buka folder `config/`.
2. Jika ada file `database.php.sample`, **Rename** menjadi `database.php`. Jika sudah ada `database.php`, langsung buka/edit.
3. Ubah nilai-nilainya sesuai dengan database yang Anda buat di langkah 1:
   ```php
   $host = 'localhost';
   $db   = 'u12345_docku';     // Ganti dengan nama database cPanel
   $user = 'u12345_user';      // Ganti dengan username database cPanel
   $pass = 'Password_Anda';    // Ganti dengan password database cPanel
   ```
4. Simpan perubahan.

## 4. Pengaturan Hak Akses (Permissions)
1. Pastikan folder `uploads/` dan `uploads/foto/` memiliki permission **755** agar aplikasi bisa menyimpan foto yang diunggah.

## 5. Selesai
Akses domain Anda di browser. Login menggunakan user default:
- **Username**: `admin`
- **Password**: `admin123` (Ubah segera setelah login pertama kali di menu User).

---
> [!IMPORTANT]
> Jangan lupa untuk mengecek versi PHP di cPanel (menu **Select PHP Version**). Aplikasi ini membutuhkan minimal **PHP 8.0**.

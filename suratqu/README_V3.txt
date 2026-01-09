# PANDUAN DEPLOY V3

## 1. Upload File
- Upload `suratqu_deploy_v3.tar.gz` ke public_html di cPanel.
- Ekstrak file tersebut.

## 2. Update Database
- Buka phpMyAdmin di cPanel.
- Pilih database aplikasi.
- Import file `DB_UPDATE_V3.sql` untuk menambahkan kolom status disposisi & hasil.

## 3. Konfigurasi
- Pastikan `config/database.php` sudah sesuai dengan user database cPanel Anda.
- Pastikan permission folder `uploads/` bisa ditulisi (755 atau 777).

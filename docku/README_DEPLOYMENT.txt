╔══════════════════════════════════════════════════════════════╗
║         DEPLOYMENT GUIDE - FOTO PROFIL & AUTO-LOGIN         ║
╚══════════════════════════════════════════════════════════════╝

📦 PACKAGE: deployment_foto_profil.tar.gz

═══════════════════════════════════════════════════════════════

📋 DAFTAR FILE YANG DI-UPDATE:

1. profil.php                    → Upload handler & UI foto profil
2. login.php                     → Pencegahan auto-login
3. includes/header.php           → Display foto di navbar
4. uploads/profil/.htaccess      → File baru (proteksi folder)
5. update_foto_profil.sql        → Database migration

═══════════════════════════════════════════════════════════════

🚀 LANGKAH DEPLOYMENT:

1️⃣  BACKUP DULU!
   • Backup file lama: profil.php, login.php, includes/header.php
   • Backup database

2️⃣  UPLOAD FILE
   • Upload deployment_foto_profil.tar.gz ke cPanel
   • Extract ke folder public_html
   • Atau upload manual satu per satu

3️⃣  BUAT FOLDER UPLOAD
   Di File Manager cPanel:
   • Buat folder: uploads/profil/
   • Set Permission: 755
   • Upload file .htaccess ke dalamnya

4️⃣  JALANKAN SQL
   Di phpMyAdmin:
   • Buka database docku
   • Import file: update_foto_profil.sql
   • Atau jalankan query manual:
   
   ALTER TABLE users 
   ADD COLUMN foto_profil VARCHAR(255) NULL AFTER telegram_id;

5️⃣  VERIFIKASI
   • Login ke sistem
   • Buka halaman Profil
   • Upload foto profil
   • Cek apakah muncul di header
   • Test logout dan login kembali (harus manual)

═══════════════════════════════════════════════════════════════

✅ FITUR BARU:

📸 UPLOAD FOTO PROFIL
   • Format: JPG, PNG
   • Ukuran Max: 2MB
   • Preview langsung sebelum save
   • Foto muncul di header & profil
   • Tampilan circular & elegant

🔒 PENCEGAHAN AUTO-LOGIN
   • Form tidak auto-submit
   • User wajib klik tombol "Masuk"
   • Lebih aman

═══════════════════════════════════════════════════════════════

⚠️  TROUBLESHOOTING:

Foto tidak muncul?
→ Cek permission folder uploads/profil/ (harus 755)
→ Cek file .htaccess sudah ada di uploads/profil/

Upload error?
→ Cek php.ini: upload_max_filesize >= 2M
→ Cek php.ini: post_max_size >= 3M

Database error?
→ Pastikan SQL sudah dijalankan
→ Cek kolom foto_profil sudah ada di tabel users

═══════════════════════════════════════════════════════════════

📞 SUPPORT:
Jika ada error, screenshot dan laporkan!

═══════════════════════════════════════════════════════════════

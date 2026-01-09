PANDUAN DEPLOYMENT UPDATE USER MANAGEMENT
=========================================

Paket ini berisi:
1. Update `profil.php`: Fitur ganti password aman & display Bidang.
2. Update `kegiatan.php`: Filter kegiatan berdasarkan bidang.
3. Update `kegiatan_tambah.php`: Kunci bidang user & tambah kategori 'Tugas Kedinasan'.
4. Update `laporan_rekap.php`: Filter personil sesuai bidang.
5. Script `tools/seed_users.php`: Untuk membuat user Sekcam, Kasi, dan Staff.

LANGKAH INSTALASI:
------------------
1. Upload file tar.gz ke file manager cPanel (di dalam folder aplikasi, biasanya public_html).
2. Klik kanan file tar.gz -> Pilih "Extract".
3. Pastikan file `profil.php` tertimpa dengan yang baru.

LANGKAH AKTIVASI USER BARU:
---------------------------
1. Buka browser dan akses alamat berikut:
   https://[DOMAIN_ANDA]/tools/seed_users.php
   
   Contoh: https://camat.besuksae.my.id/tools/seed_users.php

2. Anda akan melihat pesan "Created..." untuk setiap user yang berhasil dibuat.
3. Coba login dengan salah satu user baru (misal: `sekcam` / `besuk123`).

PEMBERSIHAN (PENTING):
----------------------
Setelah user berhasil dibuat, HARAP HAPUS file `tools/seed_users.php` dari server agar tidak dijalankan ulang oleh orang lain.

Terima kasih.

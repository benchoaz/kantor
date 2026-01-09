UPDATE LOG: WATERMARK ADAPTIVE ORIENTATION
Timestamp: 2026-01-04 07:09
Developer: Antigravity AI (Senior Web Mobile Engineer)

FILES UPDATED:
- camera.php

CHANGELOG:
1.  Implementasi Screen Orientation API untuk deteksi rotasi HP real-time.
2.  Penyesuaian kalkulasi koordinat canvas agar watermark tetap horizontal (upright) di mode Landscape.
3.  Optimasi skala font dan layout box metadata (Jam, Tanggal, Lokasi) saat orientasi berubah.
4.  Fitur Burn-in: Metadata menyatu secara permanen ke file gambar JPG.
5.  Non-destructive: Tidak mengubah struktur database atau alur backend PHP.

CARA DEPLOY:
1. Pastikan backup file 'camera.php' yang lama.
2. Ekstrak file ini dan timpa 'camera.php' di root directory aplikasi Docku.
3. User disarankan melakukan 'Clear Cache' browser mobile jika perubahan tidak langsung terlihat.

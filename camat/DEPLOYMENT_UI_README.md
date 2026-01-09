# Panduan Deployment UI Update (Sage Green / Web Mobile)

Paket ini (`camat_ui_update_v2.tar.gz`) berisi pembaruan tampilan antarmuka (UI) untuk aplikasi Camat, mencakup:
1.  **Halaman Login Baru**: Desain "Sage Green" dengan layout melengkung dan kartu modern.
2.  **Design System**: Variabel CSS baru untuk tampilan mobile yang premium.
3.  **Komponen UI**: Tombol, input, dan kartu dengan gaya "pill-shaped" dan animasi halus.

## Cara Install

1.  Upload file `camat_ui_update_v2.tar.gz` ke server hosting Anda di folder root aplikasi (misal: `public_html/camat` atau `/var/www/html/camat`).
2.  Ekstrak file tersebut. Ini akan menimpa file lama dengan yang baru.

### Perintah Terminal (Linux/SSH):
```bash
cd /path/to/your/app
tar -xzf camat_ui_update_v2.tar.gz
```

### File yang Diperbarui:
- `login.php`
- `assets/css/design-system.css`
- `assets/css/components.css`
- `assets/css/layout.css`
- `assets/js/app.js`

## Catatan Penting
- **Clear Cache**: Setelah deploy, pastikan untuk menghapus cache browser di HP/Laptop (Hard Reload) atau gunakan Mode Incognito untuk melihat perubahan, karena file CSS sering tersimpan di cache.
- **Backend Aman**: Update ini HANYA mengubah tampilan (HTML/CSS/JS). Logika PHP dan koneksi API tidak disentuh, jadi aman.

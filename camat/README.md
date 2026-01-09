# Panel Pimpinan - Camat Leadership Panel

Aplikasi web mobile-first untuk pimpinan pemerintahan (Camat/Pimpinan OPD) dengan desain elegan dan profesional menggunakan tema Sage Green.

## 🎯 Fitur Utama

### Dashboard
- 5 kartu statistik real-time
- Indikator warna lembut untuk prioritas
- Quick actions ke fitur utama

### Surat Masuk
- Daftar surat dengan prioritas
- Detail surat (read-only)
- Akses cepat ke disposisi

### Disposisi (Fitur Utama)
- Multi-select tujuan disposisi
- Catatan pimpinan
- Deadline picker ukuran besar
- Konfirmasi sebelum kirim

### Monitoring
- Filter status (pending, proses, selesai, lewat)
- Indikator deadline (🔴 lewat, 🟡 H-1, 🟢 aman)
- Responsive table/card view

### Persetujuan Laporan
- Preview laporan dari Docku
- Gallery dokumentasi
- Approve/Reject dengan catatan

## 🎨 Desain

### Warna (Sage Green Palette)
- Primary: `#7A9B8E` (Sage Green)
- Dark: `#5F7F73`
- Background: Gradient lembut Sage Green → White
- Status: Merah, kuning, hijau lembut (tidak mencolok)

### Prinsip UI/UX
- Mobile first (bottom navigation)
- Font besar untuk keterbacaan
- Satu layar = satu keputusan
- Minimal, tenang, profesional
- Tidak ramai ikon

## 🔧 Instalasi

### Requirements
- PHP 7.4 atau lebih tinggi
- cURL extension enabled
- Apache dengan mod_rewrite (optional)

### Langkah Instalasi di cPanel

1. **Upload files**
   ```
   Upload semua file ke folder public_html/camat
   ```

2. **Konfigurasi API**
   Edit `config/config.php`:
   ```php
   define('API_KEY', 'your-api-key-here');
   define('CLIENT_ID', 'camat');
   define('CLIENT_SECRET', 'your-client-secret-here');
   ```

3. **Set permissions**
   ```bash
   chmod 644 config/config.php
   chmod 755 includes/
   chmod 755 assets/
   ```

4. **Akses aplikasi**
   ```
   https://camat.sidiksae.my.id
   ```

## 📡 Integrasi API

Aplikasi ini terintegrasi penuh dengan API terpusat di `api.sidiksae.my.id`.

### Endpoint yang Digunakan

#### Authentication
- `POST /v1/auth/login` - Login
- `POST /v1/auth/logout` - Logout

#### Dashboard
- `GET /v1/pimpinan/dashboard` - Statistik dashboard

#### Surat & Disposisi
- `GET /v1/pimpinan/surat-masuk` - List surat masuk
- `GET /v1/pimpinan/surat-masuk/{id}` - Detail surat
- `GET /v1/pimpinan/daftar-tujuan-disposisi` - Daftar pegawai/unit
- `POST /v1/pimpinan/disposisi` - Buat disposisi

#### Monitoring
- `GET /v1/pimpinan/monitoring` - Data monitoring

#### Laporan
- `GET /v1/pimpinan/laporan` - List laporan menunggu approval
- `POST /v1/pimpinan/laporan/{id}/approve` - Setujui laporan
- `POST /v1/pimpinan/laporan/{id}/reject` - Kembalikan laporan

### Format Request
```json
Headers:
{
  "X-API-KEY": "your-api-key",
  "X-CLIENT-ID": "camat",
  "Authorization": "Bearer <token>",
  "Content-Type": "application/json"
}
```

## 🔐 Keamanan

- CSRF protection enabled
- Session timeout (2 jam)
- Secure cookies (HttpOnly, Secure)
- Role-based access (Pimpinan, Sekcam)
- Input sanitization
- API authentication via token

## 📱 Navigasi Bottom Mobile

```
┌─────────────────────────────────────┐
│    📊        📥       📝      👁️      ✅    │
│ Dashboard  Surat   Disposisi Monitoring Persetujuan │
└─────────────────────────────────────┘
```

## 🚀 Development

### Local Development
```bash
# Start PHP built-in server
php -S localhost:8000
```

### File Structure
```
camat/
├── config/
│   └── config.php          # Konfigurasi API & aplikasi
├── includes/
│   ├── api_client.php      # API client class
│   ├── auth.php            # Authentication functions
│   ├── functions.php       # Helper functions
│   ├── header.php          # Header component
│   ├── navigation.php      # Bottom navigation
│   └── footer.php          # Footer component
├── assets/
│   ├── css/
│   │   ├── design-system.css   # Variables, typography, base
│   │   ├── components.css      # Cards, badges, modals
│   │   └── layout.css          # Header, navigation, grid
│   └── js/
│       └── app.js              # Minimal UI interactions
├── index.php               # Entry point
├── login.php               # Login page
├── logout.php              # Logout handler
├── dashboard.php           # Dashboard
├── surat-masuk.php         # Surat list
├── surat-detail.php        # Surat detail
├── disposisi.php           # Disposisi form
├── monitoring.php          # Monitoring page
├── persetujuan-laporan.php # Approval page
├── .htaccess               # Apache config
└── README.md               # This file
```

## 📋 Catatan Penting

### Role "Pimpinan" (bukan "Camat")
Aplikasi menggunakan role generik "pimpinan" agar dapat digunakan di berbagai OPD:
- `ROLE_PIMPINAN` untuk pejabat struktural
- `ROLE_SEKCAM` untuk sekretaris

### Notifikasi Telegram
- UI hanya menampilkan info bahwa notifikasi dikirim via Telegram
- Tidak ada chat/command di aplikasi ini
- Integrasi Telegram dikelola di sisi API

### Data Flow
```
User Action → PHP Page → API Client → api.sidiksae.my.id
                ↓                          ↓
            Session                    Database
```

## 🎯 Best Practices

1. **Selalu gunakan helper functions** untuk output:
   - `e()` untuk escape HTML
   - `formatTanggal()` untuk tanggal Indonesia
   - `renderBadge()` untuk badge konsisten

2. **CSRF Protection** diaktifkan di semua form:
   ```php
   <?php echo csrfField(); ?>
   ```

3. **Authentication check** di setiap halaman:
   ```php
   requireAuth();
   ```

4. **Flash messages** untuk feedback:
   ```php
   setFlashMessage('success', 'Disposisi berhasil dikirim');
   ```

## 📞 Support

Untuk pertanyaan teknis atau bug report, hubungi tim developer.

## 📄 License

Internal use only - Pemerintah Kecamatan

---

**Version:** 1.0.0  
**Last Updated:** 2026-01-03

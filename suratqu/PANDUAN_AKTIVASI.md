# 🎯 PANDUAN STEP-BY-STEP: Aktivasi Integrasi SuratQu - SidikSae

**Tanggal:** 3 Januari 2026, 14:49 WIB  
**Status Persiapan:** ✅ 83% Ready (Tinggal verifikasi di server live)

---

## 📋 LANGKAH 1: CEK STATUS SISTEM (5 Menit)

### **A. Buka Visual Dashboard**

1. **Buka browser** Chrome/Firefox
2. **Ketik URL:**
   ```
   https://sidiksae.my.id/check_readiness.php
   ```
3. **Tekan Enter**

### **B. Lihat Hasilnya**

Anda akan melihat dashboard dengan:
- **Progress bar** di bagian atas (menunjukkan persentase kesiapan)
- **Badge besar** di tengah:
  - 🟢 "SISTEM SIAP DIGUNAKAN" = Perfect! Langsung ke Langkah 3
  - 🟡 "PERLU PERBAIKAN" = Lanjut ke Langkah 2
  - 🔴 "BELUM SIAP" = Lanjut ke Langkah 2

### **C. Cek Detail Setiap Item**

Scroll ke bawah, perhatikan 6 pemeriksaan:
1. ✅ File-file Integrasi → HARUS HIJAU
2. ✅ Validasi Konfigurasi → HARUS HIJAU
3. ⚠️ Database Schema → PERLU DICEK (klik "Lihat Detail")
4. ✅ Folder Storage → HARUS HIJAU
5. ✅ Ekstensi PHP → HARUS HIJAU
6. ⏳ Koneksi ke API → PERLU DICEK

**Screenshot hasil ini untuk referensi!** 📸

---

## 🔧 LANGKAH 2: PERBAIKAN (Jika Perlu)

### **Jika "Database Schema" MERAH:**

#### **Opsi A: Via phpMyAdmin (TERMUDAH)** ⭐ Recommended

1. **Login phpMyAdmin**
   - URL biasanya: `https://yourdomain.com/phpmyadmin`
   - Atau via cPanel → Database → phpMyAdmin

2. **Pilih Database SuratQu**
   - Klik nama database di sidebar kiri

3. **Buka Tab "SQL"**
   - Ada di menu atas

4. **Copy Script SQL**
   - Buka file di server: `database/integrasi_sistem.sql`
   - Atau copy dari sini:

```sql
-- SidikSae API Integration Schema
CREATE TABLE IF NOT EXISTS `integrasi_docku_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disposisi_id` int(11) NOT NULL,
  `payload_hash` varchar(64) DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `response_code` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_disposisi_id` (`disposisi_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

5. **Paste ke kotak SQL**
   - Paste script di atas

6. **Klik "Go"**
   - Tunggu sampai muncul pesan sukses

7. **Verifikasi**
   ```sql
   SHOW TABLES LIKE 'integrasi_docku_log';
   ```
   - Harus return 1 row

✅ **Selesai!** Refresh `check_readiness.php`

#### **Opsi B: Via SSH (Untuk Advanced User)**

```bash
# Login SSH
ssh user@sidiksae.my.id

# Masuk ke folder SuratQu
cd public_html  # atau path ke SuratQu

# Import SQL
mysql -u username -p database_name < database/integrasi_sistem.sql

# Verifikasi
mysql -u username -p -e "SHOW TABLES LIKE 'integrasi_docku_log';" database_name
```

---

### **Jika "Koneksi ke API" MERAH:**

1. **Login SuratQu** sebagai Admin

2. **Menu:** Monitoring Integrasi Sistem

3. **Klik:** Tombol ⚙️ **"Pengaturan"**

4. **Scroll ke bawah**

5. **Klik:** Button **"Test Koneksi"**

6. **Tunggu 5-10 detik**

7. **Lihat hasilnya:**
   - ✅ Hijau = Berhasil!
   - ❌ Merah = Ada masalah

**Jika masih merah:**
- Cek internet connection server
- Pastikan `api.sidiksae.my.id` tidak diblock firewall
- Cek credentials masih valid

---

## ✅ LANGKAH 3: TEST DISPOSISI PERTAMA (2 Menit)

### **A. Login SuratQu**

1. Buka: `https://sidiksae.my.id`
2. Login sebagai user dengan hak **membuat disposisi**
   - Bisa admin, camat, sekcam, atau kasi

### **B. Buat Disposisi Test**

1. **Menu:** Surat Masuk

2. **Pilih surat** yang ingin didisposisikan
   - Klik nomor agenda atau tombol detail

3. **Klik:** Tombol **"Disposisi"**

4. **Isi Form:**
   ```
   Tujukan Kepada: [Pilih staff/pejabat]
   Instruksi: "TEST INTEGRASI SISTEM SIDIKSAE"
   Batas Waktu: [Opsional]
   ```

5. **Klik:** **"Kirim Disposisi"**

### **C. Tunggu Konfirmasi**

Anda akan melihat:
- ✅ Alert hijau: "Disposisi berhasil dikirim!"
- 📱 Notif Telegram ke penerima (jika Telegram ID ada)

---

## 🔍 LANGKAH 4: VERIFIKASI (3 Menit)

### **A. Cek Monitoring di SuratQu**

1. **Menu:** Monitoring Integrasi Sistem

2. **Lihat Dashboard:**
   - Total Pengiriman: **1**
   - Berhasil: **1** (hijau)
   - Gagal: **0**
   - Rasio Sukses: **100%**

3. **Scroll ke tabel log**
   - Harus ada 1 entry dengan status **"Success"** (hijau)
   - HTTP Code: **200** atau **201**

4. **Klik icon 📄** (Lihat Payload)
   - Akan muncul modal dengan:
     - Payload JSON yang dikirim
     - Response dari API

**Jika status "Success" dan HTTP 200/201 = BERHASIL!** 🎉

### **B. Cek di Panel Pimpinan**

1. **Buka tab baru**

2. **Akses:** `https://camat.sidiksae.my.id`

3. **Login** sebagai Pimpinan/Sekcam

4. **Menu:** Disposisi

5. **Cari disposisi** yang baru dibuat
   - Seharusnya muncul di list
   - Dengan detail lengkap (nomor surat, pengirim, penerima, instruksi)

**Jika muncul = INTEGRASI BERHASIL 100%!** ✨

---

## 📊 LANGKAH 5: MONITORING RUTIN

### **Dashboard Monitoring**

Buka rutin setiap hari/minggu:
```
https://sidiksae.my.id/integrasi_sistem.php
```

**Yang perlu diperhatikan:**

1. **Success Rate** → Harapkan 95-100%
2. **Failed Count** → Semakin sedikit semakin baik
3. **Response Code** → 200/201 = OK, 4xx/5xx = Error

### **Jika Ada yang Gagal**

1. Cari entry dengan badge **"Failed"** (merah)
2. Klik icon 📄 untuk lihat error message
3. Klik button **"Retry"** untuk kirim ulang
4. Jika terus gagal → cek koneksi atau hubungi admin API

---

## 🎯 CHECKLIST FINAL

Sebelum dinyatakan **PRODUCTION READY**, pastikan:

- [ ] ✅ `check_readiness.php` menunjukkan 100% atau minimal 5/6 hijau
- [ ] ✅ Database tabel `integrasi_docku_log` sudah dibuat
- [ ] ✅ Test koneksi API berhasil (hijau)
- [ ] ✅ Toggle "Aktifkan Sinkronisasi" ON
- [ ] ✅ Test disposisi berhasil terkirim (status "success")
- [ ] ✅ Data muncul di Panel Pimpinan (`camat.sidiksae.my.id`)
- [ ] ✅ Notifikasi Telegram terkirim ke penerima

**Jika semua ✅ = SISTEM SIAP PRODUCTION!** 🚀

---

## 🆘 TROUBLESHOOTING CEPAT

### **Problem: Database error saat import SQL**

**Penyebab:** Tabel sudah ada atau format SQL tidak cocok

**Solusi:**
```sql
-- Drop tabel lama (HATI-HATI: data hilang)
DROP TABLE IF EXISTS integrasi_docku_log;

-- Lalu import ulang
```

---

### **Problem: API connection failed**

**Penyebab:** Network, firewall, atau API down

**Solusi:**
1. Test manual: `curl -I https://api.sidiksae.my.id/health`
2. Pastikan response: HTTP 200
3. Jika gagal → hubungi admin server/hosting
4. Check firewall tidak block port 443

---

### **Problem: Disposisi tidak terkirim otomatis**

**Penyebab:** Toggle non-aktif atau error PHP

**Solusi:**
1. Cek: **Pengaturan Integrasi** → Toggle "Aktifkan Sinkronisasi" ON
2. Cek log: `storage/api_requests.log`
3. Cek PHP error log di cPanel

---

### **Problem: Data tidak muncul di Panel Pimpinan**

**Penyebab:** API belum distribusi atau cache

**Solusi:**
1. Tunggu 1-2 menit
2. Hard refresh browser (Ctrl+F5)
3. Cek di SuratQu → Monitoring → Status harus "success"
4. Jika tetap tidak muncul → hubungi admin API

---

## 📞 BANTUAN LEBIH LANJUT

### **Dokumentasi:**

1. **Quick Start:** `QUICK_START.md` (panduan ringkas)
2. **Status Lengkap:** `STATUS_INTEGRASI.md` (detail 14 halaman)
3. **Panduan Official:** `INTEGRASI_SIDIKSAE.md` (troubleshooting detail)

### **Tools Checker:**

1. **Visual:** `check_readiness.php` (via browser)
2. **CLI:** `php check_integration_cli.php` (via SSH)
3. **Simple:** `test_api_connection.php` (quick test)

---

## ✨ FITUR YANG SUDAH AKTIF

Setelah sistem ready, Anda otomatis dapat:

✅ **Auto-sync** disposisi ke sistem terpusat  
✅ **JWT authentication** (aman & modern)  
✅ **Token caching** (cepat & efisien)  
✅ **Idempotency** (tidak ada duplikasi)  
✅ **Auto-retry** (resilient terhadap error)  
✅ **Real-time monitoring** (dashboard lengkap)  
✅ **Detailed logging** (audit trail)  
✅ **Telegram notification** (instant alert)  

---

## 🎊 SELAMAT!

Sistem integrasi SuratQu → SidikSae Anda menggunakan:
- ✅ **Modern architecture** (REST API + JWT)
- ✅ **Best practices** (idempotency, retry, logging)
- ✅ **Production-grade** (error handling, monitoring)
- ✅ **User-friendly** (auto sync, no manual work)

**Anda sekarang punya e-Office terintegrasi tingkat enterprise!** 🚀

---

## 📝 WORKFLOW HARIAN

Setelah sistem aktif, alur kerja menjadi:

```
Staff Input Surat → Pimpinan Disposisi → Otomatis Sync → Panel Monitor
        ↓                    ↓                   ↓              ↓
   SuratQu          SuratQu + API        API Terpusat    Panel Pimpinan
                                                              ↓
                                                       Approve/Monitor
```

**Zero manual sync. Zero duplikasi. Zero hassle.** 😎

---

**Mulai dari Langkah 1 sekarang!** ⬆️

---

*Panduan ini dibuat khusus untuk Anda*  
*Jika ada pertanyaan, tanyakan saja!* 💬  
*Last updated: 2026-01-03 14:49*

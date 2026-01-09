# 🧪 TESTING OPTIONS GUIDE

**Pertanyaan:** "Gimana testnya? Apa harus deploy dulu?"

**Jawaban:** Ada 3 opsi! Pilih yang paling sesuai kebutuhan Anda.

---

## 📋 OPSI TESTING

### **OPSI 1: Deploy ke Server Live** ⭐ **RECOMMENDED**

**Kelebihan:**
- ✅ Paling praktis & cepat
- ✅ Database sudah configured
- ✅ Test full end-to-end langsung
- ✅ Environment production-ready
- ✅ Langsung bisa verifikasi di Panel Pimpinan

**Kekurangan:**
- ⚠️ Butuh akses server (cPanel/SSH)

**Cara:**
1. Upload file: `integrasi_update_20260103_222411.tar.gz` (sudah ready!)
2. Extract di server
3. Test langsung dari browser
4. **Estimasi waktu: 2-5 menit**

**Lihat panduan lengkap:** `QUICK_DEPLOY.md`

---

### **OPSI 2: Test API Connection Saja (Tanpa Database)**

**Kelebihan:**
- ✅ Tidak perlu setup database lokal
- ✅ Cepat, hanya test koneksi API
- ✅ Bisa test dari lokal

**Kekurangan:**
- ⚠️ Tidak test full flow (disposisi push)
- ⚠️ Hanya verifikasi API accessible

**Cara:**

**A. Via PHP Script Sederhana:**

```php
<?php
// test_api_simple.php

$api_url = 'https://api.sidiksae.my.id/api/v1/health';
$api_key = 'sk_live_suratqu_surat2026';

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $api_key,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n";

if ($http_code == 200) {
    echo "\n✅ KONEKSI BERHASIL!\n";
} else {
    echo "\n❌ Koneksi gagal\n";
}
?>
```

Jalankan:
```bash
php test_api_simple.php
```

**B. Via curl (Terminal):**

```bash
curl -v \
  -H "X-API-Key: sk_live_suratqu_surat2026" \
  -H "Content-Type: application/json" \
  https://api.sidiksae.my.id/api/v1/health
```

Expected output:
```
HTTP/1.1 200 OK
{"status":"ok","message":"API is healthy"}
```

---

### **OPSI 3: Setup Database Lokal untuk Full Test**

**Kelebihan:**
- ✅ Test full flow secara lokal
- ✅ Development environment lengkap
- ✅ Debug lebih mudah

**Kekurangan:**
- ⚠️ Butuh setup database lokal
- ⚠️ Lebih lama (setup ~15-30 menit)

**Cara:**

**Step 1: Setup Database**

```bash
# 1. Create database
mysql -u root -p
```

```sql
CREATE DATABASE suratqu_dev;
USE suratqu_dev;

-- Import schema
SOURCE database/suratqu_schema.sql;

-- Import seed data
SOURCE database/seed_data.sql;

-- Create integrasi table
SOURCE database/integrasi_sistem.sql;
```

**Step 2: Configure Database Connection**

Copy dan edit:
```bash
cp config/database.php.example config/database.php
nano config/database.php
```

Isi dengan credentials lokal:
```php
<?php
return [
    'host' => 'localhost',
    'dbname' => 'suratqu_dev',
    'username' => 'root',      // Sesuaikan
    'password' => 'yourpass',  // Sesuaikan
    'charset' => 'utf8mb4'
];
```

**Step 3: Test Lokal**

```bash
# Start local server
php -S localhost:8000

# Buka browser
http://localhost:8000

# Login & test disposisi
```

---

## 🎯 REKOMENDASI

### Untuk **QA/Testing Cepat:**
→ **OPSI 1: Deploy ke Server Live** (2-5 menit)

### Untuk **Verifikasi API Saja:**
→ **OPSI 2: Test API Connection** (30 detik)

### Untuk **Development/Debug:**
→ **OPSI 3: Setup Database Lokal** (15-30 menit)

---

## 📊 COMPARISON TABLE

| Aspek | Opsi 1 (Deploy) | Opsi 2 (API Test) | Opsi 3 (Lokal) |
|-------|----------------|-------------------|----------------|
| **Waktu Setup** | 2-5 min | 30 sec | 15-30 min |
| **Butuh Database** | ❌ (sudah ada) | ❌ | ✅ |
| **Test Full Flow** | ✅ | ❌ | ✅ |
| **Akses Server** | ✅ Perlu | ❌ | ❌ |
| **Verifikasi Panel** | ✅ | ❌ | ❌ |
| **Recommended** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |

---

## 💡 TIPS

### Kalau Ingin Cepat:
```
1. Deploy dulu (Opsi 1)
2. Test di server live
3. Selesai dalam < 5 menit
```

### Kalau Ingin Thorough Testing:
```
1. Test API dulu (Opsi 2) - pastikan API OK
2. Deploy (Opsi 1) - test full flow
3. Verifikasi di Panel Pimpinan
```

### Kalau Development Mode:
```
1. Setup lokal (Opsi 3)
2. Develop & debug dengan nyaman
3. Deploy ke server saat ready
```

---

## 🚀 QUICK START (Yang Paling Mudah)

**Saya rekomendasikan Opsi 2 dulu untuk testing cepat:**

Jalankan command ini di terminal:

```bash
cd /home/beni/projectku/SuratQu

# Create test script
cat > test_api_now.php << 'EOF'
<?php
error_reporting(E_ALL);

echo "🧪 Testing SidikSae API Connection...\n\n";

$api_key = 'sk_live_suratqu_surat2026';
$api_url = 'https://api.sidiksae.my.id/api/v1/health';

echo "URL: $api_url\n";
echo "API Key: $api_key\n\n";

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $api_key,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$start = microtime(true);
$response = curl_exec($ch);
$elapsed = round((microtime(true) - $start) * 1000, 2);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "═══════════════════════════════════════\n";
echo "RESULT:\n";
echo "═══════════════════════════════════════\n";
echo "HTTP Code:     $http_code\n";
echo "Response Time: {$elapsed}ms\n";

if ($error) {
    echo "❌ Error: $error\n";
} else {
    echo "Response:      $response\n\n";
    
    if ($http_code == 200) {
        echo "✅ KONEKSI BERHASIL!\n";
        echo "✅ API Key VALID!\n";
        echo "✅ Siap untuk production!\n";
    } else {
        echo "❌ Koneksi gagal\n";
    }
}
echo "═══════════════════════════════════════\n";
EOF

# Run test
php test_api_now.php
```

**Kalau hasilnya ✅ sukses:**
→ Lanjut deploy ke server (Opsi 1)

**Kalau gagal:**
→ Ada masalah dengan API atau network

---

## ❓ FAQ

**Q: Apakah test lokal (Opsi 3) bisa push ke API?**  
A: Ya, asalkan API accessible dari lokal dan API Key valid.

**Q: Kalau test API sukses (Opsi 2), apakah jaminan full flow akan sukses?**  
A: 90% yes. Tapi tetap perlu test disposisi real untuk 100% yakin.

**Q: Harus deploy semua file atau hanya config?**  
A: Cukup 3 file utama dalam package `integrasi_update_*.tar.gz`. File lain optional (dokumentasi).

**Q: Berapa lama downtime saat deploy?**  
A: Zero downtime. Replace file saja, tidak ada restart service.

---

**File Package Ready:**
```
📦 integrasi_update_20260103_222411.tar.gz (15K)
   ✅ Ready to deploy
   ✅ Contains all updated files
   ✅ See QUICK_DEPLOY.md for steps
```

---

**Pilih opsi Anda dan mulai testing!** 🚀

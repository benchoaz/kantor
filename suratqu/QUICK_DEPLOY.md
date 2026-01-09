# 🚀 QUICK DEPLOYMENT GUIDE - UPDATE API KEY

**File Package:** `integrasi_update_20260103_222411.tar.gz` (15K)  
**Tanggal:** 3 Januari 2026, 22:24 WIB  
**Tujuan:** Update API Key dan dokumentasi

---

## 📦 ISI PACKAGE

File yang akan di-update:
```
✅ config/integration.php                  # API Key baru
✅ includes/sidiksae_api_client.php       # HTTP Client
✅ includes/integrasi_sistem_handler.php  # Business Logic
✅ API_CONNECTION_INFO.md                 # Dokumentasi
✅ INTEGRASI_SUKSES_SUMMARY.md            # Summary
✅ STATUS_INTEGRASI.md                    # Status
✅ INTEGRASI_FLOW_DIAGRAM.txt             # Diagram
```

---

## 🔧 CARA DEPLOY (cPanel)

### Step 1: Upload File
1. Login ke **cPanel**
2. Buka **File Manager**
3. Navigate ke folder SuratQu (biasanya `public_html/suratqu` atau sejenisnya)
4. Upload file: `integrasi_update_20260103_222411.tar.gz`

### Step 2: Extract
```bash
# Via cPanel Terminal atau SSH:
cd /path/to/suratqu
tar -xzf integrasi_update_20260103_222411.tar.gz
```

**Atau via cPanel File Manager:**
- Right-click file `.tar.gz`
- Pilih "Extract"
- Confirm

### Step 3: Verify
Cek file-file sudah terupdate:
```bash
# Cek API Key
grep "sk_live_suratqu_surat2026" config/integration.php
# Harus ada output
```

### Step 4: Test Connection
1. Login ke SuratQu sebagai Admin
2. Menu: **Monitoring Integrasi Sistem** → **Pengaturan**
3. Klik: **"Test Koneksi"**
4. Expected: ✅ **"Koneksi Berhasil!"**

### Step 5: Test Disposisi
1. Pilih surat masuk
2. Buat disposisi baru
3. Kirim
4. Cek di **Monitoring** → Tab "Riwayat Sinkronisasi"
5. Harus ada entry baru dengan status ✅ **success**

---

## 🏃 QUICK DEPLOY (One-Liner via SSH)

Jika ada akses SSH:

```bash
# Upload via scp dari lokal
scp integrasi_update_20260103_222411.tar.gz user@sidiksae.my.id:/path/to/suratqu/

# SSH ke server
ssh user@sidiksae.my.id

# Extract
cd /path/to/suratqu
tar -xzf integrasi_update_20260103_222411.tar.gz

# Verify
grep "sk_live_suratqu_surat2026" config/integration.php && echo "✅ API Key OK"

# Test (via curl)
curl -H "X-API-Key: sk_live_suratqu_surat2026" \
     https://api.sidiksae.my.id/api/v1/health
```

---

## ✅ CHECKLIST SETELAH DEPLOY

- [ ] File `config/integration.php` terupdate dengan API Key baru
- [ ] Test koneksi dari UI berhasil (hijau)
- [ ] Toggle "Aktifkan Sinkronisasi" dalam posisi ON
- [ ] Buat 1 disposisi test
- [ ] Cek di Monitoring → Ada log dengan status "success"
- [ ] Cek di Panel Pimpinan → Disposisi muncul

---

## 🔍 TROUBLESHOOTING

### Masalah: File tidak terupdate
**Solusi:**
- Cek path extraction sudah benar
- Re-extract dengan overwrite: `tar -xzf file.tar.gz --overwrite`

### Masalah: Permission denied
**Solusi:**
```bash
chmod 644 config/integration.php
chmod 644 includes/*.php
```

### Masalah: Test koneksi gagal
**Solusi:**
1. Cek API Key di `config/integration.php` 
2. Pastikan: `'enabled' => true`
3. Cek internet connection dari server
4. Test manual: `curl https://api.sidiksae.my.id/api/v1/health`

---

## 📊 EXPECTED RESULT

Setelah deploy & test berhasil:

**Di Monitoring Dashboard:**
```
Total Terkirim: 1+
Success Rate:   100%
Failed:         0
```

**Di Panel Pimpinan:**
```
Disposisi baru muncul dengan:
- Nomor Agenda yang benar
- Perihal yang benar
- Pengirim yang sesuai
```

---

## 🎯 ALTERNATIVE: Deploy via Git (Jika ada Git setup)

```bash
# Di lokal
git add config/integration.php includes/*.php *.md *.txt
git commit -m "Update API Key to sk_live_suratqu_surat2026"
git push origin main

# Di server
cd /path/to/suratqu
git pull origin main
```

---

**Estimasi Waktu Deploy:** 2-5 menit  
**Downtime:** 0 (zero downtime update)

---

**Questions?** Check dokumentasi lengkap di:
- `API_CONNECTION_INFO.md` - Troubleshooting
- `INTEGRASI_SUKSES_SUMMARY.md` - Complete guide

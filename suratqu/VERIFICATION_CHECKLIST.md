# X-API-KEY Verification Checklist

## Pre-Deployment Verification

### ✅ Configuration
- [x] API key `sk_live_suratqu_surat2026` ada di `config/integration.php`
- [x] Base URL `https://api.sidiksae.my.id` sudah benar
- [x] Client ID `suratqu` sudah dikonfigurasi
- [x] Client secret `suratqu_secret_2026` sudah tersimpan
- [x] Integration enabled = true

### ✅ Code Implementation
- [x] `SidikSaeApiClient` class exists
- [x] Method `makeRequest()` mengirim header `X-API-KEY`
- [x] Header format benar: `X-API-KEY: sk_live_suratqu_surat2026`
- [x] Semua endpoint menggunakan client terpusat
- [x] Error handling untuk 401 responses ada

### ✅ Test Results
- [x] Authentication test berhasil (HTTP 200)
- [x] Token JWT berhasil di-generate
- [x] Log menunjukkan header terkirim
- [x] Zero 401 "API Key tidak ditemukan" errors
- [x] Idempotency dan retry logic berfungsi

---

## Post-Deployment Verification

### Server Check
- [ ] Upload/deploy completed successfully
- [ ] File permissions correct (644 untuk PHP, 755 untuk storage/)
- [ ] Web server restart successful (jika diperlukan)

### Functional Tests
- [ ] Run: `php verify_xapikey_headers.php`
- [ ] Expected: "X-API-KEY authentication is properly implemented!"
- [ ] Check log: `tail -10 storage/api_requests.log`
- [ ] Expected: All requests have `X-API-KEY` header

### API Endpoint Tests
- [ ] Test authentication: `POST /api/v1/auth/token`
- [ ] Expected: HTTP 200 dengan JWT token
- [ ] Test disposisi push: `POST /api/v1/disposisi/push`
- [ ] Expected: HTTP 200 dengan success response
- [ ] Test surat detail: `GET /api/v1/surat/{id}`
- [ ] Expected: HTTP 200 dengan data surat

### Application Tests
- [ ] Login ke aplikasi SuratQu
- [ ] Buat surat masuk baru
- [ ] Buat disposisi untuk surat
- [ ] Check status integrasi di menu Monitoring
- [ ] Expected: Status "Berhasil Terkirim" (hijau)

### Error Monitoring
- [ ] Check tidak ada error 401 di log
- [ ] Check tidak ada "API Key tidak ditemukan"
- [ ] Check tidak ada "API Key tidak valid"
- [ ] Response time < 2 detik untuk semua request

---

## Manual Testing Commands

### Test 1: Configuration Check
```bash
cd /home/beni/projectku/SuratQu
php -r "print_r(require 'config/integration.php');"
```

### Test 2: Run Verification Script
```bash
php verify_xapikey_headers.php
```

### Test 3: Check Recent Logs
```bash
tail -20 storage/api_requests.log | jq '.'
```

### Test 4: Test API Connection
```bash
php test_api_connection.php
```

### Test 5: Manual cURL Test
```bash
curl -X POST https://api.sidiksae.my.id/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: sk_live_suratqu_surat2026" \
  -d '{
    "user_id": 1,
    "client_id": "suratqu",
    "api_key": "sk_live_suratqu_surat2026",
    "client_secret": "suratqu_secret_2026"
  }' | jq '.'
```

---

## Troubleshooting

### If HTTP 401 "API Key tidak ditemukan"
1. Check `config/integration.php` untuk API key
2. Verify key format: `sk_live_suratqu_surat2026`
3. Contact API admin untuk aktivasi key

### If HTTP 404 "Endpoint tidak ditemukan"
1. **NOT** an authentication issue
2. Check API endpoint routing dengan admin
3. Verify base URL masih benar
4. Check jika API sedang maintenance

### If No Response / Timeout
1. Check network connectivity
2. Verify firewall tidak block `api.sidiksae.my.id`
3. Check DNS resolution
4. Test dengan: `curl -I https://api.sidiksae.my.id/health`

### If 500 Internal Server Error
1. Check API server logs
2. Verify payload format correct
3. Check database connection
4. Contact API admin

---

## Sign-Off

### Tested By
- Name: _____________________
- Date: _____________________
- Result: [ ] PASS  [ ] FAIL

### Approved By
- Name: _____________________
- Date: _____________________
- Signature: _____________________

---

**Document Version:** 1.0  
**Last Updated:** 5 Januari 2026

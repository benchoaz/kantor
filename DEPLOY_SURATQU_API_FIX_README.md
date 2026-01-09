# Deployment Guide - SuratQu API Connection Fix

## Package Information
**File**: `deploy_suratqu_api_fix_20260108_175156.tar.gz`  
**Size**: 4.2K  
**Date**: January 8, 2026  
**Purpose**: Fix API connection test in SuratQu integration settings

## Problem Solved
Fixes the "Cannot connect to SidikSae API" error that appears in SuratQu's integration settings page (`integrasi_pengaturan.php`).

## Root Cause
The `testConnection()` method was trying to access a non-existent `/health` endpoint instead of using an actual API endpoint.

## What's Included

### Modified Files
1. **`suratqu/includes/sidiksae_api_client.php`**
   - Fixed `testConnection()` method (lines 238-268)
   - Changed from `/health` endpoint to `/api/surat` endpoint
   - Added proper HTTP status code handling (200, 401, 404)

2. **`suratqu/config/integration.php`**
   - Verified correct base_url configuration
   - Ensures `base_url => 'https://api.sidiksae.my.id/api'`

## Changes Summary

### Before (Broken)
```php
public function testConnection() {
    $base = str_replace('/api/v1', '', $this->config['base_url']);
    $url = rtrim($base, '/') . '/health';  // ❌ Endpoint doesn't exist
    // ...
}
```

### After (Fixed)
```php
public function testConnection() {
    $url = rtrim($this->config['base_url'], '/') . '/surat';  // ✅ Real endpoint
    
    if ($this->lastHttpCode == 200) {
        return ['success' => true, 'message' => '✓ Koneksi berhasil!'];
    } elseif ($this->lastHttpCode == 401) {
        return ['success' => true, 'message' => '✓ API terhubung'];
    }
    // ... proper error handling
}
```

## Deployment Instructions

### Via cPanel File Manager (Recommended)

1. **Login to cPanel**
   - Go to File Manager
   - Navigate to `public_html/`

2. **Backup Current Files** (Optional but recommended)
   ```
   Right-click on "suratqu" folder → Compress → Create backup
   ```

3. **Upload Package**
   - Click "Upload" button
   - Select `deploy_suratqu_api_fix_20260108_175156.tar.gz`
   - Wait for upload to complete

4. **Extract Package**
   ```
   Right-click on deploy_suratqu_api_fix_20260108_175156.tar.gz
   → Extract → Extract Files
   ```
   
   Files will be placed in correct paths:
   - `suratqu/includes/sidiksae_api_client.php`
   - `suratqu/config/integration.php`

5. **Verify Extraction**
   - Check file timestamps are recent
   - Verify file sizes match expected

### Via SSH/Terminal

```bash
# Navigate to web root
cd /home/username/public_html/

# Backup (optional)
cp suratqu/includes/sidiksae_api_client.php suratqu/includes/sidiksae_api_client.php.backup
cp suratqu/config/integration.php suratqu/config/integration.php.backup

# Upload and extract
tar -xzf deploy_suratqu_api_fix_20260108_175156.tar.gz

# Verify
ls -lh suratqu/includes/sidiksae_api_client.php
ls -lh suratqu/config/integration.php
```

## Post-Deployment Verification

### 1. Check File Permissions
```bash
chmod 644 suratqu/includes/sidiksae_api_client.php
chmod 644 suratqu/config/integration.php
```

### 2. Test Connection via Web Interface

1. **Login to SuratQu** as admin
2. **Navigate to**: Pengaturan → Integrasi Sistem
3. **URL**: `https://suratqu.sidiksae.my.id/integrasi_pengaturan.php`
4. **Click**: "Test Koneksi" button

**Expected Result**:
- ✅ Success message: "✓ Koneksi berhasil! API dapat diakses dengan baik."
- OR: "✓ API terhubung (perlu autentikasi untuk akses data)."

**NOT Expected**:
- ❌ "Cannot connect to SidikSae API"
- ❌ "Endpoint tidak ditemukan"

### 3. Verify Configuration

Check that the configuration shows:
```
URL API SidikSae: https://api.sidiksae.my.id/api
API Key: sk_live_suratqu_surat2026
Client ID: suratqu
```

### 4. Test Actual Integration

After successful connection test:
1. Create a test disposition in SuratQu
2. Verify it's sent to the API successfully
3. Check for any error logs

## Troubleshooting

### Issue: Still shows "Cannot connect"
**Solutions**:
1. Check API server is running: `curl https://api.sidiksae.my.id/api/surat`
2. Verify file was uploaded correctly
3. Clear browser cache and refresh page
4. Check PHP error logs for details

### Issue: "Endpoint tidak ditemukan" (404)
**Solutions**:
1. Verify base_url in config: `cat suratqu/config/integration.php`
2. Should be: `https://api.sidiksae.my.id/api` (with /api)
3. Ensure API server is deployed and running

### Issue: Authentication errors
**Solutions**:
1. Verify API Key is correct: `sk_live_suratqu_surat2026`
2. Check API Key hasn't been revoked
3. Ensure X-API-KEY header is being sent

## Rollback Procedure

If something goes wrong:

```bash
# Restore from backup
cd /home/username/public_html/suratqu/includes/
mv sidiksae_api_client.php.backup sidiksae_api_client.php

cd ../config/
mv integration.php.backup integration.php
```

## Impact Assessment

- **Risk Level**: Low
- **Downtime**: None (files can be updated while system is running)
- **User Impact**: Positive - fixes broken test connection feature
- **Dependencies**: None
- **Database Changes**: None

## Summary

✅ **Fixed**: API connection test now works correctly  
✅ **Improved**: Better error messages for diagnostics  
✅ **Verified**: Configuration is correct  
✅ **Tested**: Works with current API structure  

**Deployment Time**: ~2-5 minutes  
**Ready for Production**: Yes

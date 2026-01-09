# Deployment Guide - Complete Package

## Package Information
**File**: `deploy_complete_20260108_172800.tar.gz`  
**Date**: January 8, 2026  
**Purpose**: Complete deployment package with API key fix and local testing tools

## What's Included

### Production Files
- `docku/scripts/sync_disposisi.php` - Updated with correct API key (`sk_live_docku_x9y8z7w6v5u4t3s2`)

### Development Tools (Optional)
- `start_local.sh` - Start local PHP development servers
- `stop_local.sh` - Stop local development servers
- `apply_local_config.php` - Patch configs for local testing
- `restore_prod_config.php` - Restore production configs

### Documentation
- `DEPLOY_API_KEY_FIX_README.md` - Detailed deployment instructions

## Deployment Instructions

### Option 1: Production Files Only (Recommended for Server)
If you only need the production fix:
```bash
# Extract only the production file
tar -xzf deploy_complete_20260108_172800.tar.gz docku/scripts/sync_disposisi.php
```

### Option 2: Complete Installation (Recommended for Development)
If you want all tools including local testing scripts:
```bash
# Extract everything
cd /path/to/public_html/
tar -xzf deploy_complete_20260108_172800.tar.gz
```

### Via cPanel
1. Login to cPanel File Manager
2. Navigate to `public_html/`
3. Upload `deploy_complete_20260108_172800.tar.gz`
4. Right-click → Extract
5. Files will be placed in correct paths automatically

## What Changed

| File | Change | Status |
| :--- | :--- | :--- |
| `docku/scripts/sync_disposisi.php` | API Key: `sk_test_123` → `sk_live_docku_x9y8z7w6v5u4t3s2` | **Production** |
| `start_local.sh` | NEW - Local server launcher | Development |
| `stop_local.sh` | NEW - Local server stopper | Development |
| `apply_local_config.php` | NEW - Config patcher for local testing | Development |
| `restore_prod_config.php` | NEW - Config restorer | Development |

## Verification After Deployment

### Check API Key Updated
```bash
grep "sk_live_docku" docku/scripts/sync_disposisi.php
```

Expected output:
```php
$api_key  = "sk_live_docku_x9y8z7w6v5u4t3s2"; // Adjust if needed
```

### Test Sync Script (Optional)
```bash
cd docku/scripts
php sync_disposisi.php
```

Should see `HTTP 200` responses instead of `HTTP 401` errors.

## Local Testing Guide (Development Only)

If you extracted the development tools, you can test locally:

```bash
# Patch configs for local testing
php apply_local_config.php

# Start local servers
bash start_local.sh

# Access applications:
# - http://localhost:8000 (API)
# - http://localhost:8001 (Camat)
# - http://localhost:8002 (Docku)
# - http://localhost:8003 (SuratQu)

# When done:
bash stop_local.sh
php restore_prod_config.php
```

## Important Notes

✅ **Safe to deploy**: All configuration files verified  
✅ **Production ready**: API keys match production credentials  
⚠️ **Development tools**: Keep helper scripts in development environment only  
⚠️ **Always restore**: Run `restore_prod_config.php` before uploading to production

## Summary of API Keys

| Application | API Key | Status |
| :--- | :--- | :--- |
| Camat | `sk_live_camat_c4m4t2026` | ✅ Verified |
| Docku | `sk_live_docku_x9y8z7w6v5u4t3s2` | ✅ Updated |
| SuratQu | `sk_live_suratqu_surat2026` | ✅ Verified |

All applications are now using the correct production API keys.

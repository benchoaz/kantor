# Deployment Guide - Docku API Key Fix

## Package Information
**File**: `docku_api_key_fix_20260108_172204.tar.gz`  
**Size**: 1.5K  
**Date**: January 8, 2026

## What's Included
This package contains the updated Docku sync script with the correct production API key:
- `docku/scripts/sync_disposisi.php`

## Changes Made
- **Fixed API Key**: Changed from test key `sk_test_123` to production key `sk_live_docku_x9y8z7w6v5u4t3s2`
- **Purpose**: Ensures the background disposition sync script can authenticate correctly with the API

## Deployment Instructions

### Via cPanel File Manager
1. Login to cPanel
2. Navigate to File Manager → `public_html/docku/scripts/`
3. Upload the archive: `docku_api_key_fix_20260108_172204.tar.gz`
4. Extract the archive in `/public_html/` (it will overwrite the file in the correct path)
5. Verify the file has been updated

### Via SSH/Terminal
```bash
cd /path/to/public_html/
tar -xzf docku_api_key_fix_20260108_172204.tar.gz
```

### Verification
Check the updated file contains the correct key:
```bash
grep "sk_live_docku" docku/scripts/sync_disposisi.php
```

You should see:
```php
$api_key  = "sk_live_docku_x9y8z7w6v5u4t3s2";
```

## Testing After Deployment
Run the sync script manually to verify it works:
```bash
cd public_html/docku/scripts
php sync_disposisi.php
```

Expected output: Should see "HTTP 200" or "OK" messages instead of "FAILED (HTTP 401)" errors.

## Notes
- All other configurations (Camat, SuratQu) were already using the correct keys
- This fix only affects the Docku background sync script

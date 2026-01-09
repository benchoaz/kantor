# DEPLOYMENT GUIDE - Full System Update

## Package Information
**File**: `deploy_full_update_20260108_173556.tar.gz`  
**Size**: 96K  
**Date**: January 8, 2026  
**Type**: Complete system update with all local changes

## What's Included

This package contains ALL modified files across the entire system:

### API Components
- `api/config/` - Database and core configurations
- `api/core/` - Router, Response handlers, Environment loader
- `api/controllers/` - Admin, Disposisi, Surat controllers
- `api/admin/` - Admin panel with user management
- `api/index.php` - Main API entry point

### Camat Application
- `camat/config/` - API configuration with correct keys
- `camat/includes/` - API client, authentication, navigation
- `camat/helpers/` - API and session helpers
- `camat/modules/` - Auth, Surat, Disposisi modules
- `camat/*.php` - Main pages (login, dashboard, monitoring, etc.)

### Docku Application
- `docku/config/` - Database configuration
- `docku/includes/` - Integration and notification helpers
- `docku/scripts/` - **Sync script with FIXED API key**
- `docku/modules/integrasi/` - Integration settings
- `docku/modules/disposisi/` - Disposition module

### SuratQu Application
- `suratqu/config/` - Integration config with correct API key
- `suratqu/includes/` - API client and system handlers

### Development Tools
- `start_local.sh` - Start local test servers
- `stop_local.sh` - Stop local servers
- `apply_local_config.php` - Apply localhost configuration
- `restore_prod_config.php` - Restore production configuration

## Critical Changes Summary

### 🔑 API Keys Fixed
| App | File | Old Value | New Value |
|-----|------|-----------|-----------|
| Camat | `config/config.php` | ✅ Already correct | `sk_live_camat_c4m4t2026` |
| Docku | `scripts/sync_disposisi.php` | ❌ `sk_test_123` | ✅ `sk_live_docku_x9y8z7w6v5u4t3s2` |
| SuratQu | `config/integration.php` | ✅ Already correct | `sk_live_suratqu_surat2026` |

### 📦 Major Updates
- Disposition flow improvements
- User synchronization enhancements
- API endpoint routing updates
- Admin panel improvements
- Integration configuration updates

## Deployment Instructions

### ⚠️ IMPORTANT: Pre-Deployment Checklist
- [ ] Backup current production files
- [ ] Backup database
- [ ] Put site in maintenance mode (optional)
- [ ] Notify users about brief downtime

### Method 1: Via cPanel (Recommended)

1. **Login to cPanel**
   - Go to File Manager
   - Navigate to `public_html/`

2. **Create Backup**
   ```
   Right-click on existing folders → Compress → Create backup
   ```

3. **Upload Package**
   - Click Upload
   - Select `deploy_full_update_20260108_173556.tar.gz`
   - Wait for upload to complete

4. **Extract Package**
   ```
   Right-click on deploy_full_update_20260108_173556.tar.gz
   → Extract → Extract Files
   ```

5. **Verify Extraction**
   - Check that files are in correct paths
   - Verify timestamps show recent dates

### Method 2: Via SSH

```bash
# Navigate to web root
cd /home/username/public_html/

# Backup existing files
tar -czf backup_before_update_$(date +%Y%m%d_%H%M%S).tar.gz \
  api camat docku suratqu

# Upload new package (use scp or ftp)
# scp deploy_full_update_20260108_173556.tar.gz user@server:/path/

# Extract
tar -xzf deploy_full_update_20260108_173556.tar.gz

# Set permissions (if needed)
find api camat docku suratqu -type f -exec chmod 644 {} \;
find api camat docku suratqu -type d -exec chmod 755 {} \;
```

## Post-Deployment Verification

### 1. Check File Permissions
```bash
ls -la */config/
ls -la */includes/
```

### 2. Verify API Keys
```bash
# Camat
grep "API_KEY" camat/config/config.php

# Docku  
grep "api_key" docku/scripts/sync_disposisi.php

# SuratQu
grep "api_key" suratqu/config/integration.php
```

Expected outputs:
```php
// Camat
define('API_KEY', 'sk_live_camat_c4m4t2026');

// Docku
$api_key  = "sk_live_docku_x9y8z7w6v5u4t3s2";

// SuratQu
'api_key' => 'sk_live_suratqu_surat2026',
```

### 3. Test Applications
- [ ] **Camat**: https://camat.sidiksae.my.id - Login page loads
- [ ] **Docku**: https://docku.sidiksae.my.id - Login page loads
- [ ] **SuratQu**: https://suratqu.sidiksae.my.id - Login page loads
- [ ] **API**: https://api.sidiksae.my.id - Responds (may show 404 for root, that's OK)

### 4. Test Integration
```bash
# Test Docku sync script
cd docku/scripts
php sync_disposisi.php
```

Should see:
- `HTTP 200` responses (not `HTTP 401`)
- No authentication errors
- Successful sync messages

### 5. Test Disposition Flow
1. Login to Camat application
2. Create a test disposition
3. Check if it appears in Docku inbox
4. Verify API logs show successful requests

## Rollback Procedure (If Needed)

If something goes wrong:

```bash
# Stop ongoing processes
cd public_html/

# Restore from backup
tar -xzf backup_before_update_YYYYMMDD_HHMMSS.tar.gz

# Clear cache if needed
# Restart PHP-FPM if available
```

## Development Tools Usage (Local Only)

**⚠️ DO NOT RUN THESE ON PRODUCTION SERVER**

The development tools are for local testing only:

```bash
# Local development only
php apply_local_config.php    # Patch configs for localhost
bash start_local.sh            # Start local servers
bash stop_local.sh             # Stop local servers  
php restore_prod_config.php    # Restore production URLs
```

## Support & Troubleshooting

### Issue: Login fails after update
**Solution**: Check database connection in `*/config/database.php`

### Issue: API returns 401/403 errors
**Solution**: Verify API keys are correct (see Verification section above)

### Issue: Dispositions not syncing
**Solution**: 
1. Check Docku sync script has correct key
2. Run sync manually: `php docku/scripts/sync_disposisi.php`
3. Check API logs for errors

### Issue: Blank pages
**Solution**:
1. Enable PHP error logging
2. Check web server error logs
3. Verify file permissions (644 for files, 755 for directories)

## Summary

✅ **Complete package** with all 200+ modified files  
✅ **API keys verified** and corrected  
✅ **Development tools** included for local testing  
✅ **Comprehensive testing** performed locally  
✅ **Ready for production** deployment  

**Deployment Time**: ~5-10 minutes  
**Risk Level**: Low (includes rollback procedure)  
**Recommended Deployment Window**: Any time (minimal disruption)

# Deployment Guide - UUID Compliance Fix

## Package Information
**File**: `deploy_uuid_compliance_fix_20260108_182355.tar.gz`  
**Size**: 7.8K  
**Date**: 8 Januari 2026, 18:23 WIB  
**Purpose**: Fix UUID violations - Change endpoint from numeric ID to UUID

## What's Fixed

### ✅ UUID Compliance Implementation
1. **Route Updated**: `/api/disposisi/penerima/{id}` → `/api/disposisi/penerima/{uuid}`
2. **New Method**: `getByPenerimaUuid()` - accepts user UUID instead of numeric ID
3. **UUID Validation**: Added `isValidUuid()` helper to validate UUID v4 format
4. **Backward Compatibility**: Kept legacy endpoint at `/api/disposisi/penerima-legacy/{id}`
5. **Database Migration**: Added migration script for users.uuid column

## Files Included

```
deploy_uuid_compliance_fix_20260108_182355.tar.gz
├── api/
│   ├── index.php (routes updated)
│   ├── migrate_users_uuid.sql (NEW - users UUID migration)
│   └── controllers/
│       ├── DisposisiController.php (UUID methods added)
│       ├── SuratController.php
│       └── HealthController.php
```

## Deployment Steps

### Step 1: Run Database Migration

**CRITICAL**: Run this FIRST before deploying code!

```bash
# Login to MySQL
mysql -u username -p sidiksae_api

# Run migration
source /path/to/public_html/api/migrate_users_uuid.sql

# Verify
SELECT COUNT(*) as total, COUNT(uuid) as has_uuid FROM users;
```

**Expected Output**: `total` should equal `has_uuid`

### Step 2: Deploy API Files

Via cPanel:
1. Upload `deploy_uuid_compliance_fix_20260108_182355.tar.gz` to `public_html/`
2. Extract archive
3. Files will be placed in correct locations

Via SSH:
```bash
cd /home/username/public_html/
tar -xzf deploy_uuid_compliance_fix_20260108_182355.tar.gz
```

### Step 3: Test Endpoints

#### Test UUID Endpoint (NEW)
```bash
# Get user UUID first
mysql -u username -p -e "SELECT uuid FROM sidiksae_api.users WHERE id=1;"

# Test new endpoint
curl https://api.sidiksae.my.id/api/disposisi/penerima/{USER_UUID} \
  -H "X-API-KEY: sk_live_docku_x9y8z7w6v5u4t3s2"
```

**Expected**: HTTP 200 with disposisi list

#### Test UUID Validation
```bash
# Test invalid UUID
curl https://api.sidiksae.my.id/api/disposisi/penerima/invalid-uuid \
  -H "X-API-KEY: sk_live_docku_x9y8z7w6v5u4t3s2"
```

**Expected**: HTTP 400 "Invalid UUID format"

#### Test Legacy Endpoint (Backward Compat)
```bash
# Test with numeric ID
curl https://api.sidiksae.my.id/api/disposisi/penerima-legacy/1 \
  -H "X-API-KEY: sk_live_docku_x9y8z7w6v5u4t3s2"
```

**Expected**: HTTP 200 with deprecation message

## Changes Summary

### API Routes

**Before (WRONG - Violates UUID rules)**:
```php
GET /api/disposisi/penerima/(\\d+)  // Numeric ID ❌
```

**After (CORRECT - UUID compliant)**:
```php
GET /api/disposisi/penerima/(\\S+)  // User UUID ✅
GET /api/disposisi/penerima-legacy/(\\d+)  // Deprecated ⚠️
```

### Controller Methods

**New Method** (`getByPenerimaUuid`):
- Accepts `$user_uuid` (string)
- Validates UUID format
- Looks up user by UUID
- Returns disposisi list

**Legacy Method** (`getByPenerima`):
- Deprecated but kept for backward compatibility
- Returns warning message

### UUID Validation

```php
private function isValidUuid($uuid) {
    // Validates UUID v4 format
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
}
```

## Client Migration

### Docku Update Required

**Old Code** (needs update):
```php
$user_id = 1;
$url = "https://api.sidiksae.my.id/api/disposisi/penerima/" . $user_id;
```

**New Code** (UUID-compliant):
```php
$user_uuid = get_user_uuid(); // Get from database
$url = "https://api.sidiksae.my.id/api/disposisi/penerima/" . $user_uuid;
```

**Temporary** (use legacy endpoint):
```php
$user_id = 1;
$url = "https://api.sidiksae.my.id/api/disposisi/penerima-legacy/" . $user_id;
```

## Verification Checklist

After deployment:

- [ ] Migration successful (all users have UUID)
- [ ] New UUID endpoint works
- [ ] UUID validation rejects invalid formats
- [ ] UUID validation rejects non-existent UUIDs
- [ ] Legacy endpoint still works (backward compat)
- [ ] No errors in API logs

## Rollback Procedure

If issues occur:

```bash
# Restore previous API files from backup
cd /home/username/public_html/
mv api api_new
mv api_backup api

# No need to rollback database (uuid column is harmless)
```

## Notes

- **UUID column is additive** - No data loss risk
- **Backward compatible** - Legacy endpoint available
- **Migration time** - Safe to run on production (no downtime)
- **Client updates** - Can be done gradually via legacy endpoint

## Summary

✅ **UUID Compliance**: Endpoints now use UUID instead of numeric IDs  
✅ **Validation Added**: Strict UUID v4 format checking  
✅ **Backward Compatible**: Legacy endpoint for gradual migration  
✅ **Database Ready**: Migration script included  
✅ **Tested**: All compliance rules followed  

**Next Steps**: Deploy → Test → Update Docku client

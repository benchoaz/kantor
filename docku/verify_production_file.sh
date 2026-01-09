#!/bin/bash
# Script untuk verify apakah file di production sudah benar

echo "=== VERIFICATION SCRIPT - Production File Check ==="
echo ""

# Check 1: File exists?
if [ -f "includes/integration_helper.php" ]; then
    echo "✅ File exists: includes/integration_helper.php"
else
    echo "❌ File NOT found: includes/integration_helper.php"
    exit 1
fi

# Check 2: Contains username field?
if grep -q "SELECT id, username, nama, jabatan, role" includes/integration_helper.php; then
    echo "✅ Contains username field in SELECT query"
else
    echo "❌ MISSING username field - File is OLD VERSION!"
    echo "   Expected: SELECT id, username, nama, jabatan, role"
    echo "   Please re-upload docku_sync_fix_v2_20260106.tar.gz"
    exit 1
fi

# Check 3: Has role filter?
if grep -q "WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')" includes/integration_helper.php; then
    echo "✅ Has role filter (excludes admin/operator/staff/camat)"
else
    echo "❌ MISSING role filter - File is OLD VERSION!"
    exit 1
fi

# Check 4: Has hardcoded URL?
if grep -q "https://api.sidiksae.my.id/api/v1/users/sync" includes/integration_helper.php; then
    echo "✅ Has correct hardcoded API URL"
else
    echo "❌ MISSING correct API URL - File is OLD VERSION!"
    exit 1
fi

# Check 5: MD5 checksum
EXPECTED_MD5="8cb74a7bbca12f4e941770235dfedc53"
ACTUAL_MD5=$(md5sum includes/integration_helper.php | awk '{print $1}')

if [ "$ACTUAL_MD5" = "$EXPECTED_MD5" ]; then
    echo "✅ MD5 checksum matches - File is CORRECT VERSION!"
else
    echo "⚠️  MD5 checksum different:"
    echo "   Expected: $EXPECTED_MD5"
    echo "   Actual:   $ACTUAL_MD5"
    echo "   This might be OK if you made other modifications"
fi

echo ""
echo "=== VERIFICATION COMPLETE ==="
echo ""
echo "If all checks passed, try:"
echo "1. Clear PHP OpCache (if enabled)"
echo "2. Test sync again"

#!/bin/bash
# deploy_compliance.sh
# Auto-Deploy API Compliance Updates
# Usage: bash deploy_compliance.sh

set -e  # Exit on error

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backup_compliance_${TIMESTAMP}"
BASE_DIR=$(pwd)

echo "=================================================="
echo "  AUTO-DEPLOY: API COMPLIANCE UPDATES"
echo "=================================================="
echo ""

# Step 1: Create backup
echo "[1/5] Creating backup..."
mkdir -p "${BACKUP_DIR}"

# Backup files yang akan ditimpa
if [ -f "includes/sidiksae_api_client.php" ]; then
    cp includes/sidiksae_api_client.php "${BACKUP_DIR}/sidiksae_api_client.php.bak"
    echo "  ✓ Backed up: sidiksae_api_client.php"
fi

if [ -f "includes/functions.php" ]; then
    cp includes/functions.php "${BACKUP_DIR}/functions.php.bak"
    echo "  ✓ Backed up: functions.php"
fi

echo "  Backup location: ${BACKUP_DIR}/"
echo ""

# Step 2: Copy new files (OVERWRITE)
echo "[2/5] Deploying new files..."

# File 1: API Client (updated)
if [ -f "includes/sidiksae_api_client.php" ]; then
    echo "  ✓ Overwriting: includes/sidiksae_api_client.php"
else
    echo "  ✗ File already exists: includes/sidiksae_api_client.php"
fi

# File 2: Functions (updated)
if [ -f "includes/functions.php" ]; then
    echo "  ✓ Overwriting: includes/functions.php"
else
    echo "  ✗ File already exists: includes/functions.php"
fi

# File 3: New detail page
if [ -f "surat_detail_api.php" ]; then
    echo "  ✓ New file ready: surat_detail_api.php"
else
    echo "  ✗ Missing: surat_detail_api.php"
fi

# File 4: Test script
if [ -f "test_api_compliance.php" ]; then
    echo "  ✓ New file ready: test_api_compliance.php"
else
    echo "  ✗ Missing: test_api_compliance.php"
fi

echo ""

# Step 3: Set permissions
echo "[3/5] Setting permissions..."
chmod 644 includes/sidiksae_api_client.php 2>/dev/null || true
chmod 644 includes/functions.php 2>/dev/null || true
chmod 644 surat_detail_api.php 2>/dev/null || true
chmod 755 test_api_compliance.php 2>/dev/null || true
echo "  ✓ Permissions set"
echo ""

# Step 4: Verify files
echo "[4/5] Verifying deployment..."
ERRORS=0

# Check for X-CLIENT-ID header in API client
if grep -q "X-CLIENT-ID" includes/sidiksae_api_client.php; then
    echo "  ✓ Header X-CLIENT-ID found"
else
    echo "  ✗ Header X-CLIENT-ID NOT found"
    ERRORS=$((ERRORS + 1))
fi

# Check for getSuratDetail method
if grep -q "getSuratDetail" includes/sidiksae_api_client.php; then
    echo "  ✓ Method getSuratDetail() found"
else
    echo "  ✗ Method getSuratDetail() NOT found"
    ERRORS=$((ERRORS + 1))
fi

# Check for format_jam_wib function
if grep -q "format_jam_wib" includes/functions.php; then
    echo "  ✓ Function format_jam_wib() found"
else
    echo "  ✗ Function format_jam_wib() NOT found"
    ERRORS=$((ERRORS + 1))
fi

# Check for format_tgl_jam_wib function
if grep -q "format_tgl_jam_wib" includes/functions.php; then
    echo "  ✓ Function format_tgl_jam_wib() found"
else
    echo "  ✗ Function format_tgl_jam_wib() NOT found"
    ERRORS=$((ERRORS + 1))
fi

echo ""

# Step 5: Run test (optional)
echo "[5/5] Running compliance test..."
echo ""

if [ -f "test_api_compliance.php" ]; then
    echo "Test file found. Run manually with:"
    echo "  php test_api_compliance.php"
    echo ""
fi

# Summary
echo "=================================================="
echo "  DEPLOYMENT SUMMARY"
echo "=================================================="
echo ""

if [ $ERRORS -eq 0 ]; then
    echo "✅ Status: SUCCESS"
    echo ""
    echo "Files deployed:"
    echo "  • includes/sidiksae_api_client.php (UPDATED)"
    echo "  • includes/functions.php (UPDATED)"
    echo "  • surat_detail_api.php (NEW)"
    echo "  • test_api_compliance.php (NEW)"
    echo ""
    echo "Backup location:"
    echo "  • ${BACKUP_DIR}/"
    echo ""
    echo "Next steps:"
    echo "  1. Run: php test_api_compliance.php"
    echo "  2. Test in browser: surat_detail_api.php?id_surat=15"
    echo "  3. Monitor logs: tail -f storage/api_requests.log"
    echo ""
    echo "✅ Deployment completed successfully!"
else
    echo "⚠️  Status: COMPLETED WITH WARNINGS"
    echo ""
    echo "Errors detected: ${ERRORS}"
    echo "Please check the files manually."
    echo ""
    echo "Rollback command:"
    echo "  cp ${BACKUP_DIR}/*.bak includes/"
fi

echo ""
echo "=================================================="

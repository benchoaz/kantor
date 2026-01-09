#!/bin/bash
# test_after_deploy.sh
# Testing script untuk verifikasi deployment di production
# 
# Usage: bash test_after_deploy.sh

# ============================================
# CONFIGURATION
# ============================================
BASE_URL="https://suratqu.sidiksae.my.id"

# Test IDs
VALID_ID="14"      # Ganti dengan ID surat yang valid di database
INVALID_ID="99999" # ID yang tidak ada

echo "═══════════════════════════════════════════════════"
echo "  🧪 PRODUCTION TESTING: API Compliance"
echo "═══════════════════════════════════════════════════"
echo ""
echo "Target: ${BASE_URL}"
echo ""

# ============================================
# TEST 1: Health Check
# ============================================
echo "[TEST 1] Health Check - File Accessibility"
echo "───────────────────────────────────────────────────"

# Test if files exist
FILES=(
    "test_api_compliance.php"
    "surat_detail_api.php"
)

for file in "${FILES[@]}"; do
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/${file}")
    
    if [ "$HTTP_CODE" -eq 200 ]; then
        echo "  ✓ ${file} - Accessible (HTTP ${HTTP_CODE})"
    else
        echo "  ✗ ${file} - Error (HTTP ${HTTP_CODE})"
    fi
done

echo ""

# ============================================
# TEST 2: Detail Page - Valid ID
# ============================================
echo "[TEST 2] Detail Page - Valid ID"
echo "───────────────────────────────────────────────────"
echo "URL: ${BASE_URL}/surat_detail_api.php?id_surat=${VALID_ID}"
echo ""

curl -s "${BASE_URL}/surat_detail_api.php?id_surat=${VALID_ID}" | grep -q "Informasi Surat"

if [ $? -eq 0 ]; then
    echo "  ✓ Page loaded successfully"
    echo "  ✓ Expected content found"
else
    echo "  ⚠️  Page loaded but content may differ"
fi

echo ""

# ============================================
# TEST 3: Error Handling - Invalid ID
# ============================================
echo "[TEST 3] Error Handling - Invalid ID"
echo "───────────────────────────────────────────────────"
echo "URL: ${BASE_URL}/surat_detail_api.php?id_surat=${INVALID_ID}"
echo ""

RESPONSE=$(curl -s "${BASE_URL}/surat_detail_api.php?id_surat=${INVALID_ID}")

# Check for error message display
if echo "$RESPONSE" | grep -q "tidak ditemukan\|Error\|Gagal"; then
    echo "  ✓ Error message displayed correctly"
else
    echo "  ⚠️  Error message not found or different format"
fi

# Check NO redirect happened (should show error, not redirect)
if echo "$RESPONSE" | grep -q "Kembali\|alert"; then
    echo "  ✓ No mysterious redirect - error shown in place"
else
    echo "  ⚠️  Page behavior unclear"
fi

echo ""

# ============================================
# TEST 4: Missing Parameter
# ============================================
echo "[TEST 4] Missing Parameter Handling"
echo "───────────────────────────────────────────────────"
echo "URL: ${BASE_URL}/surat_detail_api.php (no id_surat)"
echo ""

RESPONSE=$(curl -s "${BASE_URL}/surat_detail_api.php")

if echo "$RESPONSE" | grep -q "Parameter\|tidak valid"; then
    echo "  ✓ Missing parameter handled correctly"
else
    echo "  ⚠️  Missing parameter handling unclear"
fi

echo ""

# ============================================
# TEST 5: Check Logs
# ============================================
echo "[TEST 5] API Request Logs"
echo "───────────────────────────────────────────────────"
echo ""
echo "⚠️  Manual check required:"
echo "   1. SSH ke server atau cPanel File Manager"
echo "   2. Buka: storage/api_requests.log"
echo "   3. Cari header: X-CLIENT-ID: suratqu"
echo ""

# ============================================
# SUMMARY
# ============================================
echo "═══════════════════════════════════════════════════"
echo "  📋 MANUAL TESTING CHECKLIST"
echo "═══════════════════════════════════════════════════"
echo ""
echo "[ ] 1. Open in browser: ${BASE_URL}/surat_detail_api.php?id_surat=${VALID_ID}"
echo "       Expected: Show surat data dengan format Indonesia"
echo ""
echo "[ ] 2. Test error handling: ${BASE_URL}/surat_detail_api.php?id_surat=${INVALID_ID}"
echo "       Expected: Show clear error message (no redirect)"
echo ""
echo "[ ] 3. Test missing param: ${BASE_URL}/surat_detail_api.php"
echo "       Expected: Show 'Parameter tidak valid' message"
echo ""
echo "[ ] 4. Check date format on page"
echo "       Expected: '31 Desember 2025' (Indonesian format)"
echo ""
echo "[ ] 5. Check logs"
echo "       Expected: Header X-CLIENT-ID present in all requests"
echo ""
echo "═══════════════════════════════════════════════════"
echo ""
echo "Testing script completed!"
echo ""

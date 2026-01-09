#!/bin/bash
# deploy_to_cpanel.sh
# Deploy API Compliance Updates to cPanel Production Server
# 
# CONFIGURATION REQUIRED:
# Edit the variables below before running

set -e

# ============================================
# CONFIGURATION - EDIT THESE VALUES
# ============================================

# cPanel/Server Details
CPANEL_HOST="suratqu.sidiksae.my.id"
CPANEL_USER="your_cpanel_username"
CPANEL_PASSWORD="your_cpanel_password"  # Optional, will prompt if empty
CPANEL_PORT="21"  # FTP port (usually 21)

# Target directory di server (biasanya public_html atau htdocs)
REMOTE_DIR="/public_html"

# Files to deploy
FILES=(
    "includes/sidiksae_api_client.php"
    "includes/functions.php"
    "surat_detail_api.php"
    "test_api_compliance.php"
)

# ============================================
# SCRIPT START - DO NOT EDIT BELOW
# ============================================

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
TEMP_DIR="deploy_temp_${TIMESTAMP}"

echo "=================================================="
echo "  DEPLOY TO CPANEL: API Compliance"
echo "=================================================="
echo ""
echo "Target: ${CPANEL_HOST}"
echo "User: ${CPANEL_USER}"
echo "Remote Dir: ${REMOTE_DIR}"
echo ""

# Check if lftp is installed (for FTP deployment)
if command -v lftp &> /dev/null; then
    METHOD="FTP"
    echo "Method: FTP (using lftp)"
elif command -v scp &> /dev/null; then
    METHOD="SCP"
    echo "Method: SCP (using scp)"
else
    echo "Error: Neither lftp nor scp found!"
    echo "Install one of them:"
    echo "  - FTP: sudo apt-get install lftp"
    echo "  - SSH: scp should be available by default"
    exit 1
fi

echo ""

# Prompt for password if not set
if [ -z "$CPANEL_PASSWORD" ]; then
    read -sp "Enter cPanel password: " CPANEL_PASSWORD
    echo ""
fi

# Create temp directory with files to upload
echo "[1/4] Preparing files..."
mkdir -p "${TEMP_DIR}"

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        # Create directory structure
        mkdir -p "${TEMP_DIR}/$(dirname $file)"
        cp "$file" "${TEMP_DIR}/${file}"
        echo "  ✓ Prepared: $file"
    else
        echo "  ✗ Missing: $file"
        exit 1
    fi
done

echo ""

# Upload based on method
if [ "$METHOD" == "FTP" ]; then
    echo "[2/4] Uploading via FTP..."
    
    # Create lftp script
    cat > /tmp/lftp_script_${TIMESTAMP}.txt << EOF
open -u ${CPANEL_USER},${CPANEL_PASSWORD} ${CPANEL_HOST}
set ftp:ssl-allow no
cd ${REMOTE_DIR}

# Upload files
$(for file in "${FILES[@]}"; do
    echo "put -O $(dirname $file) ${TEMP_DIR}/${file}"
done)

bye
EOF

    # Execute lftp
    lftp -f /tmp/lftp_script_${TIMESTAMP}.txt
    
    # Cleanup
    rm -f /tmp/lftp_script_${TIMESTAMP}.txt
    
    echo "  ✓ Upload complete"

elif [ "$METHOD" == "SCP" ]; then
    echo "[2/4] Uploading via SCP..."
    
    for file in "${FILES[@]}"; do
        scp "${TEMP_DIR}/${file}" "${CPANEL_USER}@${CPANEL_HOST}:${REMOTE_DIR}/${file}"
        echo "  ✓ Uploaded: $file"
    done
fi

echo ""

# Cleanup temp directory
echo "[3/4] Cleaning up..."
rm -rf "${TEMP_DIR}"
echo "  ✓ Temporary files removed"
echo ""

# Verification
echo "[4/4] Verification Steps"
echo ""
echo "Please verify the deployment manually:"
echo ""
echo "1. Test authentication:"
echo "   curl -X POST https://${CPANEL_HOST}/test_api_compliance.php"
echo ""
echo "2. Test detail page (replace ID):"
echo "   https://${CPANEL_HOST}/surat_detail_api.php?id_surat=15"
echo ""
echo "3. Check error handling:"
echo "   https://${CPANEL_HOST}/surat_detail_api.php?id_surat=99999"
echo ""

echo "=================================================="
echo "  DEPLOYMENT SUMMARY"
echo "=================================================="
echo ""
echo "✅ Status: Uploaded to production"
echo ""
echo "Files deployed:"
for file in "${FILES[@]}"; do
    echo "  • $file"
done
echo ""
echo "⚠️  Important:"
echo "  1. Backup sudah dibuat di local: backup_compliance_*/"
echo "  2. Test semua fitur di production"
echo "  3. Monitor logs: tail -f storage/api_requests.log"
echo ""
echo "✅ Deployment to cPanel completed!"
echo "=================================================="

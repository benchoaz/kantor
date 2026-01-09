#!/bin/bash

# Deployment Package Creator - Disposition Notification System
# Date: 2026-01-06

PACKAGE_NAME="camat_disposition_notification_$(date +%Y%m%d_%H%M%S).tar.gz"

echo "================================================"
echo "Creating Disposition Notification Update Package"
echo "================================================"

# Remove old package if exists
rm -f camat_disposition_notification_*.tar.gz

# List of modified/new files
FILES=(
    "disposisi.php"
    "laporan-disposisi.php"
    "includes/navigation.php"
    "IMPLEMENTATION_API_BACKEND.md"
    "IMPLEMENTATION_DOCKU.md"
    "QUICK_START.md"
    "DEPLOYMENT_DISPOSITION.md"
)

echo ""
echo "Files to be packaged:"
for file in "${FILES[@]}"; do
    if [ -e "$file" ]; then
        echo "  ✓ $file"
    else
        echo "  ✗ $file (NOT FOUND)"
    fi
done

echo ""
echo "Creating archive..."

# Create the tar.gz package
tar -czvf "$PACKAGE_NAME" "${FILES[@]}" 2>/dev/null

if [ $? -eq 0 ]; then
    echo ""
    echo "================================================"
    echo "✓ Package Created Successfully!"
    echo "================================================"
    echo "File: $PACKAGE_NAME"
    ls -lh "$PACKAGE_NAME"
    echo ""
    echo "Upload to cPanel and extract with:"
    echo "  tar -xzvf $PACKAGE_NAME"
    echo ""
else
    echo ""
    echo "✗ Error creating package"
    exit 1
fi

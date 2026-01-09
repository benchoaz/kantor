#!/bin/bash

# Define package name
PACKAGE_NAME="camat_premium_ui_update_$(date +%Y%m%d_%H%M%S).tar.gz"

# List of files to include
FILES="login.php dashboard.php surat-masuk.php monitoring.php disposisi.php persetujuan-laporan.php assets/css/layout.css DEPLOYMENT_README.md"

# Create the archive
echo "Packging files..."
tar -czvf "$PACKAGE_NAME" $FILES

echo "-----------------------------------"
echo "Deployment package created: $PACKAGE_NAME"
echo "Ready to upload to server."

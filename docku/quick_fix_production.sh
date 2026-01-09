#!/bin/bash
# Quick Fix Script - Replace integration_helper.php directly on production

echo "=== QUICK FIX: Replacing integration_helper.php ==="
echo ""

# Backup existing file
if [ -f "includes/integration_helper.php" ]; then
    cp includes/integration_helper.php includes/integration_helper.php.backup.$(date +%Y%m%d_%H%M%S)
    echo "✅ Backup created"
else
    echo "❌ File not found: includes/integration_helper.php"
    exit 1
fi

# Download the correct file from local
echo "Copying correct file..."

# You can either:
# 1. If running on same server, copy directly:
# cp /home/beni/projectku/Docku/includes/integration_helper.php includes/integration_helper.php

# 2. Or paste the fixed content directly (see below)

echo ""
echo "Please choose how to update:"
echo "1. Copy from /home/beni/projectku/Docku/includes/integration_helper.php"
echo "2. Manually paste content (for different servers)"
echo ""
read -p "Enter choice (1 or 2): " choice

if [ "$choice" = "1" ]; then
    if [ -f "/home/beni/projectku/Docku/includes/integration_helper.php" ]; then
        cp /home/beni/projectku/Docku/includes/integration_helper.php includes/integration_helper.php
        echo "✅ File copied successfully"
    else
        echo "❌ Source file not found"
        exit 1
    fi
elif [ "$choice" = "2" ]; then
    echo "Please upload the correct file manually via cPanel File Manager"
    echo "or use SCP/FTP to upload from local machine"
fi

# Set permissions
chmod 644 includes/integration_helper.php
echo "✅ Permissions set"

# Verify
echo ""
echo "=== VERIFICATION ==="
if grep -q "SELECT id, username, nama, jabatan, role" includes/integration_helper.php; then
    echo "✅ Username field found in query"
else
    echo "❌ Username field MISSING - File still incorrect!"
    exit 1
fi

if grep -q "WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')" includes/integration_helper.php; then
    echo "✅ Role filter found"
else
    echo "❌ Role filter MISSING"
    exit 1
fi

echo ""
echo "=== SUCCESS ==="
echo "File has been updated correctly!"
echo ""
echo "Next steps:"
echo "1. Clear PHP cache (restart PHP-FPM if possible)"
echo "2. Test sync again from Docku UI"

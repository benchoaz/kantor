#!/bin/bash
# deploy_c3_production.sh
# Script Helper untuk Deploy Step C3 di Production (cPanel)

echo "🚀 DEPLOY STEP C3 - PRODUCTION HELPER"
echo "======================================"

# 1. Pastikan berada di folder public_html/api
TARGET_DIR="/home/sidiksae/public_html/api"
if [ ! -d "$TARGET_DIR" ]; then
    echo "❌ Error: Direktori $TARGET_DIR tidak ditemukan."
    echo "   Pastikan Anda menjalankan script ini di server production."
    exit 1
fi

cd "$TARGET_DIR"
echo "📂 Current Directory: $(pwd)"

# 2. Extract Hotfix
if [ -f "step_c3_hotfix.tar.gz" ]; then
    echo "📦 Extracting Hotfix..."
    tar -xzf step_c3_hotfix.tar.gz
    echo "✅ Hotfix extracted."
else
    echo "⚠️ Warning: step_c3_hotfix.tar.gz tidak ditemukan. Upload dulu!"
    exit 1
fi

# 3. Run Migration (PHP)
echo "--------------------------------------"
echo "🛠️ Running Migration (Update Table Surat)..."
php migrate_c1.php

# 4. Run Auth Setup
echo "--------------------------------------"
echo "🔑 Setup API Key..."
php insert_apikey.php

echo "--------------------------------------"
echo "🎉 DEPLOY C3 SELESAI!"
echo "   Sekarang coba login sebagai Camat di SuratQu dan cek surat masuk."

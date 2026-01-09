#!/bin/bash

# FILE: deploy.sh
# Script untuk mempaketkan aplikasi Camat Stateless

APP_NAME="camat_stateless_v1"
OUTPUT_FILE="${APP_NAME}.tar.gz"

echo "=== MEMULAI PROSES DEPLOYMENT ==="
echo "Target: $OUTPUT_FILE"

# 1. Pastikan kita di root project
# (Asumsi dijalankan dari /home/beni/projectku/camat)

# 2. Hapus file sampah / temp / git
echo "[1/4] Membersihkan file sementera..."
rm -rf .git
rm -f *.sql
rm -f *.tar.gz
rm -f test-*.php
rm -f debug-*.php

# 3. Create Archive (Exclude dev useful files if needed, but keep simple)
echo "[2/4] Membuat arsip tar.gz..."
tar -czf "$OUTPUT_FILE" \
    --exclude='deploy.sh' \
    --exclude='.git' \
    --exclude='*.md' \
    --exclude='info.php' \
    config/ \
    helpers/ \
    modules/ \
    includes/ \
    assets/ \
    *.php \
    .htaccess

# 4. Verifikasi
if [ -f "$OUTPUT_FILE" ]; then
    SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    echo "[3/4] BERHASIL! File deployment siap."
    echo "      Nama File: $OUTPUT_FILE"
    echo "      Ukuran:    $SIZE"
    echo ""
    echo "[4/4] INSTRUKSI UPLOAD:"
    echo "      1. Upload $OUTPUT_FILE ke public_html di hosting (camat.sidiksae.my.id)"
    echo "      2. Extract file"
    echo "      3. Pastikan PHP versi 7.4 atau 8.x"
    echo "      4. Selesai (Tidak ada database yang perlu diimport)"
else
    echo "[ERROR] Gagal membuat file arsip."
    exit 1
fi

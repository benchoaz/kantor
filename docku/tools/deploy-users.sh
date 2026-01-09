#!/bin/bash
# Deploy Script untuk Update User & Password
# Usage: bash tools/deploy-users.sh

echo "🚀 BESUK SAE User Management Deployment"
echo "========================================"
echo ""

# Set tanggal untuk nama file
DATE=$(date +%Y%m%d_%H%M%S)
OUTPUT_FILE="deploy-users-${DATE}.tar.gz"

# Warna untuk output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}📦 Membuat archive deployment...${NC}"

# Buat tar.gz
tar -czf "$OUTPUT_FILE" \
  profil.php \
  kegiatan.php \
  kegiatan_tambah.php \
  laporan_rekap.php \
  tools/seed_users.php \
  DEPLOYMENT_USERS_README.txt

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Archive berhasil dibuat: $OUTPUT_FILE${NC}"
    echo ""
    
    # Tampilkan ukuran
    SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    echo "📊 Ukuran file: $SIZE"
    echo ""
    echo "📋 Isi Update:"
    echo "  1. profil.php (Fitur Ganti Password Aman)"
    echo "  2. tools/seed_users.php (Pembuat User Otomatis)"
    echo "  3. DEPLOYMENT_USERS_README.txt (Panduan)"
    echo ""
    
    echo "📤 Langkah Deploy:"
    echo "  1. Upload $OUTPUT_FILE ke cPanel (root folder/public_html)"
    echo "  2. Extract File"
    echo "  3. Buka browser: https://domain-anda.com/tools/seed_users.php"
    echo "  4. Hapus file tools/seed_users.php setelah selesai"
    echo ""
    
    echo -e "${GREEN}✨ Siap didownload!${NC}"
else
    echo "❌ Gagal membuat archive."
    exit 1
fi

#!/bin/bash
# Deploy Script untuk Integrasi SuratQu
# Usage: bash tools/deploy-integrasi.sh

echo "🚀 BESUK SAE Integration Deployment Script"
echo "========================================"
echo ""

# Set tanggal untuk nama file
DATE=$(date +%Y%m%d_%H%M%S)
OUTPUT_FILE="integrasi-update-${DATE}.tar.gz"

# Warna untuk output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}📦 Membuat archive file integrasi...${NC}"

# Buat tar.gz dengan file integrasi
tar -czf "$OUTPUT_FILE" \
  api/v1/disposisi/receive.php \
  includes/api_auth.php \
  includes/integration_helper.php \
  modules/integrasi/settings.php \
  modules/integrasi/tutorial.php \
  modules/integrasi/test_connection.php \
  tools/update_db.php \
  migrations/008_add_integration_module.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Archive berhasil dibuat: $OUTPUT_FILE${NC}"
    echo ""
    
    # Tampilkan ukuran file
    SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    echo "📊 Ukuran file: $SIZE"
    
    echo ""
    echo "📋 File yang di-archive:"
    echo "  1. api/v1/disposisi/receive.php (API endpoint)"
    echo "  2. includes/api_auth.php (Authentication)"
    echo "  3. modules/integrasi/settings.php (Settings UI)"
    echo "  4. modules/integrasi/tutorial.php (Documentation)"
    echo "  5. modules/integrasi/test_connection.php (Test feature)"
    echo "  6. migrations/008_add_integration_module.sql (DB migration)"
    echo ""
    
    echo "📤 Langkah Upload ke cPanel:"
    echo "  1. Login ke cPanel File Manager"
    echo "  2. Upload file: $OUTPUT_FILE"
    echo "  3. Extract di folder aplikasi (klik kanan > Extract)"
    echo "  4. Jalankan migration di phpMyAdmin (jika belum)"
    echo "  5. Akses: https://sidiksae.my.id/modules/integrasi/settings.php"
    echo ""
    
    echo "🧪 Test setelah upload:"
    echo "  curl -X GET https://sidiksae.my.id/api/v1/disposisi/receive.php -i"
    echo ""
    
    echo -e "${GREEN}✨ Deploy package siap!${NC}"
else
    echo "❌ Error: Gagal membuat archive"
    exit 1
fi

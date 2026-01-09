#!/bin/bash
# migrate_sidiksae.sh
# Script untuk apply database migration untuk integrasi SidikSae

echo "==================================="
echo "SidikSae Integration Migration"
echo "==================================="
echo ""

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ PHP tidak ditemukan. Install PHP terlebih dahulu."
    exit 1
fi

echo "📋 Applying database migration..."

# Run PHP script to apply migration
php -r "
require_once 'config/database.php';

\$sql = file_get_contents('database/integrasi_sistem.sql');

try {
    \$db->exec(\$sql);
    echo \"✅ Migration berhasil diaplikasikan!\n\";
    echo \"\n\";
    echo \"Perubahan:\n\";
    echo \"- Menambahkan kolom 'payload' di tabel integrasi_docku_log\n\";
    echo \"- Menambahkan kolom 'created_at' dan 'updated_at'\n\";
    echo \"- Menambahkan index untuk optimasi performa\n\";
} catch (PDOException \$e) {
    if (strpos(\$e->getMessage(), 'Duplicate column') !== false) {
        echo \"ℹ️  Migration sudah pernah diaplikasikan sebelumnya.\n\";
    } else {
        echo \"❌ Error: \" . \$e->getMessage() . \"\n\";
        exit(1);
    }
}
"

echo ""
echo "✅ Selesai!"

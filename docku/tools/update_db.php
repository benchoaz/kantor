<?php
/**
 * update_db.php - DATABASE MIGRATION SCRIPT
 * Menggunakan koneksi dari config/database.php agar tidak terjadi error password.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Database Migration & Fix</h2>";
echo "<pre>";

try {
    // 1. Coba ambil koneksi dari file utama
    if (file_exists('config/database.php')) {
        echo "Menggunakan konfigurasi dari config/database.php...\n";
        require_once 'config/database.php';
    } elseif (file_exists('../config/database.php')) {
        echo "Menggunakan konfigurasi dari ../config/database.php...\n";
        require_once '../config/database.php';
    } else {
        throw new Exception("File config/database.php tidak ditemukan di server.\n\n" . 
                            "SOLUSI:\n" .
                            "1. Rename file 'config/database.php.sample' menjadi 'config/database.php'\n" .
                            "2. Masukkan username/password database Anda di file tersebut.\n" .
                            "3. Upload ke folder 'config/' di cPanel.");
    }

    if (!isset($pdo)) {
        throw new Exception("Variabel \$pdo tidak ditemukan di config/database.php.");
    }

    echo "✅ Koneksi Berhasil.\n\n";

    // 1. Cek kolom file_hash
    $check = $pdo->query("SHOW COLUMNS FROM foto_kegiatan LIKE 'file_hash'");
    $exists = $check->fetch();

    if (!$exists) {
        echo "Menambahkan kolom 'file_hash' ke tabel 'foto_kegiatan'...\n";
        $pdo->exec("ALTER TABLE foto_kegiatan ADD COLUMN file_hash VARCHAR(32) NULL AFTER file");
        echo "✅ Kolom 'file_hash' berhasil ditambahkan.\n";
    } else {
        echo "ℹ️ Kolom 'file_hash' sudah ada.\n";
    }

    // 2. Cek Tabel Pengaturan (Kop Surat)
    echo "\nMemeriksa tabel 'pengaturan'...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS pengaturan (
        id INT PRIMARY KEY,
        nama_instansi_1 VARCHAR(100) DEFAULT 'PEMERINTAH KABUPATEN PROBOLINGGO',
        nama_instansi_2 VARCHAR(100) DEFAULT 'KECAMATAN BESUK',
        alamat_1 VARCHAR(255) DEFAULT 'Jalan Raya Besuk No. 1, Besuk, Probolinggo',
        alamat_2 VARCHAR(255) DEFAULT 'Email: kecamatan.besuk@probolinggokab.go.id',
        nama_camat VARCHAR(100) DEFAULT 'PUJA KURNIAWAN, S.STP., M.Si',
        nip_camat VARCHAR(50) DEFAULT '19800101 200001 1 001',
        jabatan_ttd VARCHAR(100) DEFAULT 'Camat Besuk',
        golongan_ttd VARCHAR(100) DEFAULT 'Pembina Tingkat I (IV/b)',
        logo VARCHAR(255) NULL
    )");

    // Cek kolom logo (jika tabel sudah ada tapi belum ada kolom logo)
    $checkLogo = $pdo->query("SHOW COLUMNS FROM pengaturan LIKE 'logo'");
    if (!$checkLogo->fetch()) {
        echo "Menambahkan kolom 'logo' ke tabel 'pengaturan'...\n";
        $pdo->exec("ALTER TABLE pengaturan ADD COLUMN logo VARCHAR(255) NULL AFTER nip_camat");
        echo "✅ Kolom 'logo' berhasil ditambahkan.\n";
    }

    // Cek kolom jabatan_ttd
    $checkJabatan = $pdo->query("SHOW COLUMNS FROM pengaturan LIKE 'jabatan_ttd'");
    if (!$checkJabatan->fetch()) {
        echo "Menambahkan kolom 'jabatan_ttd' ke tabel 'pengaturan'...\n";
        $pdo->exec("ALTER TABLE pengaturan ADD COLUMN jabatan_ttd VARCHAR(100) DEFAULT 'Camat Besuk' AFTER nip_camat");
        echo "✅ Kolom 'jabatan_ttd' berhasil ditambahkan.\n";
    }

    // Cek kolom golongan_ttd
    $checkGolongan = $pdo->query("SHOW COLUMNS FROM pengaturan LIKE 'golongan_ttd'");
    if (!$checkGolongan->fetch()) {
        echo "Menambahkan kolom 'golongan_ttd' ke tabel 'pengaturan'...\n";
        $pdo->exec("ALTER TABLE pengaturan ADD COLUMN golongan_ttd VARCHAR(100) DEFAULT 'Pembina Tingkat I (IV/b)' AFTER jabatan_ttd");
        echo "✅ Kolom 'golongan_ttd' berhasil ditambahkan.\n";
    }
    
    // Insert default content if empty
    $checkSettings = $pdo->query("SELECT COUNT(*) FROM pengaturan");
    if ($checkSettings->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO pengaturan (id) VALUES (1)");
        echo "✅ Tabel 'pengaturan' dibuat dan diisi data default.\n";
    } else {
        echo "ℹ️ Tabel 'pengaturan' sudah siap.\n";
    }

    // 3. Migrasi Tipe Kegiatan & Kategori di tabel kegiatan
    echo "\nMemeriksa kolom 'tipe_kegiatan' dan 'kategori'...\n";
    $pdo->exec("ALTER TABLE kegiatan MODIFY COLUMN tipe_kegiatan VARCHAR(100) DEFAULT 'biasa'");
    $pdo->exec("ALTER TABLE kegiatan MODIFY COLUMN kategori VARCHAR(100) DEFAULT NULL");
    
    // Tambah kolom Monev jika belum ada
    $cols = $pdo->query("SHOW COLUMNS FROM kegiatan")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('temuan', $cols)) {
        $pdo->exec("ALTER TABLE kegiatan ADD COLUMN temuan TEXT DEFAULT NULL AFTER deskripsi");
        echo "✅ Kolom 'temuan' berhasil ditambahkan.\n";
    }
    if (!in_array('saran_rekomendasi', $cols)) {
        $pdo->exec("ALTER TABLE kegiatan ADD COLUMN saran_rekomendasi TEXT DEFAULT NULL AFTER temuan");
        echo "✅ Kolom 'saran_rekomendasi' berhasil ditambahkan.\n";
    }
    if (!in_array('capaian', $cols)) {
        $pdo->exec("ALTER TABLE kegiatan ADD COLUMN capaian INT DEFAULT 0 AFTER saran_rekomendasi");
        echo "✅ Kolom 'capaian' berhasil ditambahkan.\n";
    }
    if (!in_array('join_code', $cols)) {
        $pdo->exec("ALTER TABLE kegiatan ADD COLUMN join_code VARCHAR(10) DEFAULT NULL UNIQUE AFTER created_by");
        echo "✅ Kolom 'join_code' berhasil ditambahkan.\n";
    }

    // 5. Update tabel integrasi_config untuk SidikSae Centralized API
    echo "\nMemeriksa tabel 'integrasi_config'...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS integrasi_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(100) NOT NULL,
        inbound_key VARCHAR(100),
        outbound_url VARCHAR(255),
        outbound_key VARCHAR(255),
        is_active TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $cols_integrasi = $pdo->query("SHOW COLUMNS FROM integrasi_config")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('client_secret', $cols_integrasi)) {
        $pdo->exec("ALTER TABLE integrasi_config ADD COLUMN client_secret VARCHAR(255) DEFAULT NULL AFTER outbound_key");
        echo "✅ Kolom 'client_secret' berhasil ditambahkan.\n";
    }
    if (!in_array('app_url', $cols_integrasi)) {
        $pdo->exec("ALTER TABLE integrasi_config ADD COLUMN app_url VARCHAR(255) DEFAULT NULL AFTER client_secret");
        echo "✅ Kolom 'app_url' berhasil ditambahkan.\n";
    }
    if (!in_array('timeout', $cols_integrasi)) {
        $pdo->exec("ALTER TABLE integrasi_config ADD COLUMN timeout INT DEFAULT 10 AFTER app_url");
        echo "✅ Kolom 'timeout' berhasil ditambahkan.\n";
    }

    // Pastikan ada satu baris default untuk SidikSae jika kosong
    $checkSidikSae = $pdo->prepare("SELECT COUNT(*) FROM integrasi_config WHERE label = 'SidikSae'");
    $checkSidikSae->execute();
    if ($checkSidikSae->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO integrasi_config (label, inbound_key, outbound_url, outbound_key, client_secret, is_active) 
                   VALUES ('SidikSae', 'SIDIKSAE-OUT', 'https://api.sidiksae.my.id/api/v1/', '', '', 1)");
        echo "✅ Baris default 'SidikSae' berhasil ditambahkan.\n";
    }

    echo "✅ Struktur database telah diperbarui.\n";

    echo "\n🎉 SELESAI. Semua perubahan berhasil diterapkan.";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
    echo "\n\nSaran: Pastikan file config/database.php di server sudah benar passwordnya.";
}

echo "</pre>";
echo "<br><a href='index.php'>Ke Dashboard</a>";
?>

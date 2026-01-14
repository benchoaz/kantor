<?php
// docku/init_tables.php
// Database Initialization for Disposition Sync Mirror Tables

require_once 'config/database.php';
header('Content-Type: text/plain');

echo "=== 🏗️ DOCKU DATABASE INITIALIZATION ===\n\n";

if (!isset($pdo)) die("No DB Connection.");

$queries = [
    // 1. SURAT TABLE
    "CREATE TABLE IF NOT EXISTS `surat` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `uuid` varchar(36) NOT NULL,
      `nomor_surat` varchar(100) DEFAULT NULL,
      `perihal` text,
      `asal_surat` varchar(200) DEFAULT NULL,
      `tanggal_surat` date DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uuid` (`uuid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 2. DISPOSISI TABLE
    "CREATE TABLE IF NOT EXISTS `disposisi` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `uuid` varchar(36) NOT NULL,
      `uuid_surat` varchar(36) NOT NULL,
      `instruksi` text,
      `tgl_disposisi` datetime DEFAULT NULL,
      `status_global` varchar(50) DEFAULT 'BARU',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uuid` (`uuid`),
      KEY `uuid_surat` (`uuid_surat`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 3. DISPOSISI_PENERIMA TABLE
    "CREATE TABLE IF NOT EXISTS `disposisi_penerima` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `disposisi_id` int(11) NOT NULL,
      `disposisi_uuid` varchar(36) NOT NULL,
      `user_id` int(11) NOT NULL,
      `status` varchar(50) DEFAULT 'baru',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `disposisi_id` (`disposisi_id`),
      KEY `user_id` (`user_id`),
      KEY `disposisi_uuid` (`disposisi_uuid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ Success executing: " . substr($sql, 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Special check for 'disposisi' schema update if it already existed
try {
    // Check if 'uuid' column exists in 'disposisi'
    $stmt = $pdo->query("SHOW COLUMNS FROM `disposisi` LIKE 'uuid'");
    if ($stmt->rowCount() === 0) {
        echo "⚠️ Adding missing columns to 'disposisi'...\n";
        $pdo->exec("ALTER TABLE `disposisi` ADD COLUMN `uuid` varchar(36) NOT NULL AFTER `id`, ADD UNIQUE KEY (`uuid`) ");
        $pdo->exec("ALTER TABLE `disposisi` ADD COLUMN `uuid_surat` varchar(36) NOT NULL AFTER `uuid` ");
        $pdo->exec("ALTER TABLE `disposisi` ADD COLUMN `status_global` varchar(50) DEFAULT 'BARU' AFTER `tgl_disposisi` ");
        echo "✅ 'disposisi' table updated.\n";
    }
    
    // Check if 'disposisi_uuid' exists in 'disposisi_penerima'
    $stmt = $pdo->query("SHOW COLUMNS FROM `disposisi_penerima` LIKE 'disposisi_uuid'");
    if ($stmt->rowCount() === 0) {
        echo "⚠️ Adding missing columns to 'disposisi_penerima'...\n";
        $pdo->exec("ALTER TABLE `disposisi_penerima` ADD COLUMN `disposisi_uuid` varchar(36) NOT NULL AFTER `disposisi_id` ");
        echo "✅ 'disposisi_penerima' table updated.\n";
    }
} catch (Exception $e) {
    echo "ℹ️ Schema check: " . $e->getMessage() . "\n";
}

echo "\n🚀 Initialization Complete. You can now run the Sync script.";

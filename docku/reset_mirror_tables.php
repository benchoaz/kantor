<?php
// docku/reset_mirror_tables.php
// Reset Mirror Tables for a clean Fresh Start
// Enhanced with Foreign Key Check Disabling

require_once 'config/database.php';
header('Content-Type: text/plain');

echo "=== 🔄 DOCKU MIRROR TABLE RESET ===\n\n";

if (!isset($pdo)) die("No DB Connection.");

// Disable FK checks to allow dropping tables with constraints
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

$tables = ['disposisi_penerima', 'disposisi', 'surat'];

foreach ($tables as $t) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS `$t` ");
        echo "🗑️ Dropped table: $t\n";
    } catch (Exception $e) {
        echo "❌ Error dropping $t: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Re-creating tables ---\n";

$queries = [
    "CREATE TABLE `surat` (
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

    "CREATE TABLE `disposisi` (
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

    "CREATE TABLE `disposisi_penerima` (
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
        echo "✅ Created: " . substr($sql, 13, 30) . "...\n";
    } catch (Exception $e) {
        echo "❌ Error creating: " . $e->getMessage() . "\n";
    }
}

// Re-enable FK checks
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo "\n✨ Database is now CLEAN and READY for Sync.";

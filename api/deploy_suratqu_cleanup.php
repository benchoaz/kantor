<?php
// deploy_suratqu_cleanup.php
// Deployment Script for SuratQu Governance Update (Role Restrictions)
// Run this at: https://api.sidiksae.my.id/deploy_suratqu_cleanup.php

header('Content-Type: text/plain');

$packageFile = 'suratqu_role_cleanup.tar.gz';
$targetDir = '../suratqu'; // Relative path from api/ to suratqu/

echo "🛡️ STARTING SURATQU GOVERNANCE UPDATE...\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "---------------------------------------------------\n";

// 1. Check Package
if (!file_exists($packageFile)) {
    die("❌ GAGAL: File package '$packageFile' tidak ditemukan.\n");
}
echo "✅ Package found: $packageFile (" . number_format(filesize($packageFile)/1024, 2) . " KB)\n";

// 2. Determine Target Directory
if (!is_dir($targetDir)) {
    $targetDir = '../suratqu.sidiksae.my.id';
    if (!is_dir($targetDir)) {
        $targetDir = 'temp_suratqu_extract';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        echo "⚠️ WARNING: Target directory not found. Extracting to '$targetDir'.\n";
    }
}
echo "✅ Target Directory: $targetDir\n";

// 3. Extract Package
echo "📦 Extracting files...\n";
$phar = new PharData($packageFile);
try {
    $phar->extractTo($targetDir, null, true);
    echo "✅ Extraction success!\n";
} catch (Exception $e) {
    die("❌ GAGAL Extract: " . $e->getMessage() . "\n");
}

echo "---------------------------------------------------\n";
echo "🎉 UPDATE FINISHED!\n";
echo "SuratQu sekarang dibatasi hanya untuk Admin, Operator, dan Staf.\n";
echo "Fitur Disposisi di SuratQu telah dinonaktifkan (dipindah ke App Camat).\n";
?>

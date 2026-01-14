<?php
// deploy_c4_camat.php
// Deployment Script for Step C4 (Camat App Disposisi Feature)
// Run this at: https://api.sidiksae.my.id/deploy_c4_camat.php

header('Content-Type: text/plain');

$packageFile = 'step_c4_camat_disposisi.tar.gz';
$targetDir = '../camat'; // Relative path from api/ to camat/ (Sibling directories)

echo "🚀 STARTING CAMAT APP DEPLOYMENT (STEP C4)...\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "---------------------------------------------------\n";

// 1. Check Package
if (!file_exists($packageFile)) {
    die("❌ GAGAL: File package '$packageFile' tidak ditemukan.\n   Silakan upload file tersebut ke folder 'api/' terlebih dahulu.\n");
}
echo "✅ Package found: $packageFile (" . number_format(filesize($packageFile)/1024, 2) . " KB)\n";

// 2. Determine Target Directory
// We assume api/ and camat/ are siblings in public_html or root.
// Check if ../camat exists
if (!is_dir($targetDir)) {
    // Try absolute path casing or alternative
    $targetDir = '../camat.sidiksae.my.id'; // Common cPanel subdomain pattern
    if (!is_dir($targetDir)) {
        $targetDir = '../camat'; // Reset
        echo "⚠️ WARNING: Target directory '../camat' not found.\n";
        echo "   Mencoba mengekstrak ke folder sementara 'temp_camat_extract/'...\n";
        $targetDir = 'temp_camat_extract';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    }
}
echo "✅ Target Directory: $targetDir\n";

// 3. Extract Package
echo "📦 Extracting files...\n";
$phar = new PharData($packageFile);
try {
    // Extract to target
    // Note: The tar was created with relative paths like 'surat-detail.php' inside.
    $phar->extractTo($targetDir, null, true); // Overwrite existing
    echo "✅ Extraction success!\n";
} catch (Exception $e) {
    die("❌ GAGAL Extract: " . $e->getMessage() . "\n");
}

// 4. Verify Files
$criticalFiles = [
    'surat-detail.php',
    'includes/modal_disposisi.php',
    'disposisi_kirim.php'
];

echo "\n🔍 Verifying Files in $targetDir:\n";
foreach ($criticalFiles as $file) {
    if (file_exists("$targetDir/$file")) {
        echo "   - $file: OK\n";
    } else {
        echo "   - $file: MISSING! ❌\n";
    }
}

echo "---------------------------------------------------\n";
echo "🎉 DEPLOYMENT FINISHED!\n";
echo "Sekarang fitur Disposisi dan Instruksi Langsung sudah aktif di Aplikasi Camat.\n";
echo "Silakan akses: https://camat.sidiksae.my.id/surat-masuk.php\n";
?>

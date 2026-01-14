<?php
// deploy_c4_web.php
// Web-Based Deployer for Step C4 (Disposisi Flow)

header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "🚀 WEB DEPLOYMENT STEP C4 (DISPOSISI FLOW)\n";
echo "=========================================\n\n";

$baseDir = __DIR__; // public_html/api
$tarFile = $baseDir . '/step_c4_disposisi_flow.tar.gz';

// 1. EXTRACT PACKAGE
echo "[1] Extracting Package...\n";
if (file_exists($tarFile)) {
    try {
        $phar = new PharData($tarFile);
        $phar->extractTo($baseDir, null, true); // Extract here
        echo "✅ Extraction Success!\n";
    } catch (Exception $e) {
        echo "❌ Extraction Failed: " . $e->getMessage() . "\n";
        echo "⚠️ Try extracting manually via cPanel File Manager.\n";
    }
} else {
    echo "❌ File not found: step_c4_disposisi_flow.tar.gz\n";
    exit;
}

// 2. MOVE FILES
echo "\n[2] Moving Files...\n";

// A. Move SuratQu files (extracted to public_html/api/suratqu -> public_html/suratqu)
$sourceSuratQu = $baseDir . '/suratqu';
$targetSuratQu = realpath($baseDir . '/../suratqu');

if ($targetSuratQu && is_dir($sourceSuratQu)) {
    echo "   Moving SuratQu files to: $targetSuratQu\n";
    
    // Recursive Copy/Move Function
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceSuratQu, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        $subPath = $iterator->getSubPathName();
        $targetPath = $targetSuratQu . DIRECTORY_SEPARATOR . $subPath;
        
        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath);
            }
        } else {
            copy($item, $targetPath);
            echo "   -> Copied: $subPath\n";
        }
    }
    echo "✅ SuratQu files updated.\n";
} else {
    echo "⚠️ Target SuratQu folder not found or Source empty.\n";
}

// B. Move API files (extracted to public_html/api/api -> public_html/api)
// Because tarball was created from root, it contains 'api/...'
$sourceApi = $baseDir . '/api';
if (is_dir($sourceApi)) {
    echo "   Updating API files...\n";
    // Move contents of api/api/* to api/* (current dir)
    // Actually, since we extracted in $baseDir (api), the folder 'api' exists there.
    // We need to move files from $baseDir/api/* to $baseDir/*
    
    $iteratorApi = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceApi, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iteratorApi as $item) {
        $subPath = $iteratorApi->getSubPathName();
        $targetPath = $baseDir . DIRECTORY_SEPARATOR . $subPath;
        
        if ($item->isDir()) {
             if (!is_dir($targetPath)) mkdir($targetPath);
        } else {
             // Avoid overwriting deploy_c4_web.php itself if it were in tar
             if (basename($item) !== 'deploy_c4_web.php') {
                 copy($item, $targetPath);
                 echo "   -> Updated API file: $subPath\n";
             }
        }
    }
    echo "✅ API files locally updated.\n";
}

// 3. RUN DATABASE MIGRATION
echo "\n[3] Running Database Migration...\n";
$migrateScript = $baseDir . '/migrate_c4.php';

if (file_exists($migrateScript)) {
    include $migrateScript;
} else {
    echo "❌ Migration script not found: migrate_c4.php\n";
    // Fallback manual execution based on known include
    try {
        require_once 'config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $sqlPath = $baseDir . '/migrations/step_c4_disposisi_schema.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $conn->exec($sql);
            echo "✅ Manual Migration Success (Fallback)\n";
        }
    } catch (Exception $e) {
        echo "❌ Fallback Migration Failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=========================================\n";
echo "🎉 DEPLOYMENT STEP C4 FINISHED!\n";
echo "   Sekarang Camat bisa melakukan disposisi dari Surat Masuk Detail.\n";
?>

<?php
// restore_prod_config.php

echo "Restoring Production Configuration...\n";

// Target Files
$files = [
    'camat' => __DIR__ . '/camat/config/config.php',
    'suratqu' => __DIR__ . '/suratqu/config/integration.php',
    'docku_sync' => __DIR__ . '/docku/scripts/sync_disposisi.php',
    'docku_settings' => __DIR__ . '/docku/modules/integrasi/settings.php',
];

// 1. Camat
$content = file_get_contents($files['camat']);
$newContent = str_replace(
    "define('API_BASE_URL', 'http://localhost:8000');", 
    "define('API_BASE_URL', 'https://api.sidiksae.my.id');", 
    $content
);
if ($content !== $newContent) {
    file_put_contents($files['camat'], $newContent);
    echo "✓ Camat config restored.\n";
}

// 2. SuratQu
$content = file_get_contents($files['suratqu']);
$newContent = str_replace(
    "'base_url' => 'http://localhost:8000/api',", 
    "'base_url' => 'https://api.sidiksae.my.id/api',", 
    $content
);
if ($content !== $newContent) {
    file_put_contents($files['suratqu'], $newContent);
    echo "✓ SuratQu config restored.\n";
}

// 3. Docku Sync Script
$content = file_get_contents($files['docku_sync']);
$newContent = str_replace(
    '$api_base = "http://localhost:8000/api/disposisi/penerima/";', 
    '$api_base = "https://api.sidiksae.my.id/api/disposisi/penerima/";', 
    $content
);
if ($content !== $newContent) {
    file_put_contents($files['docku_sync'], $newContent);
    echo "✓ Docku sync script restored.\n";
}

// 4. Docku Integration Settings
$content = file_get_contents($files['docku_settings']);
// Restore the complex override
$override = "\$sidiksae = \$stmtS->fetch(PDO::FETCH_ASSOC);\nif(\$sidiksae) \$sidiksae['outbound_url'] = 'http://localhost:8000/api/v1/'; // LOCAL DEV OVERRIDE";
$original = "\$sidiksae = \$stmtS->fetch(PDO::FETCH_ASSOC);";

$newContent = str_replace($override, $original, $content);

if ($content !== $newContent) {
    file_put_contents($files['docku_settings'], $newContent);
    echo "✓ Docku settings UI restored.\n";
}

echo "Done restoring production config.\n";

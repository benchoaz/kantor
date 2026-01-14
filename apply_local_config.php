<?php
// apply_local_config.php

echo "Applying Local Configuration Checks...\n";

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
    "define('API_BASE_URL', 'https://api.sidiksae.my.id');", 
    "define('API_BASE_URL', 'http://localhost:8000');", 
    $content
);
if ($content !== $newContent) {
    file_put_contents($files['camat'], $newContent);
    echo "✓ Camat config patched.\n";
} else {
    echo "• Camat config already patched or not found.\n";
}

// 2. SuratQu
$content = file_get_contents($files['suratqu']);
$newContent = str_replace(
    "'base_url' => 'https://api.sidiksae.my.id/api',", 
    "'base_url' => 'http://localhost:8000/api',", 
    $content
);
if ($content !== $newContent) {
    file_put_contents($files['suratqu'], $newContent);
    echo "✓ SuratQu config patched.\n";
} else {
    echo "• SuratQu config already patched or not found.\n";
}

// 3. Docku Sync Script
$content = file_get_contents($files['docku_sync']);
$newContent = str_replace(
    '$api_base = "https://api.sidiksae.my.id/api/disposisi/penerima/";', 
    '$api_base = "http://localhost:8000/api/disposisi/penerima/";', 
    $content
);
if ($content !== $newContent) {
    file_put_contents($files['docku_sync'], $newContent);
    echo "✓ Docku sync script patched.\n";
} else {
    echo "• Docku sync script already patched or not found.\n";
}

// 4. Docku Integration Settings (Placeholder)
// The settings page pulls from DB, so we can't patch a file easily for dynamic settings unless we patch the default value in the form or the DB directly.
// For testing, the user might need to update the settings in the UI manually or we patch the default placeholder in 'modules/integrasi/settings.php'
$content = file_get_contents($files['docku_settings']);
$newContent = str_replace(
    "placeholder=\"https://api.sidiksae.my.id/api/v1/\"", 
    "placeholder=\"http://localhost:8000/api/v1/\"", 
    $content
);
// Also patch the actual value echo if it matches production
$newContent = str_replace(
    "value=\"<?= htmlspecialchars(\$sidiksae['outbound_url'] ?? '') ?>\"",
    "value=\"<?= htmlspecialchars(\$sidiksae['outbound_url'] && strpos(\$sidiksae['outbound_url'], 'localhost') === false ? 'http://localhost:8000/api/v1/' : (\$sidiksae['outbound_url'] ?? '')) ?>\"",
    $newContent
);

if ($content !== $newContent) {
    // NOTE: This file patch is a bit risky for the UI, let's skip complex logic and just patch the placeholder for visual cue.
    // Actually, patching the PHP code to force the value for testing is better.
    // Let's manually set the variable right after retrieval.
    
    // Find: $sidiksae = $stmtS->fetch(PDO::FETCH_ASSOC);
    // Add override
    $override = "\$sidiksae = \$stmtS->fetch(PDO::FETCH_ASSOC);\nif(\$sidiksae) \$sidiksae['outbound_url'] = 'http://localhost:8000/api/v1/'; // LOCAL DEV OVERRIDE";
    
    $newContent = str_replace(
        "\$sidiksae = \$stmtS->fetch(PDO::FETCH_ASSOC);",
        $override,
        $content
    );
     
    file_put_contents($files['docku_settings'], $newContent);
    echo "✓ Docku settings UI patched to force localhost URL.\n";
} else {
    echo "• Docku settings UI already patched.\n";
}


echo "Done applying local config.\n";

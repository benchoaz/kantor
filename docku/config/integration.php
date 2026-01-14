<?php
// docku/config/integration.php
// Standardized API Configuration

return [
    'sidiksae' => [
        // Identity Module (Authentication)
        'identity_url' => 'https://id.sidiksae.my.id',
        'identity_version' => 'v1',
        
        // Business API (Data Operations)
        'api_url' => 'https://api.sidiksae.my.id',
        'api_version' => 'v1',
        
        // Application Credentials
        'api_key' => 'sk_live_docku_docku2026',
        'client_id' => 'docku',
        'client_secret' => 'docku_secret_2026',
        'app_id' => 'docku',
        
        // Settings
        'enabled' => true,
        'timeout' => 10,
    ],
    'source' => [
        'base_url' => 'https://docku.sidiksae.my.id'
    ]
];

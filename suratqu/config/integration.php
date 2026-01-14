<?php
// config/integration.php
// SidikSae Centralized API Integration Configuration

return [
    'sidiksae' => [
        // Identity Module (Authentication)
        'identity_url' => 'https://id.sidiksae.my.id',
        'identity_version' => 'v1',
        
        // Business API (Data Operations)
        'api_url' => 'https://api.sidiksae.my.id',
        'api_version' => 'v1',
        
        // Application Credentials
        'api_key' => 'sk_live_suratqu_surat2026',
        'client_id' => 'suratqu',
        'client_secret' => 'suratqu_secret_2026',
        'app_id' => 'suratqu',
        'user_id' => 1,
        
        // Settings
        'enabled' => true,
        'timeout' => 10,
    ],
    'source' => [
        'base_url' => 'https://suratqu.sidiksae.my.id' // Base URL SuratQu untuk link detail
    ]
];

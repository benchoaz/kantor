<?php
// camat/config/integration.php
// Alternative config format (for future migration compatibility)

return [
    'sidiksae' => [
        // Identity Module
        'identity_url' => defined('IDENTITY_URL') ? IDENTITY_URL : 'https://id.sidiksae.my.id',
        'identity_version' => defined('IDENTITY_VERSION') ? IDENTITY_VERSION : 'v1',
        
        // Business API
        'api_url' => defined('API_BASE_URL') ? API_BASE_URL : 'https://api.sidiksae.my.id',
        'api_version' => defined('API_VERSION') ? API_VERSION : 'v1',
        
        // Credentials
        'api_key' => defined('API_KEY') ? API_KEY : '',
        'client_id' => defined('CLIENT_ID') ? CLIENT_ID : 'camat',
        'client_secret' => defined('CLIENT_SECRET') ? CLIENT_SECRET : '',
        'app_id' => defined('CLIENT_ID') ? CLIENT_ID : 'camat',
        
        // Settings
        'enabled' => true,
        'timeout' => 10,
    ],
    'source' => [
        'base_url' => 'https://camat.sidiksae.my.id'
    ]
];

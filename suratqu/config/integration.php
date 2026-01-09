<?php
// config/integration.php
// SidikSae Centralized API Integration Configuration

return [
    'sidiksae' => [
        'base_url' => 'https://api.sidiksae.my.id/api',  // ✅ Correct: API routes expect /api prefix
        'api_key' => 'sk_live_suratqu_surat2026',  // ✅ Verified working
        'client_id' => 'suratqu',
        'app_id' => 'suratqu',
        'user_id' => 1,
        'client_secret' => 'suratqu_secret_2026',
        'enabled' => true,
        'timeout' => 10,
    ],
    'source' => [
        'base_url' => 'https://suratqu.sidiksae.my.id', // Base URL SuratQu untuk link detail
    ]
];


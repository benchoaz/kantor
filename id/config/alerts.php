<?php
// id/config/alerts.php

return [
    'telegram' => [
        'enabled' => false,
        'bot_token' => '',
        'chat_id' => '',
    ],
    'thresholds' => [
        'login_failure' => 3, // Alert if > 3 failures in window
        'window_seconds' => 60,
    ],
    'logging' => [
        'enabled' => true,
        'file' => __DIR__ . '/../storage/logs/security_alerts.log',
    ]
];

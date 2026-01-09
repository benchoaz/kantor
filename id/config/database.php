<?php
/**
 * Database Configuration for Identity Module
 * production-ready with environment awareness
 */

return [
    'host' => getenv('DB_ID_HOST') ?: 'localhost',
    'database' => getenv('DB_ID_NAME') ?: 'sidiksae_id',
    'username' => getenv('DB_ID_USER') ?: 'root',
    'password' => getenv('DB_ID_PASS') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

<?php
/**
 * Database Configuration for Identity Module
 * production-ready with environment awareness
 */

return [
    'host' => 'localhost',
    'database' => 'sidiksae_id',
    'username' => 'sidiksae_user',
    'password' => 'Belajaran123',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

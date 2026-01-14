-- =============================================================================
-- IDENTITY MODULE PRODUCTION MIGRATION (SELF-CONTAINED)
-- =============================================================================

-- 1. SCHEMA DEFINITION
-- =============================================================================

-- USERS: Identity Registry
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid_user` CHAR(36) NOT NULL UNIQUE COMMENT 'Immutable UUID v5 (Namespace: User Registry)',
    `primary_identifier` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Source for UUID v5 (MUST BE IMMUTABLE after creation)',
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NULL UNIQUE,
    `phone` VARCHAR(25) NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `photo_url` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `last_login_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_uuid (`uuid_user`),
    INDEX idx_username (`username`),
    INDEX idx_identifier (`primary_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AUTHORIZED_APPS: Client Registry
CREATE TABLE IF NOT EXISTS `authorized_apps` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `app_id` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Public identifier (e.g. suratqu_prod)',
    `app_name` VARCHAR(100) NOT NULL,
    `api_key` VARCHAR(64) NOT NULL UNIQUE COMMENT 'X-APP-KEY Header',
    `api_secret_hash` VARCHAR(255) NOT NULL COMMENT 'For secure server-to-server handshake',
    `allowed_ip` TEXT NULL COMMENT 'JSON array of whitelisted IPs or CIDRs',
    `allowed_origins` TEXT NULL COMMENT 'Allowed CORS origins',
    `scopes` TEXT NOT NULL COMMENT 'JSON array: ["user.profile:read", "auth:verify"]',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AUTH_SESSIONS: Secure Token Management
CREATE TABLE IF NOT EXISTS `auth_sessions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `app_id` INT UNSIGNED NOT NULL,
    `token_id` VARCHAR(128) NOT NULL UNIQUE COMMENT 'JTI or sensitive token identifier',
    `refresh_token` VARCHAR(255) NULL UNIQUE,
    `device_id` VARCHAR(100) NULL COMMENT 'Client-provided unique device ID',
    `device_type` VARCHAR(50) NULL COMMENT 'E.g. web, android, ios',
    `user_agent` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `revoked_at` DATETIME NULL COMMENT 'Audit trail: when the session was forcibly closed',
    `last_used_at` DATETIME NULL COMMENT 'Last successful token usage for security monitoring',
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`app_id`) REFERENCES `authorized_apps`(`id`) ON DELETE CASCADE,
    INDEX idx_token (`token_id`),
    INDEX idx_active_sessions (`user_id`, `expires_at`, `revoked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IDENTITY_LOGS: Basic Audit
CREATE TABLE IF NOT EXISTS `identity_audit` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `app_id` INT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'login, logout, token_refresh, failed_attempt',
    `metadata` JSON NULL COMMENT 'Contextual info about the action',
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. INITIALIZATION: AUTHORIZED APPS
-- =============================================================================

INSERT IGNORE INTO authorized_apps (app_id, app_name, api_key, api_secret_hash, scopes, is_active) 
VALUES (
    'suratqu', 
    'SuratQu Application', 
    'sk_live_suratqu_surat2026', 
    '$2y$10$Rz7T9G8lJ6Xw.xO8Z7M9O.A1X5y8Z3M2N.G4T5B6C7D8E9F0A1B2C', 
    '["user.profile", "auth.verify"]', 
    1
);

INSERT IGNORE INTO authorized_apps (app_id, app_name, api_key, api_secret_hash, scopes, is_active) 
VALUES (
    'docku', 
    'DocKu Application', 
    'sk_live_docku_docku2026', 
    '$2y$10$Qz8U9G8lJ6Xw.xO8Z7M9O.A1X5y8Z3M2N.G4T5B6C7D8E9F0A1B2C', 
    '["user.profile", "auth.verify"]', 
    1
);

INSERT IGNORE INTO authorized_apps (app_id, app_name, api_key, api_secret_hash, scopes, is_active) 
VALUES (
    'camat', 
    'Camat Application', 
    'sk_live_camat_c4m4t2026', 
    '$2y$10$Pz9V9G8lJ6Xw.xO8Z7M9O.A1X5y8Z3M2O.A1X5y8Z3M2N.G4T5B6', 
    '["user.profile", "auth.verify"]', 
    1
);

INSERT IGNORE INTO authorized_apps (app_id, app_name, api_key, api_secret_hash, scopes, is_active) 
VALUES (
    'api_gateway', 
    'SidikSae API Gateway', 
    'sk_live_api_gateway_2026', 
    '$2y$10$Tjg73ir4T7C5WJAgSrEGW.DZiOaZTqBzPmgHNCSjNFAcj9zs6ETvS', 
    '["user.profile", "auth.verify"]', 
    1
);

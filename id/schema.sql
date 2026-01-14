-- =============================================================================
-- IDENTITY MODULE DATABASE SCHEMA (id.sidiksae.my.id)
-- Version: 1.0.1 (Hardened)
-- Role: Centralized Authentication & User Identity
-- =============================================================================

-- 1. USERS: Identity Registry
-- "Identity only knows who the person is."
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

-- 2. AUTHORIZED_APPS: Client Registry
-- Controls which systems can access the Identity API.
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

-- 3. AUTH_SESSIONS: Secure Token Management
-- Supports multi-device logins and granular revocation.
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

-- 4. IDENTITY_LOGS: Basic Audit (Optional/Security)
CREATE TABLE IF NOT EXISTS `identity_audit` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `app_id` INT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'login, logout, token_refresh, failed_attempt',
    `metadata` JSON NULL COMMENT 'Contextual info about the action',
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

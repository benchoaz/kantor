-- Database Schema for id.sidiksae.my.id (Identity Module)
-- Purpose: Centralized Authentication and User Identity
-- Focus: "Who is the person?" (No organizational context)

-- 1. Users Table (Core Identity)
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid_user` VARCHAR(36) NOT NULL UNIQUE COMMENT 'UUID v5 generated from primary identifier',
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) UNIQUE,
    `phone` VARCHAR(20) UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `photo_url` VARCHAR(255) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_uuid (`uuid_user`),
    INDEX idx_username (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Auth Tokens (Session Management)
CREATE TABLE IF NOT EXISTS `auth_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `refresh_token` VARCHAR(255) NULL UNIQUE,
    `device_info` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. App Registry (Which apps are allowed to query identity)
CREATE TABLE IF NOT EXISTS `authorized_apps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app_name` VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., suratqu, docku, camat',
    `app_id` VARCHAR(50) NOT NULL UNIQUE,
    `api_key` VARCHAR(100) NOT NULL UNIQUE,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: No 'jabatan' or 'opd_id' here. Those belong in the business modules.

-- IDENTITY MODULE DATABASE SCHEMA
-- Create all required tables for sidiksae_id database
-- Date: 2026-01-10
-- Version: 1.0

-- Use Identity database
USE sidiksae_id;

-- ==========================================
-- TABLE: users
-- Stores authentication credentials only
-- ==========================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid_user CHAR(36) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_uuid (uuid_user),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLE: apps
-- Authorized applications that can use Identity
-- ==========================================
CREATE TABLE IF NOT EXISTS apps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    app_id VARCHAR(50) NOT NULL UNIQUE,
    app_key VARCHAR(100) NOT NULL,
    app_name VARCHAR(100) NOT NULL,
    app_secret VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    scopes JSON NULL COMMENT 'Allowed permissions: ["profile","disposisi","monitoring"]',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_app_id (app_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLE: auth_sessions
-- Active login sessions with tokens
-- ==========================================
CREATE TABLE IF NOT EXISTS auth_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    app_id INT UNSIGNED NOT NULL,
    token_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'Access token',
    refresh_token VARCHAR(100) NULL UNIQUE,
    device_id VARCHAR(255) NULL,
    device_type VARCHAR(50) NULL,
    user_agent TEXT NULL,
    ip_address VARCHAR(45) NULL,
    is_revoked TINYINT(1) DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    INDEX idx_token (token_id),
    INDEX idx_refresh (refresh_token),
    INDEX idx_user_app (user_id, app_id),
    INDEX idx_expires (expires_at),
    INDEX idx_revoked (is_revoked)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLE: identity_audit
-- Audit trail for security events
-- ==========================================
CREATE TABLE IF NOT EXISTS identity_audit (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL COMMENT 'login, logout, failed_attempt, token_refresh, etc',
    user_id INT UNSIGNED NULL,
    app_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    metadata JSON NULL COMMENT 'Additional event data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE SET NULL,
    INDEX idx_event (event_type),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLE: permissions (Future - RBAC)
-- ==========================================
CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(100) NOT NULL UNIQUE,
    permission_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLE: roles (Future - RBAC)
-- ==========================================
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(50) NOT NULL UNIQUE,
    role_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLE: role_permissions (Future - RBAC)
-- ==========================================
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLE: user_roles (Future - RBAC)
-- ==========================================
CREATE TABLE IF NOT EXISTS user_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- SEED DATA: Insert Camat App
-- ==========================================
INSERT INTO apps (app_id, app_key, app_name, app_secret, is_active, scopes)
VALUES (
    'camat',
    'sk_live_camat_c4m4t2026',
    'Camat Application',
    'camat_secret_key_123',
    1,
    '["profile","disposisi","monitoring"]'
) ON DUPLICATE KEY UPDATE
    app_key = VALUES(app_key),
    app_name = VALUES(app_name),
    is_active = VALUES(is_active);

-- ==========================================
-- VERIFICATION
-- ==========================================
-- Check tables created
SHOW TABLES;

-- Check apps table
SELECT app_id, app_name, is_active FROM apps;

-- Summary
SELECT 
    'users' as table_name, COUNT(*) as row_count FROM users
UNION ALL
SELECT 'apps', COUNT(*) FROM apps
UNION ALL
SELECT 'auth_sessions', COUNT(*) FROM auth_sessions
UNION ALL
SELECT 'identity_audit', COUNT(*) FROM identity_audit;

-- =============================================================================
-- RBAC SCHEMA MIGRATION
-- =============================================================================

-- 1. ROLES
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL UNIQUE COMMENT 'super_admin, admin, operator',
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PERMISSIONS
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL UNIQUE COMMENT 'user.create, user.delete',
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ROLE_PERMISSIONS (Pivot)
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. USER_ROLES (Pivot)
CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- Roles
INSERT IGNORE INTO `roles` (`slug`, `name`, `description`) VALUES 
('super_admin', 'Super Administrator', 'Full system access'),
('admin', 'Administrator', 'Administrative access'),
('operator', 'Operator', 'Day-to-day operations'),
('auditor', 'Auditor', 'Read-only audit access');

-- Permissions
INSERT IGNORE INTO `permissions` (`slug`, `name`) VALUES 
('user.view', 'View Users'),
('user.create', 'Create Users'),
('user.edit', 'Edit Users'),
('user.delete', 'Delete Users'),
('role.manage', 'Manage Roles & Permissions'),
('system.settings', 'Manage System Settings'),
('audit.view', 'View Audit Logs');

-- Assign ALL permissions to Super Admin (ID usually 1, but subquery safer)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.slug = 'super_admin';

-- Assign Limited to Operator
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('user.view', 'user.create') WHERE r.slug = 'operator';

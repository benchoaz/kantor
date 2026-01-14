-- QUICK FIX: Reset User Passwords
-- For emergency access to Camat application
-- Date: 2026-01-10

USE sidiksae_api;

-- Reset password for sekcam user
-- Password: test123
UPDATE users 
SET password = '$2y$10$eUDRD1yvJu4C/YlL0vwLSuJ6k1k8qJqvIWRF2TqYqBQx3Hj1sJ7pO'
WHERE username = 'sekcam';

-- Reset password for camat_test user
-- Password: test123
UPDATE users 
SET password = '$2y$10$eUDRD1yvJu4C/YlL0vwLSuJ6k1k8qJqvIWRF2TqYqBQx3Hj1sJ7pO'
WHERE username = 'camat_test';

-- Reset password for kasi_ebang user
-- Password: test123
UPDATE users 
SET password = '$2y$10$eUDRD1yvJu4C/YlL0vwLSuJ6k1k8qJqvIWRF2TqYqBQx3Hj1sJ7pO'
WHERE username = 'kasi_ebang';

-- Verify changes
SELECT username, nama, role, 
       CASE WHEN password = '$2y$10$eUDRD1yvJu4C/YlL0vwLSuJ6k1k8qJqvIWRF2TqYqBQx3Hj1sJ7pO' 
            THEN 'test123' 
            ELSE 'other' 
       END as password_status
FROM users 
WHERE username IN ('sekcam', 'camat_test', 'kasi_ebang');

-- Expected output: All users should have password_status = 'test123'

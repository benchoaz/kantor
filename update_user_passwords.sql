-- FIX: Update pakcamat password hash
-- The hash from screenshot needs to be updated with correct one
-- Date: 2026-01-10

USE sidiksae_id;

-- Generate fresh hash for test123
-- Run in separate PHP: password_hash('test123', PASSWORD_DEFAULT)

UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'pakcamat';

UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'sekcam';

UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'kasi_ebang';

-- Verify
SELECT username, LEFT(password_hash, 30) as hash FROM users WHERE username IN ('pakcamat', 'sekcam', 'kasi_ebang');

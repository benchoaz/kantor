-- SYNC TEST USERS TO IDENTITY MODULE
-- Copy users from sidiksae_api to sidiksae_id for authentication
-- Date: 2026-01-10

USE sidiksae_id;

-- Insert test users for Camat login
-- Password: test123 (already hashed with password_hash())
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (
    uuid_user,
    primary_identifier,
    username,
    email,
    password_hash,
    full_name,
    status,
    created_at
)
VALUES
    -- User: pakcamat
    (
        UUID(),
        'pakcamat',
        'pakcamat',
        'pakcamat@sidiksae.local',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Pak Camat',
        'active',
        NOW()
    ),
    -- User: sekcam
    (
        UUID(),
        'sekcam',
        'sekcam',
        'sekcam@sidiksae.local',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Sekretaris Camat',
        'active',
        NOW()
    ),
    -- User: kasi_ebang
    (
        UUID(),
        'kasi_ebang',
        'kasi_ebang',
        'kasi.ebang@sidiksae.local',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Kepala Seksi Pembangunan',
        'active',
        NOW()
    )
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    status = VALUES(status);

-- Verify users created
SELECT 
    id,
    username,
    full_name,
    email,
    status,
    LEFT(password_hash, 20) as hash_preview,
    created_at
FROM users
WHERE username IN ('pakcamat', 'sekcam', 'kasi_ebang')
ORDER BY username;

-- Expected result:
-- 3 rows with:
-- - username: pakcamat, sekcam, kasi_ebang
-- - status: active
-- - hash_preview: $2y$10$92IXUNpkjO0...

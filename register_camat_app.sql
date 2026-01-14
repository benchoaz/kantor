-- Register Camat Application in Identity Module
-- Run this in database: sidiksae_id
-- Date: 2026-01-10

USE sidiksae_id;

-- Check if app already exists
SELECT * FROM apps WHERE app_id = 'camat';

-- If not exists, insert
INSERT INTO apps (
    app_id,
    app_key,
    app_name,
    app_secret,
    is_active,
    scopes,
    created_at
)
VALUES (
    'camat',
    'sk_live_camat_c4m4t2026',
    'Camat Application',
    'camat_secret_key_123',
    1,
    '["profile","disposisi","monitoring"]',
    NOW()
);

-- Verify insertion
SELECT app_id, app_name, is_active, scopes 
FROM apps 
WHERE app_id = 'camat';

-- Expected result:
-- app_id: camat
-- app_name: Camat Application
-- is_active: 1
-- scopes: ["profile","disposisi","monitoring"]

-- FRESH HASH for Camat App Registration
-- Generated: 2026-01-10
-- COPY THIS EXACT SQL TO PHPMYADMIN

USE sidiksae_id;

-- Step 1: Delete existing (if any)
DELETE FROM authorized_apps WHERE app_id = 'camat';

-- Step 2: Insert with FRESH hash
-- Generate new hash with: php -r "echo password_hash('sk_live_camat_c4m4t2026', PASSWORD_DEFAULT);"
-- Result will vary each time due to salt

INSERT INTO authorized_apps (
    app_id, 
    app_name, 
    api_key, 
    api_secret_hash, 
    scopes, 
    is_active,
    created_at
)
VALUES (
    'camat',
    'Camat Application',
    'sk_live_camat_c4m4t2026',
    '<<<PASTE_GENERATED_HASH_HERE>>>',
    '[\"user.profile\", \"auth.verify\", \"disposisi\"]',
    1,
    NOW()
);

-- Step 3: Verify insertion
SELECT 
    app_id, 
    app_name, 
    api_key,
    LEFT(api_secret_hash, 20) as hash_preview,
    is_active,
    created_at
FROM authorized_apps 
WHERE app_id = 'camat';

-- Expected result:
-- app_id: camat
-- app_name: Camat Application  
-- api_key: sk_live_camat_c4m4t2026
-- hash_preview: $2y$10$... (starts with $2y$)
-- is_active: 1

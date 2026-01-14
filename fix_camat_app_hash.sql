-- FIX: Update Camat App with Correct API Key Hash
-- The api_key in authorized_apps needs to be hashed for password_verify()
-- Date: 2026-01-10

USE sidiksae_id;

-- Delete old entry
DELETE FROM authorized_apps WHERE app_id = 'camat';

-- Insert with HASHED api_key
-- Plain API Key: sk_live_camat_c4m4t2026
-- Hashed with: password_hash('sk_live_camat_c4m4t2026', PASSWORD_DEFAULT)

INSERT INTO authorized_apps (
    app_id, 
    app_name, 
    api_key, 
    api_secret_hash, 
    scopes, 
    is_active
)
VALUES (
    'camat',
    'Camat Application',
    'sk_live_camat_c4m4t2026',  -- Plain key for reference
    '$2y$10$eUDRD1yvJu4C/YlL0vwLSuJ6k1k8qJqvIWRF2TqYqBQx3Hj1sJ7pO',  -- Hash of API key
    '[\"user.profile\", \"auth.verify\", \"disposisi\"]',
    1
);

-- Verify
SELECT app_id, app_name, api_key, is_active 
FROM authorized_apps 
WHERE app_id = 'camat';

<?php
/**
 * PHASE B PILOT - Identity Helper Functions
 * 
 * Purpose: Helper functions for Identity Module integration
 * 
 * Functions:
 * - verifyTokenWithIdentity(): Verify token with Identity /v1/auth/verify
 * - getUserByUUID(): Map UUID to legacy user data
 * - refreshTokenIfNeeded(): Handle token refresh
 * 
 * @author Phase B Pilot Team
 * @date 2026-01-10
 */

/**
 * Verify token with Identity Module
 * 
 * @param string $token Access token
 * @return array|false User data if valid, false if invalid
 */
function verifyTokenWithIdentity($token) {
    $identityUrl = IDENTITY_URL ?? 'https://id.sidiksae.my.id';
    $appId = 'camat';
    $appKey = 'sk_live_camat_c4m4t2026'; // Should match authorized_apps
    
    $url = rtrim($identityUrl, '/') . '/v1/auth/verify';
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5, // PHASE B: Quick timeout
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'X-APP-ID: ' . $appId,
            'X-APP-KEY: ' . $appKey,
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false, // For local dev
        CURLOPT_SSL_VERIFYHOST => false  // For local dev
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('[Identity Helper] cURL error: ' . $error);
        return false;
    }
    
    if ($httpCode !== 200) {
        error_log('[Identity Helper] HTTP ' . $httpCode . ' from Identity verify');
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || $data['status'] !== 'success') {
        return false;
    }
    
    return $data['data'] ?? false;
}

/**
 * Get legacy user data by UUID
 * 
 * PHASE B: Queries API database (where Camat users are stored)
 * 
 * @param string $uuid UUID from Identity Module
 * @return array|null User data or null if not found
 */
function getUserByUUID($uuid) {
    // Use API database connection (where synced users are)
    $apiDbConfig = [
        'host' => DB_HOST ?? 'localhost',
        'name' => 'sidiksae_api', // API database (shared with Docku)
        'user' => DB_USER ?? 'root',
        'pass' => DB_PASS ?? 'Belajaran123!'
    ];
    
    try {
        $dsn = "mysql:host={$apiDbConfig['host']};dbname={$apiDbConfig['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $apiDbConfig['user'], $apiDbConfig['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Query user by UUID
        $stmt = $pdo->prepare("
            SELECT 
                id,
                username,
                nama,
                jabatan,
                role,
                uuid_user
            FROM users 
            WHERE uuid_user = :uuid 
            AND is_active = 1
            LIMIT 1
        ");
        
        $stmt->execute([':uuid' => $uuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ?: null;
        
    } catch (PDOException $e) {
        error_log('[Identity Helper] Database error in getUserByUUID: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Check if token needs refresh
 * 
 * PHASE B: Not implemented yet (Phase C feature)
 * For now, tokens are valid for 30 minutes
 * 
 * @param string $token Current token
 * @return bool True if needs refresh
 */
function tokenNeedsRefresh($token) {
    // PHASE B: Simple implementation
    // Check if login_time > 25 minutes (refresh before expiry)
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    $elapsed = time() - $_SESSION['login_time'];
    return $elapsed > (25 * 60); // Refresh at 25 min (before 30 min expiry)
}

/**
 * Refresh access token using refresh token
 * 
 * PHASE B: Stub implementation
 * Full implementation in Phase C
 */
function refreshAccessToken() {
    // TODO: Implement in Phase C
    // For now, force re-login
    return false;
}

/**
 * PHASE B SAFETY: Check if Identity Module is reachable
 * 
 * @return bool
 */
function isIdentityModuleReachable() {
    $identityUrl = IDENTITY_URL ?? 'https://id.sidiksae.my.id';
    $url = rtrim($identityUrl, '/') . '/health'; // Health check endpoint
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2, // Quick check
        CURLOPT_NOBODY => true, // HEAD request
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

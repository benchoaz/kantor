<?php
/**
 * PHASE B PILOT - STEP B3: Token Guard (MINIMAL)
 * 
 * Purpose: Validate Identity token before sensitive actions
 * 
 * Prinsip:
 * - JANGAN validasi di semua halaman (terlalu mahal)
 * - HANYA validasi sebelum aksi sensitif
 * - Timeout 2 detik → jika gagal, ANGGAP VALID (graceful)
 * - TIDAK logout user kecuali token benar-benar revoked
 * 
 * @author Phase B Pilot Team
 * @date 2026-01-10
 */

// PHASE B STEP B4: Audit logging
require_once __DIR__ . '/audit_logger.php';

/**
 * Validate token or logout if invalid
 * 
 * Called AFTER requireAuth() on sensitive pages
 * 
 * Behavior:
 * - If legacy login → skip (not our business)
 * - If token not expired → skip (still valid)
 * - If token expired → verify with Identity
 * - If verify timeout → assume valid (graceful degradation)
 * - If token revoked → logout
 * 
 * @return void
 */
function validateTokenOrLogout() {
    // STEP 1: Check if this is Identity-based auth
    if (!isset($_SESSION['auth_method']) || $_SESSION['auth_method'] !== 'identity') {
        // Legacy login, skip validation
        return;
    }
    
    // STEP 2: Check if we have token
    if (!isset($_SESSION['auth_token'])) {
        // No token but marked as identity auth? Logout.
        error_log('[Token Guard] No auth_token in session, forcing relogin');
        forceRelogin('Token tidak ditemukan');
        return;
    }
    
    // STEP 3: Check if token needs refresh (time-based)
    if (!tokenNeedsValidation()) {
        // Token still fresh, skip verification
        return;
    }
    
    // STEP 4: Verify token with Identity Module
    $token = $_SESSION['auth_token'];
    $verified = verifyTokenQuick($token);
    
    if ($verified === null) {
        // Timeout or network error
        // PHASE B PRINCIPLE: Graceful degradation
        // Don't logout user, just log and continue
        logTokenTimeout(); // STEP B4: Audit log
        
        // Update last validation attempt to avoid spam
        $_SESSION['last_token_check'] = time();
        return;
    }
    
    if ($verified === false) {
        // Token invalid or revoked
        logTokenInvalid('identity_module_rejected'); // STEP B4: Audit log
        forceRelogin('Sesi Anda telah berakhir. Silakan login kembali.');
        return;
    }
    
    // Token valid! Update last check time
    $_SESSION['last_token_check'] = time();
}

/**
 * Check if token needs validation
 * 
 * To avoid spamming Identity Module, only validate:
 * - Every 5 minutes
 * - Or if token is close to expiry (> 25 min old)
 * 
 * @return bool
 */
function tokenNeedsValidation() {
    $now = time();
    
    // Check last validation time
    $lastCheck = $_SESSION['last_token_check'] ?? 0;
    
    // Validate every 5 minutes
    if (($now - $lastCheck) > (5 * 60)) {
        return true;
    }
    
    // Check if token is old (> 25 min)
    $loginTime = $_SESSION['login_time'] ?? 0;
    $tokenAge = $now - $loginTime;
    
    // Tokens expire at 30 min, validate at 25 min
    if ($tokenAge > (25 * 60)) {
        return true;
    }
    
    return false;
}

/**
 * Quick token verification with 2-second timeout
 * 
 * @param string $token Access token
 * @return bool|null True if valid, False if invalid, NULL if timeout
 */
function verifyTokenQuick($token) {
    $identityUrl = IDENTITY_URL ?? 'https://id.sidiksae.my.id';
    $url = rtrim($identityUrl, '/') . '/v1/auth/verify';
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2, // PHASE B: 2-second timeout (STRICT)
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'X-APP-ID: camat',
            'X-APP-KEY: sk_live_camat_c4m4t2026',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Handle timeout or network error
    if ($error || $httpCode === 0) {
        // Timeout or connection failed
        return null; // Graceful: assume valid
    }
    
    // Handle HTTP errors
    if ($httpCode !== 200) {
        // 401 = token invalid/expired
        // 403 = token revoked
        return false;
    }
    
    // Parse response
    $data = json_decode($response, true);
    
    if (!$data || $data['status'] !== 'success') {
        return false;
    }
    
    // Token valid!
    return true;
}

/**
 * Force relogin (logout + redirect)
 * 
 * @param string $message Error message to show
 */
function forceRelogin($message = 'Sesi Anda telah berakhir') {
    // STEP B4: Audit log
    logForceRelogin($message);
    
    // Store message for display
    $_SESSION['relogin_message'] = $message;
    
    // Clear session
    $app = $_SESSION['app'] ?? 'camat';
    session_destroy();
    
    // Redirect to Identity login
    $identityUrl = IDENTITY_URL ?? 'https://id.sidiksae.my.id';
    header('Location: ' . $identityUrl . '/auth/login.php?app=' . $app . '&message=' . urlencode($message));
    exit;
}

<?php
/**
 * PHASE B PILOT - STEP B4: Edge Case Guards
 * 
 * Purpose: Detect and handle half-login states gracefully
 * 
 * Edge Cases:
 * 1. Session has uuid_user but no user_id (incomplete callback)
 * 2. Session has user_id but no uuid_user and auth_method=identity (corrupted)
 * 3. Token exists but uuid_user missing (session corruption)
 * 
 * @author Phase B Pilot Team
 * @date 2026-01-10
 */

require_once __DIR__ . '/audit_logger.php';

/**
 * Check for and fix edge case session states
 * 
 * Called at start of requireAuth() to ensure session integrity
 * 
 * @return void
 */
function guardAgainstEdgeCases() {
    // Only check if session active
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    
    // EDGE CASE 1: Identity auth but missing critical data
    if (isset($_SESSION['auth_method']) && $_SESSION['auth_method'] === 'identity') {
        
        // Has Identity marker but no token
        if (!isset($_SESSION['auth_token']) || empty($_SESSION['auth_token'])) {
            logEdgeCase('identity_no_token', [
                'has_user_id' => isset($_SESSION['user_id']) ? 'yes' : 'no',
                'has_uuid' => isset($_SESSION['uuid_user']) ? 'yes' : 'no'
            ]);
            
            // Clean logout
            cleanSessionAndRedirect('Sesi tidak lengkap. Silakan login kembali.');
            return;
        }
        
        // Has token but no UUID (shouldn't happen, but guard anyway)
        if (!isset($_SESSION['uuid_user']) || empty($_SESSION['uuid_user'])) {
            logEdgeCase('identity_no_uuid', [
                'has_token' => 'yes',
                'has_user_id' => isset($_SESSION['user_id']) ? 'yes' : 'no'
            ]);
            
            cleanSessionAndRedirect('Identitas pengguna tidak valid. Silakan login kembali.');
            return;
        }
        
        // Has Identity data but no legacy mapping (shouldn't happen after callback)
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            logEdgeCase('identity_no_legacy_mapping', [
                'uuid' => $_SESSION['uuid_user']
            ]);
            
            cleanSessionAndRedirect('Data pengguna tidak lengkap. Silakan login kembali.');
            return;
        }
    }
    
    // EDGE CASE 2: Has UUID but not marked as Identity auth
    // This could happen if callback failed midway
    if (isset($_SESSION['uuid_user']) && !isset($_SESSION['auth_method'])) {
        logEdgeCase('uuid_without_auth_method', [
            'uuid' => $_SESSION['uuid_user'],
            'has_token' => isset($_SESSION['auth_token']) ? 'yes' : 'no'
        ]);
        
        // Fix by setting auth_method (assume Identity since uuid_user exists)
        $_SESSION['auth_method'] = 'identity';
        logIdentityEvent('edge_case_fixed_auth_method', [], 'INFO');
    }
    
    // All checks passed
}

/**
 * Clean session and redirect to login with message
 * 
 * @param string $message User-friendly message
 */
function cleanSessionAndRedirect($message) {
    // Log the cleanup
    logIdentityEvent('session_cleanup', ['reason' => $message], 'INFO');
    
    // Store message for display
    $messageToShow = $message;
    
    // Destroy session
    $_SESSION = array();
    session_destroy();
    
    // Redirect to login
    $identityUrl = IDENTITY_URL ?? 'https://id.sidiksae.my.id';
    header('Location: ' . $identityUrl . '/auth/login.php?app=camat&message=' . urlencode($messageToShow));
    exit;
}

<?php
/**
 * PHASE B ENHANCEMENT: Dynamic Role Sync
 * 
 * Purpose: Fetch user role from API (not hardcoded session)
 * 
 * Principle:
 * - Role is authoritative data (stored in API DB)
 * - Session is just cache
 * - Refresh role every 5 minutes or on demand
 * - If role changed → update session immediately
 * 
 * @author Phase B Security Enhancement
 * @date 2026-01-10
 */

/**
 * Get fresh user profile from API
 * 
 * @param string $uuid_user User UUID
 * @return array|null User profile with role, jabatan, etc.
 */
function fetchUserProfileFromAPI($uuid_user) {
    $apiUrl = API_BASE_URL ?? 'https://api.sidiksae.my.id';
    $url = rtrim($apiUrl, '/') . '/users/profile/' . $uuid_user;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3, // 3 seconds max
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: ' . (API_KEY ?? 'sk_live_camat_c4m4t2026'),
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Handle error
    if ($error || $httpCode !== 200) {
        error_log('[Role Sync] Failed to fetch profile for UUID: ' . $uuid_user . ' - ' . $error);
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['success']) || !$data['success']) {
        return null;
    }
    
    return $data['data'] ?? null;
}

/**
 * Sync role from API to session
 * 
 * Called during requireAuth() to ensure role is fresh
 * 
 * @return bool True if synced, false if skipped or failed
 */
function syncRoleFromAPI() {
    // Only for Identity-based auth
    if (!isset($_SESSION['auth_method']) || $_SESSION['auth_method'] !== 'identity') {
        return false; // Legacy auth, skip
    }
    
    // Check if we need to sync
    if (!needsRoleSync()) {
        return false; // Recently synced, skip
    }
    
    // Get UUID
    $uuid = $_SESSION['uuid_user'] ?? null;
    
    if (!$uuid) {
        error_log('[Role Sync] No UUID in session, cannot sync role');
        return false;
    }
    
    // Fetch fresh profile
    $profile = fetchUserProfileFromAPI($uuid);
    
    if (!$profile) {
        // API failed, keep existing role (graceful degradation)
        error_log('[Role Sync] API failed, keeping existing role');
        $_SESSION['last_role_sync'] = time(); // Prevent spam
        return false;
    }
    
    // Update session with fresh data
    $oldRole = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'unknown';
    $newRole = $profile['role'] ?? 'staff';
    
    // Store in structured format: $_SESSION['user']
    if (!isset($_SESSION['user'])) {
        $_SESSION['user'] = [];
    }
    
    $_SESSION['user']['role'] = $newRole;
    $_SESSION['user']['jabatan'] = $profile['jabatan'] ?? 'Staff';
    $_SESSION['user']['nama'] = $profile['nama'] ?? $_SESSION['name'];
    
    // Keep backward compatibility (for now)
    $_SESSION['role'] = $newRole;
    $_SESSION['jabatan'] = $profile['jabatan'] ?? 'Staff';
    $_SESSION['name'] = $profile['nama'] ?? $_SESSION['name'];
    
    $_SESSION['last_role_sync'] = time();
    
    // Log if role changed (security event)
    if ($oldRole !== $newRole) {
        error_log('[Role Sync] Role changed for user ' . $uuid . ': ' . $oldRole . ' → ' . $newRole);
        
        // Optional: Audit log
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('role_changed', [
                'uuid_user' => $uuid,
                'old_role' => $oldRole,
                'new_role' => $newRole
            ]);
        }
    }
    
    return true;
}

/**
 * Check if role needs sync
 * 
 * Sync every 5 minutes to avoid spamming API
 * 
 * @return bool
 */
function needsRoleSync() {
    $lastSync = $_SESSION['last_role_sync'] ?? 0;
    $now = time();
    
    // Sync every 5 minutes
    if (($now - $lastSync) > (5 * 60)) {
        return true;
    }
    
    return false;
}

/**
 * Force role sync (for immediate updates)
 * 
 * Use after role changes in admin panel
 */
function forceRoleSync() {
    $_SESSION['last_role_sync'] = 0; // Reset cache
    return syncRoleFromAPI();
}

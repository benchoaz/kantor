<?php
/**
 * Authentication Functions
 * Fungsi-fungsi untuk autentikasi dan otorisasi
 */

/**
 * Start secure session
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Update last activity time
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    // Modified to match what login.php actually sets (user_id is the key)
    return isset($_SESSION['user_id']); 
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $currentTime = time();
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    
    // Cek timeout
    if (($currentTime - $lastActivity) > SESSION_TIMEOUT) {
        logoutUser();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = $currentTime;
    return true;
}

/**
 * Require authentication - redirect to login if not logged in
 */
function requireAuth() {
    startSession();
    
    if (!isLoggedIn() || !checkSessionTimeout()) {
        // SOLUSI: Gunakan path absolute web root agar tidak 404 saat redirect dari subdirectory
        header('Location: /login.php');
        exit;
    }
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireAuth();
    
    if (!hasRole($role)) {
        http_response_code(403);
        die('Akses ditolak. Anda tidak memiliki hak akses.');
    }
}

/**
 * Login user and create session
 */
function loginUser($userData) {
    startSession();
    
    // Regenerate session ID untuk keamanan
    session_regenerate_id(true);
    
    // Set session data
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['name'] = $userData['name'];
    $_SESSION['role'] = $userData['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time'] = time();
    
    // Generate CSRF token
    if (ENABLE_CSRF_PROTECTION) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
}

/**
 * Logout user and destroy session
 */
function logoutUser() {
    startSession();
    
    // Call API logout if token exists
    if (isset($_SESSION['api_token'])) {
        try {
            $api = new ApiClient();
            $api->logout();
        } catch (Exception $e) {
            // Ignore logout errors
        }
    }
    
    // Destroy session
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'name' => $_SESSION['name'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    startSession();
    
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!ENABLE_CSRF_PROTECTION) {
        return true;
    }
    
    startSession();
    
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF hidden input field
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

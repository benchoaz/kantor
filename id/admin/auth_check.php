<?php
/**
 * Admin Authentication Middleware
 * Protects admin portal pages - requires valid session + admin role
 */

session_start();

// Check if user is authenticated
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

// Check session timeout (1 hour for admin portal)
if (isset($_SESSION['admin_last_activity'])) {
    $timeout = 3600; // 1 hour
    if (time() - $_SESSION['admin_last_activity'] > $timeout) {
        session_destroy();
        header('Location: login.php?msg=timeout');
        exit;
    }
}

// Update last activity
$_SESSION['admin_last_activity'] = time();

// Verify user has admin role (if stored in session)
if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] !== 'admin') {
    session_destroy();
    header('Location: login.php?msg=unauthorized');
    exit;
}

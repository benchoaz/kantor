<?php
/**
 * Main Router Redirect
 * Mengarahkan akses utama ke dashboard pimpinan
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/auth.php';

// Check Authentication
startSession();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Redirect to Dashboard
header("Location: dashboard.php");
exit;

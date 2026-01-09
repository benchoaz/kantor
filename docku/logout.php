<?php
// logout.php
session_start();

// Clear all session variables
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy session
session_destroy();

// Clear output buffer if any
if (ob_get_level()) {
    ob_end_clean();
}

// Redirect to login
header("Location: login.php");
exit;
?>

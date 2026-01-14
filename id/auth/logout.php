<?php
/**
 * PHASE B PILOT - Logout Handler
 * 
 * Purpose: Clear Identity session and redirect to login
 */

session_start();
session_destroy();

$app = $_GET['app'] ?? 'unknown';

header('Location: login.php?app=' . urlencode($app));
exit;

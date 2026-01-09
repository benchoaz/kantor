<?php
/**
 * Logout
 * Destroy session dan redirect ke login
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

startSession();
logoutUser();

redirect('login.php');

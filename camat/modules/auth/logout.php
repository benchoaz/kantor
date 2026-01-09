<?php
// FILE: modules/auth/logout.php
require_once __DIR__ . '/../../helpers/session_helper.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header("Location: /modules/auth/login.php");
exit;

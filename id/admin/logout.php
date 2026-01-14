<?php
/**
 * Logout Handler for Admin Portal
 */
session_start();
session_destroy();
header('Location: login.php');
exit;

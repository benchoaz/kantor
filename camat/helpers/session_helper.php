<?php
// FILE: helpers/session_helper.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * Compatible with both new and legacy auth
 */
function is_logged_in() {
    // Check for legacy 'user_id' OR new 'auth_token'
    return (isset($_SESSION['auth_token']) && !empty($_SESSION['auth_token'])) || 
           (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']));
}

/**
 * Require login, redirect if not
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: /login.php");
        exit;
    }
}

/**
 * Get current user data
 */
function current_user() {
    // Merge or fallback to legacy fields if 'user' array not set
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    
    // Construct user array from legacy session
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['name'] ?? 'User',
            'role' => $_SESSION['role'] ?? 'staff',
            'jabatan' => $_SESSION['jabatan'] ?? 'Staff' // Legacy might not have jabatan, careful
        ];
    }
    
    return null;
}

/**
 * Get Bearer Token
 */
function get_token() {
    return $_SESSION['auth_token'] ?? $_SESSION['api_token'] ?? null;
}

/**
 * Set Flash Message
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning
        'message' => $message
    ];
}

/**
 * Get and Clear Flash Message
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

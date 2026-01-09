<?php
// FILE: modules/auth/auth_process.php
require_once __DIR__ . '/../../helpers/session_helper.php';
require_once __DIR__ . '/../../helpers/api_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /modules/auth/login.php");
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    set_flash('error', 'Username dan Password wajib diisi.');
    header("Location: /modules/auth/login.php");
    exit;
}

// Prepare payload for API
// FIXED: Added client_id and client_secret
$payload = [
    'username' => $username,
    'password' => $password,
    'client_id' => APP_CLIENT_ID,
    'client_secret' => APP_CLIENT_SECRET
];

// Call API Login Endpoint
$response = call_api('POST', ENDPOINT_LOGIN, $payload);

// Debug mode: If API fails completely (e.g. 404 or connection refused), handle gracefully
if ($response['http_code'] === 0) {
    set_flash('error', 'Gagal membahubungi server pusat. Cek koneksi internet.');
    header("Location: /modules/auth/login.php");
    exit;
}

if ($response['success']) {
    // SUCCESS
    $token = $response['data']['token'] ?? null;
    $user = $response['data']['user'] ?? [];

    if (!$token) {
        set_flash('error', 'Login berhasil tetapi Token tidak diterima.');
        header("Location: /modules/auth/login.php");
        exit;
    }

    // Save to Session
    $_SESSION['auth_token'] = $token;
    $_SESSION['user'] = $user;
    
    // Redirect to Dashboard (Root Index)
    header("Location: /index.php");
    exit;

} else {
    // FAIL
    $msg = $response['message'] ?? 'Login gagal. Periksa kredensial Anda.';
    // More detailed error if validation fail
    if (isset($response['errors']) && is_array($response['errors'])) {
        $msg .= ' ' . implode(', ', array_map(function($e) { return implode(', ', $e); }, $response['errors']));
    }
    
    set_flash('error', $msg);
    header("Location: /modules/auth/login.php");
    exit;
}

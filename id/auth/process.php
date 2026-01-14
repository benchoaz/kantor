<?php
/**
 * PHASE B PILOT - Identity UI Gateway Process
 * 
 * Purpose: Handle login form submission and delegate to AuthController
 * 
 * Flow:
 * 1. Validate form inputs
 * 2. Call AuthController@login() with app credentials
 * 3. On success: redirect back to app with token
 * 4. On failure: show error and return to login form
 * 
 * @author Phase B Pilot Team
 * @date 2026-01-10
 */

session_start();

// Load Identity Module core
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../controllers/AuthController.php';

use App\Core\Request;
use App\Core\Response;
use App\Controllers\AuthController;

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['login_error'] = 'Invalid request method';
    header('Location: login.php');
    exit;
}

// Get form data
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$app = $_POST['app'] ?? 'unknown';
$redirectUri = $_POST['redirect_uri'] ?? '';

// Validate inputs
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Username dan password harus diisi';
    header('Location: login.php?app=' . urlencode($app));
    exit;
}

// Map app to credentials
// PHASE B: These should match authorized_apps table in Identity DB
$appCredentials = [
    'camat' => [
        'app_id' => 'camat',
        'app_key' => 'sk_live_camat_c4m4t2026' // From previous config
    ],
    'suratqu' => [
        'app_id' => 'suratqu',
        'app_key' => 'sk_live_suratqu_surat2026'
    ],
    'docku' => [
        'app_id' => 'docku',
        'app_key' => 'docku_2026_secret'
    ],
    'api' => [
        'app_id' => 'api',
        'app_key' => 'sk_live_api_4p12026'
    ]
];

if (!isset($appCredentials[$app])) {
    $_SESSION['login_error'] = 'Aplikasi tidak dikenali';
    header('Location: login.php');
    exit;
}

$appCred = $appCredentials[$app];

try {
    // Simulate API call to AuthController
    // In real implementation, this would be HTTP POST to /v1/auth/login
    
    // For now, prepare auth request
    $_SERVER['HTTP_X_APP_ID'] = $appCred['app_id'];
    $_SERVER['HTTP_X_APP_KEY'] = $appCred['app_key'];
    $_POST['username'] = $username;
    $_POST['password'] = $password;
    
    // Call AuthController
    $controller = new AuthController();
    
    // Capture output (AuthController uses Response::success which echoes JSON)
    ob_start();
    $controller->login();
    $output = ob_get_clean();
    
    $result = json_decode($output, true);
    
    if ($result && $result['status'] === 'success') {
        // Login successful!
        $userData = $result['data'];
        
        // Store in session for UI Gateway
        $_SESSION['identity_logged_in'] = true;
        $_SESSION['uuid_user'] = $userData['uuid_user'];
        $_SESSION['full_name'] = $userData['full_name'];
        $_SESSION['access_token'] = $userData['access_token'];
        $_SESSION['refresh_token'] = $userData['refresh_token'];
        $_SESSION['app'] = $app;
        
        // PHASE B: Redirect back to application with token
        if (!empty($redirectUri)) {
            // Append token to redirect URI
            $separator = strpos($redirectUri, '?') !== false ? '&' : '?';
            $redirectUrl = $redirectUri . $separator . 'token=' . urlencode($userData['access_token']) 
                         . '&uuid=' . urlencode($userData['uuid_user']);
            header('Location: ' . $redirectUrl);
        } else {
            // Default callback URLs per app
            $callbackUrls = [
                'camat' => '/kantor/camat/auth/callback.php',
                'suratqu' => '/kantor/suratqu/auth/callback.php',
                'docku' => '/kantor/docku/auth/callback.php'
            ];
            
            $callbackUrl = $callbackUrls[$app] ?? '/kantor/' . $app . '/';
            $callbackUrl .= '?token=' . urlencode($userData['access_token']) 
                         . '&uuid=' . urlencode($userData['uuid_user']);
            
            header('Location: ' . $callbackUrl);
        }
        exit;
        
    } else {
        // Login failed
        $errorMsg = $result['message'] ?? 'Login gagal. Periksa username dan password Anda.';
        $_SESSION['login_error'] = $errorMsg;
        header('Location: login.php?app=' . urlencode($app));
        exit;
    }
    
} catch (\Exception $e) {
    // Handle errors gracefully
    error_log('[Identity UI Gateway] Login error: ' . $e->getMessage());
    
    $_SESSION['login_error'] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
    header('Location: login.php?app=' . urlencode($app));
    exit;
}

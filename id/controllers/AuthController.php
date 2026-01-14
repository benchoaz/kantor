<?php
namespace App\Controllers;

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/App.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../models/Audit.php';
require_once __DIR__ . '/../core/RateLimit.php';
require_once __DIR__ . '/../core/Notifier.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\RateLimit;
use App\Core\Notifier;
use App\Models\User;
use App\Models\App;
use App\Models\Session;
use App\Models\Audit;

class AuthController {
    private $userModel;
    private $appModel;
    private $sessionModel;
    private $auditModel;
    private $notifier;

    public function __construct() {
        $this->userModel = new User();
        $this->appModel = new App();
        $this->sessionModel = new Session();
        $this->auditModel = new Audit();
        $this->notifier = new Notifier();
    }

    /**
     * Centralized LOGIN
     * Validates credentials and App authorization.
     */
    public function login() {
        $appId = Request::headers('X-APP-ID');
        $appKey = Request::headers('X-APP-KEY');

        // DEBUG: Log received headers to id_debug.txt
        $debugInfo = date('[Y-m-d H:i:s] ') . "AppId: $appId | KeyLen: " . strlen($appKey) . "\n";
        file_put_contents(__DIR__ . '/../../id_debug.txt', $debugInfo, FILE_APPEND);

        if (!$appId || !$appKey) {
            Response::error("App identification missing ($appId, $appKey)", 401);
        }

        $app = $this->appModel->findByAppIdAndKey($appId, $appKey);
        if (!$app) {
            Response::error("Application ($appId) not authorized or inactive", 403);
        }

        // 2. Validate User Credentials & Rate Limiting
        $username = Request::input('username');
        $password = Request::input('password');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimitKey = 'login_' . $ip . '_' . $username;

        if (!$username || !$password) {
            Response::error("Username and password are required", 400);
        }

        if (!RateLimit::check($rateLimitKey, 5, 15)) {
            $this->auditModel->log('failed_attempt', null, $app['id'], ['username' => $username, 'reason' => 'rate_limited']);
            $this->notifier->alert("Rate Limit Exceeded", "IP $ip exceeded login rate limit for user '$username'", 'warning');
            Response::error("Too many login attempts. Please try again in 15 minutes.", 429);
        }

        $user = $this->userModel->findByUsername($username);

        // Verification logic (using native password_verify)
        if (!$user || !password_verify($password, $user['password_hash'])) {
            RateLimit::increment($rateLimitKey, 15);
            $this->auditModel->log('failed_attempt', $user ? $user['id'] : null, $app['id'], ['username' => $username, 'reason' => 'invalid_credentials']);
            
            // Phase 6: Brute Force Detection
            $recentFailures = $this->auditModel->countRecentFailures($ip, 60);
            if ($recentFailures >= 3) {
                $this->notifier->alert("Brute Force Detection", "Multiple failed login attempts detected from IP $ip for user '$username'. Total: $recentFailures in 60s.", 'critical');
            }

            Response::error("Invalid user credentials", 401);
        }

        // Check if User Status is Active
        if ($user['status'] !== 'active') {
             $this->auditModel->log('failed_attempt', $user['id'], $app['id'], ['username' => $username, 'reason' => 'account_' . $user['status']]);
             Response::error("Account is " . $user['status'] . ". Please contact administrator.", 403);
        }

        // Success! Clear rate limit
        RateLimit::clear($rateLimitKey);
        $this->auditModel->log('login', $user['id'], $app['id']);

        // 3. Issue Token & Create Session
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(32));
        
        // Shortened lifecycles (Minimum Safe Identity)
        $accessExpiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $refreshExpiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        $this->sessionModel->create([
            'user_id' => $user['id'],
            'app_id'  => $app['id'],
            'token_id' => $accessToken,
            'refresh_token' => $refreshToken,
            'device_id' => Request::input('device_id'),
            'device_type' => Request::input('device_type'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => $ip,
            'expires_at' => $refreshExpiresAt // Session record stays valid for refresh duration
        ]);

        $this->userModel->updateLastLogin($user['id']);

        // Return minimal identity info + auth data
        Response::success("Login successful", [
            'uuid_user'  => $user['uuid_user'],
            'full_name'  => $user['full_name'],
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_at' => $accessExpiresAt,
            'scopes'     => json_decode($app['scopes'] ?? '[]')
        ]);
    }

    /**
     * Token VERIFICATION
     * Used by other apps (via /api) to verify if a token is still valid.
     */
    public function verify() {
        // 1. Extract Bearer Token
        $token = Request::bearerToken();
        $appId = Request::headers('X-APP-ID'); // App must identify itself

        if (!$token || !$appId) {
            Response::error("Missing Authorization or X-APP-ID", 401);
        }

        // 2. Security: Internal IP Whitelist for Verification
        $allowedInternalIps = ['127.0.0.1', '::1', '103.xxx.xxx.xxx']; 
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (!in_array($clientIp, $allowedInternalIps)) {
             Response::error("Access denied: Verify API only available for internal whitelisted IPs", 403);
        }

        // 3. Validate App
        $appKey = Request::headers('X-APP-KEY'); 
        $app = $this->appModel->findByAppIdAndKey($appId, $appKey);
        if (!$app) {
            Response::error("Internal request not authorized", 403);
        }

        // 4. Find and Validate Session
        $session = $this->sessionModel->findValidSession($token, $app['id']);

        if (!$session) {
            Response::error("Invalid, expired, or revoked token", 401);
        }

        // Update last used timestamp for security monitoring
        $this->sessionModel->updateLastUsed($session['id']);

        // RETURN MINIMAL DATA: Identity only
        Response::success("Token is valid", [
            'uuid_user' => $session['uuid_user'],
            'scopes'    => json_decode($session['scopes'] ?? '[]'),
            'authorized_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Token REFRESH
     * Issue new Access Token using Refresh Token.
     */
    public function refresh() {
        $refreshToken = Request::input('refresh_token');
        if (!$refreshToken) {
            Response::error("Refresh token required", 400);
        }

        // 1. Validate Refresh Token
        $session = $this->sessionModel->findValidByRefreshToken($refreshToken);
        if (!$session) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $this->auditModel->log('failed_attempt', null, null, ['reason' => 'invalid_refresh_token']);
            $this->notifier->alert("Suspicious Refresh Attempt", "Invalid or expired refresh token used from IP $ip.", 'warning');
            Response::error("Invalid or expired refresh token", 401);
        }

        // 2. Rotation Policy: Create NEW token pair, Revoke OLD session
        $this->sessionModel->revokeByRefreshToken($refreshToken);
        $this->auditModel->log('token_refresh', $session['user_id'], $session['app_id']);

        $newAccessToken = bin2hex(random_bytes(32));
        $newRefreshToken = bin2hex(random_bytes(32));
        $accessExpiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $refreshExpiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        $this->sessionModel->create([
            'user_id' => $session['user_id'],
            'app_id'  => $session['app_id'],
            'token_id' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'device_id' => $session['device_id'],
            'device_type' => $session['device_type'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'expires_at' => $refreshExpiresAt
        ]);

        Response::success("Token refreshed successfully", [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expires_at' => $accessExpiresAt
        ]);
    }

    /**
     * Global REVOKE
     * Forcibly closes a session.
     */
    public function logout() {
        $token = Request::bearerToken();
        if ($token) {
            $this->sessionModel->revoke($token);
            $this->auditModel->log('logout');
        }
        Response::success("Session revoked successfully");
    }
}

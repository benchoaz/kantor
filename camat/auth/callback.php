<?php
/**
 * PHASE B PILOT - Camat Auth Callback Handler
 * 
 * Purpose: Receive token from Identity Module and setup session
 * 
 * Flow:
 * 1. Receive token + uuid from Identity redirect
 * 2. Verify token with Identity Module
 * 3. Map uuid_user → legacy user_id  
 * 4. Setup legacy session (CRITICAL for disposisi compatibility)
 * 5. Redirect to dashboard
 * 
 * @author Phase B Pilot Team
 * @date 2026-01-10
 */

define('APP_INIT', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/identity_helper.php';

startSession();

// STEP 1: Get token and UUID from Identity callback
$token = $_GET['token'] ?? null;
$uuid = $_GET['uuid'] ?? null;

if (!$token || !$uuid) {
    // Invalid callback - redirect to Identity login
    $identityUrl = IDENTITY_URL ?? 'https://id.sidiksae.my.id';
    header('Location: ' . $identityUrl . '/auth/login.php?app=camat');
    exit;
}

// STEP 2: Verify token with Identity Module
try {
    $verified = verifyTokenWithIdentity($token);
    
    if (!$verified || $verified['uuid_user'] !== $uuid) {
        // Token invalid or UUID mismatch
        error_log('[Camat Callback] Token verification failed for UUID: ' . $uuid);
        
        // Clear any existing session
        session_destroy();
        
        // Redirect back to Identity login
        header('Location: ' . IDENTITY_URL . '/auth/login.php?app=camat&error=invalid_token');
        exit;
    }
    
} catch (Exception $e) {
    error_log('[Camat Callback] Exception during token verification: ' . $e->getMessage());
    
    // PHASE B SAFETY: Show user-friendly error
    die('
        <!DOCTYPE html>
        <html>
        <head><title>Login Error</title></head>
        <body style="font-family: sans-serif; text-align: center; padding: 50px;">
            <h2>⚠️ Terjadi Kesalahan</h2>
            <p>Tidak dapat memverifikasi identitas Anda.</p>
            <p style="color: #666;">Identity Module mungkin sedang sibuk. Silakan coba lagi.</p>
            <a href="' . IDENTITY_URL . '/auth/login.php?app=camat" style="color: #667eea;">← Kembali ke Login</a>
        </body>
        </html>
    ');
}

// STEP 3: Map UUID → Legacy User ID
try {
    $legacyUser = getUserByUUID($uuid);
    
    if (!$legacyUser) {
        // User exists in Identity but not in legacy DB
        error_log('[Camat Callback] User not found in legacy DB for UUID: ' . $uuid);
        
        die('
            <!DOCTYPE html>
            <html>
            <head><title>User Not Found</title></head>
            <body style="font-family: sans-serif; text-align: center; padding: 50px;">
                <h2>❌ User Tidak Ditemukan</h2>
                <p>Akun Anda terdaftar di Identity Module, tetapi belum tersinkronisasi dengan sistem Camat.</p>
                <p style="color: #666;">Silakan hubungi administrator untuk sinkronisasi akun.</p>
                <p style="font-size: 12px; color: #999;">UUID: ' . htmlspecialchars($uuid) . '</p>
            </body>
            </html>
        ');
    }
    
} catch (Exception $e) {
    error_log('[Camat Callback] Database error: ' . $e->getMessage());
    die('Database error. Please contact administrator.');
}

// STEP 4: Setup Session
// CRITICAL: Use SAME session structure as old login.php
// This ensures disposisi and other features work without modification

session_regenerate_id(true);

// Identity-based session
$_SESSION['auth_token'] = $token;
$_SESSION['uuid_user'] = $uuid;
$_SESSION['auth_method'] = 'identity'; // Flag untuk tracking

// Structured user data (BEST PRACTICE)
$_SESSION['user'] = [
    'id' => $legacyUser['id'],
    'uuid_user' => $uuid,
    'username' => $legacyUser['username'],
    'nama' => $legacyUser['nama'],
    'role' => $legacyUser['role'],      // Identifiers: pimpinan, sekcam
    'jabatan' => $legacyUser['jabatan'] ?? 'Staff'
];

// Legacy session (BACKWARD COMPATIBILITY - remove in Phase C)
$_SESSION['user_id'] = $legacyUser['id'];
$_SESSION['username'] = $legacyUser['username'];
$_SESSION['name'] = $legacyUser['nama']; // Note: 'nama' from DB, exposed as 'name'
$_SESSION['role'] = $legacyUser['role'];
$_SESSION['jabatan'] = $legacyUser['jabatan'] ?? 'Staff'; // For disposisi
$_SESSION['last_activity'] = time();
$_SESSION['login_time'] = time();

// CSRF token
if (ENABLE_CSRF_PROTECTION) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

// Optional: Store full verified user data for debugging
$_SESSION['identity_verified_data'] = $verified;

// STEP 5: Audit log (optional)
require_once __DIR__ . '/audit_logger.php';
logIdentityLogin($uuid, $legacyUser['username']); // STEP B4

// STEP 6: Redirect to dashboard
header('Location: /kantor/camat/dashboard.php');
exit;

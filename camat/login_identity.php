<?php
/**
 * PHASE B PILOT - Camat Login Refactor
 * 
 * Purpose: Redirect to Identity Module for authentication
 * 
 * BACKWARD COMPATIBILITY:
 * - Old login.php renamed to login.php.bak
 * - This file redirects to Identity Module
 * - If Identity unreachable, shows friendly error
 * 
 * @author Phase B Pilot Team  
 * @date 2026-01-10
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'auth/identity_helper.php';

startSession();

// STEP 1: Check if already logged in
if (isLoggedIn()) {
    // Already have session, go to dashboard
    redirect('dashboard.php');
}

// STEP 2: Check if Identity Module is reachable (SAFETY)
$identityReachable = isIdentityModuleReachable();

if (!$identityReachable) {
    // PHASE B SAFETY GUARD: Identity Module down
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Camat</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background-STEP: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .error-container {
                background: white;
                border-radius: 12px;
                padding: 40px;
                max-width: 500px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 { color: #e53e3e; margin-bottom: 20px; }
            p { color: #666; line-height: 1.6; }
            .retry-btn {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 24px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                transition: 0.3s;
            }
            .retry-btn:hover { background: #5568d3; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>⚠️ Layanan Login Tidak Tersedia</h1>
            <p>Identity Module  sedang tidak dapat dijangkau. Ini mungkin karena:</p>
            <ul style="text-align: left; color: #666;">
                <li>Server sedang maintenance</li>
                <li>Koneksi jaringan bermasalah</li>
                <li>Layanan sedang sibuk</li>
            </ul>
            <p><strong>Silakan coba lagi dalam beberapa saat.</strong></p>
            <a href="login.php" class="retry-btn">🔄 Coba Lagi</a>
            <p style="margin-top: 30px; font-size: 12px; color: #999;">
                Jika masalah berlanjut, hubungi administrator sistem.
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// STEP 3: Identity Module reachable - Redirect to login
$identityUrl = IDENTITY_URL ?? 'https://id.sidiksae.my.id';
$callbackUrl = urlencode(BASE_URL . '/kantor/camat/auth/callback.php');

// Redirect ke Identity login
$loginUrl = $identityUrl . '/auth/login.php?app=camat&redirect_uri=' . $callbackUrl;

header('Location: ' . $loginUrl);
exit;

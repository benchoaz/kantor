<?php
/**
 * Camat Leadership Panel - Configuration
 * Konfigurasi aplikasi untuk camat.sidiksae.my.id
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

// ==========================================
// API CONFIGURATION
// ==========================================
// Alamat server API pusat
// Alamat server API pusat (Ganti domain saat live)
// API Configuration
// PRODUCTION DOMAIN
define('API_BASE_URL', 'https://api.sidiksae.my.id'); // No trailing slash
define('API_VERSION', ''); 
define('IDENTITY_URL', 'https://id.sidiksae.my.id'); // Centralized Identity Service
define('IDENTITY_VERSION', 'v1'); 

/**
 * API Credentials
 * Pastikan nilai ini sama dengan yang ada di tabel `api_clients` database sidiksae_api
 */
define('API_KEY', 'sk_live_camat_c4m4t2026');
define('CLIENT_ID', 'camat');
define('CLIENT_SECRET', 'camat_secret_key_123');

// ==========================================
// SESSION CONFIGURATION
// ==========================================
define('SESSION_TIMEOUT', 7200); // 2 jam (dalam detik)
define('SESSION_NAME', 'CAMAT_SESSION');

// ==========================================
// APPLICATION SETTINGS
// ==========================================
define('APP_NAME', 'C A M A T');
define('APP_VERSION', '1.0.0');
define('DEFAULT_TIMEZONE', 'Asia/Makassar');

// ==========================================
// SECURITY SETTINGS
// ==========================================
define('ENABLE_CSRF_PROTECTION', false);
define('CSRF_TOKEN_NAME', 'csrf_token');

// ==========================================
// TELEGRAM NOTIFICATION
// ==========================================
define('TELEGRAM_ENABLED', true);
define('TELEGRAM_INFO_TEXT', 'Notifikasi dikirim via Telegram');

// ==========================================
// ROLES (Identifier sesuai sistem pusat)
// ==========================================
define('ROLE_PIMPINAN', 'pimpinan');
define('ROLE_SEKCAM', 'sekcam');

// ==========================================
// SETUP & INITIALIZATION
// ==========================================
date_default_timezone_set(DEFAULT_TIMEZONE);

// Session security configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
session_name(SESSION_NAME);

/**
 * Error Reporting
 * Set ke 0 (Off) jika sudah di production (cPanel)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1); // 0 = Sembunyikan error di layar, 1 = Tampilkan
ini_set('log_errors', 1);

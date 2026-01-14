<?php
// includes/auth.php
session_start();

// Konfigurasi Timeout (dalam detik) - 15 Menit = 900 detik
define('SESSION_TIMEOUT', 900);

/**
 * Cek Sesi Timeout Otomatis
 */
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Sesi Expired
        session_unset();
        session_destroy();
        
        // Redirect ke login dengan pesan timeout
        $script_name = $_SERVER['SCRIPT_NAME'];
        $depth = substr_count(dirname($script_name), '/');
        $path_to_root = str_repeat('../', $depth);
        
        header("Location: {$path_to_root}login.php?msg=timeout");
        exit;
    }
    // Update timestamp aktivitas terakhir
    $_SESSION['last_activity'] = time();
}

/**
 * Cek apakah user sudah login
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect ke login jika belum login
 */
function require_login() {
    if (!is_logged_in()) {
        // Detect script location and build correct path to login.php
        $script_name = $_SERVER['SCRIPT_NAME'];
        $depth = substr_count(dirname($script_name), '/');
        
        // Build relative path back to root (where login.php is)
        $path_to_root = str_repeat('../', $depth);
        
        header("Location: {$path_to_root}login.php");
        exit;
    }
}

/**
 * Cek role user
 */
function has_role($roles) {
    if (!is_logged_in()) return false;
    
    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles);
    }
    
    return $_SESSION['role'] === $roles;
}

/**
 * Cek apakah user memiliki role manajemen/struktural
 * Digunakan untuk menampilkan menu Disposisi, Laporan, dsb.
 */
function is_management_role() {
    if (!is_logged_in()) return false;
    $management_roles = ['admin', 'kasi_pemerintahan', 'kasi_pem', 'sekcam', 'pimpinan'];
    return in_array($_SESSION['role'], $management_roles);
}

/**
 * Require specific role
 */
function require_role($roles) {
    require_login();
    if (!has_role($roles)) {
        echo "Akses Ditolak: Anda tidak memiliki izin untuk halaman ini.";
        exit;
    }
}
?>

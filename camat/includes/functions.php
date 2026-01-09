<?php
/**
 * Helper Functions
 * Fungsi-fungsi pembantu untuk aplikasi
 */

/**
 * Format tanggal ke bahasa Indonesia
 */
function formatTanggal($date, $withTime = false) {
    if (empty($date)) {
        return '-';
    }
    
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $bulan[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    $formatted = $day . ' ' . $month . ' ' . $year;
    
    if ($withTime) {
        $formatted .= ' ' . date('H:i', $timestamp);
    }
    
    return $formatted;
}

/**
 * Format tanggal relatif (hari ini, kemarin, dll)
 */
function formatTanggalRelatif($date) {
    $timestamp = strtotime($date);
    $today = strtotime('today');
    $yesterday = strtotime('yesterday');
    
    if ($timestamp >= $today) {
        return 'Hari ini, ' . date('H:i', $timestamp);
    } elseif ($timestamp >= $yesterday) {
        return 'Kemarin, ' . date('H:i', $timestamp);
    } else {
        return formatTanggal($date, true);
    }
}

/**
 * Render badge prioritas surat
 */
function renderPriorityBadge($priority) {
    $badges = [
        'sangat_penting' => '<span class="badge badge-critical">Sangat Penting</span>',
        'penting' => '<span class="badge badge-warning">Penting</span>',
        'biasa' => '<span class="badge badge-normal">Biasa</span>'
    ];
    
    return $badges[$priority] ?? '<span class="badge badge-normal">-</span>';
}

/**
 * Render badge status
 */
function renderStatusBadge($status, $deadline = null) {
    // Jika ada deadline, cek apakah sudah lewat atau mendekati
    if ($deadline) {
        $deadlineTime = strtotime($deadline);
        $now = time();
        $diff = $deadlineTime - $now;
        $hours = $diff / 3600;
        
        if ($diff < 0) {
            // Lewat deadline
            return '<span class="badge badge-critical">Lewat Waktu</span>';
        } elseif ($hours <= 24) {
            // H-1 atau kurang
            return '<span class="badge badge-warning">Deadline H-1</span>';
        }
    }
    
    // Status badge biasa
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'proses' => '<span class="badge badge-info">Proses</span>',
        'selesai' => '<span class="badge badge-success">Selesai</span>',
        'ditolak' => '<span class="badge badge-critical">Ditolak</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge badge-normal">' . htmlspecialchars($status) . '</span>';
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for HTML
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    startSession();
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    startSession();
    
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

/**
 * Render flash message HTML
 */
function renderFlashMessage() {
    $flash = getFlashMessage();
    
    if (!$flash) {
        return '';
    }
    
    $typeClass = [
        'success' => 'flash-success',
        'error' => 'flash-error',
        'warning' => 'flash-warning',
        'info' => 'flash-info'
    ];
    
    $class = $typeClass[$flash['type']] ?? 'flash-info';
    
    return '<div class="flash-message ' . $class . '" onclick="this.style.display=\'none\'">' 
        . e($flash['message']) 
        . '</div>';
}

/**
 * Get role display name
 */
function getRoleDisplayName($role) {
    $roles = [
        'pimpinan' => 'Pimpinan',
        'sekcam' => 'Sekretaris Camat'
    ];
    
    return $roles[$role] ?? ucfirst($role);
}

/**
 * Check if current page is active
 */
function isActivePage($page) {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    return $currentPage === $page ? 'active' : '';
}

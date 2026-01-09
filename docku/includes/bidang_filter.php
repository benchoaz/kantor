<?php
// includes/helpers.php - Additional helper function
// ... (existing helpers remain)

/**
 * Get bidang filter SQL for current user
 * Returns WHERE clause for filtering by user's bidang
 * 
 * @param PDO $pdo Database connection
 * @param string $table_alias Alias for kegiatan table (e.g., 'k')
 * @return string SQL WHERE clause (empty string if full access)
 */
function get_user_bidang_filter($pdo, $table_alias = 'k') {
    // Admin and Pimpinan roles have full access
    if ($_SESSION['role'] === 'admin') {
        return '';
    }
    
    // Check if user has jabatan that grants full access
    $full_access_positions = ['Camat', 'Sekretaris Camat'];
    $stmt = $pdo->prepare("SELECT jabatan, bidang_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user && in_array($user['jabatan'], $full_access_positions)) {
        return ''; // Full access
    }
    
    // If user has assigned bidang, filter by it
    if ($user && $user['bidang_id']) {
        return " AND {$table_alias}.bidang_id = " . (int)$user['bidang_id'];
    }
    
    // No bidang assigned and not full access role - show nothing
    return " AND 1=0";
}

/**
 * Check if user has access to specific bidang
 * 
 * @param PDO $pdo Database connection
 * @param int $bidang_id Bidang ID to check
 * @return bool True if user has access
 */
function user_has_bidang_access($pdo, $bidang_id) {
    // Admin has full access
    if ($_SESSION['role'] === 'admin') {
        return true;
    }
    
    // Check user's bidang and jabatan
    $stmt = $pdo->prepare("SELECT jabatan, bidang_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    // Full access positions
    $full_access_positions = ['Camat', 'Sekretaris Camat'];
    if (in_array($user['jabatan'], $full_access_positions)) {
        return true;
    }
    
    // Check if bidang matches
    return ($user['bidang_id'] == $bidang_id);
}

/**
 * Get user's assigned bidang_id
 * 
 * @param PDO $pdo Database connection
 * @return int|null Bidang ID or null if full access
 */
function get_user_bidang_id($pdo) {
    // Admin has no specific bidang
    if ($_SESSION['role'] === 'admin') {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT jabatan, bidang_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return null;
    }
    
    // Full access positions
    $full_access_positions = ['Camat', 'Sekretaris Camat'];
    if (in_array($user['jabatan'], $full_access_positions)) {
        return null; // Null means full access
    }
    
    return $user['bidang_id'];
}
?>

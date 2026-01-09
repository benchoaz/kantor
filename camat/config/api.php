<?php
// FILE: config/api.php
/**
 * API Configuration - SidikSae Integration
 * 
 * PURPOSE: Define API endpoints & credentials for communication with central API
 * 
 * RULES:
 * - STRICTLY NO database connections
 * - STRICTLY NO session management
 * - STRICTLY NO business logic
 * - Only constant definitions
 * 
 * USAGE:
 * This file is included by api_helper.php and ApiClient.php
 */

// ==========================================
// BASE CONFIGURATION
// ==========================================
if (!defined('API_BASE_URL')) {
    define('API_BASE_URL', 'https://sidiksae.my.id/api');
}

if (!defined('API_TIMEOUT')) {
    define('API_TIMEOUT', 15); // Request timeout in seconds
}

// ==========================================
// API CREDENTIALS
// ==========================================
if (!defined('APP_CLIENT_ID')) {
    define('APP_CLIENT_ID', 'camat');
}

if (!defined('APP_CLIENT_SECRET')) {
    define('APP_CLIENT_SECRET', 'camat_secret_key_123');
}

if (!defined('APP_API_KEY')) {
    define('APP_API_KEY', 'sk_live_camat_c4m4t2026');
}

// ==========================================
// ENDPOINTS: AUTHENTICATION
// ==========================================
if (!defined('ENDPOINT_LOGIN')) {
    define('ENDPOINT_LOGIN', '/auth/login');
}

if (!defined('ENDPOINT_LOGOUT')) {
    define('ENDPOINT_LOGOUT', '/auth/logout');
}

// ==========================================
// ENDPOINTS: SURAT (Letter Management)
// ==========================================
// Get list of incoming letters for Pimpinan/Camat
// Usage: GET {API_BASE_URL}{ENDPOINT_SURAT_MASUK}?page=1&limit=20
if (!defined('ENDPOINT_SURAT_MASUK')) {
    define('ENDPOINT_SURAT_MASUK', '/pimpinan/surat-masuk');
}

// Get detail of a specific letter
// Usage: GET {API_BASE_URL}{ENDPOINT_SURAT_DETAIL}/{id}
// Example: /surat/detail/36
//
// IMPORTANT: Changed from '/surat' to '/surat/detail' for clarity
// - '/surat' is ambiguous (could mean list)
// - '/surat/detail' is explicit (clearly means get one item)
if (!defined('ENDPOINT_SURAT_DETAIL')) {
    define('ENDPOINT_SURAT_DETAIL', '/surat');
}

// ==========================================
// ENDPOINTS: DISPOSISI (Disposition Management)
// ==========================================
// Get list of dispositions for monitoring
// Usage: GET {API_BASE_URL}{ENDPOINT_DISPOSISI_LIST}
if (!defined('ENDPOINT_DISPOSISI_LIST')) {
    define('ENDPOINT_DISPOSISI_LIST', '/pimpinan/disposisi');
}

// Create new disposition
// Usage: POST {API_BASE_URL}{ENDPOINT_DISPOSISI_CREATE}
// Body: {surat_id, tujuan, catatan, deadline, sifat_disposisi}
if (!defined('ENDPOINT_DISPOSISI_CREATE')) {
    define('ENDPOINT_DISPOSISI_CREATE', '/disposisi');
}

// Get detail of a specific disposition
// Usage: GET {API_BASE_URL}{ENDPOINT_DISPOSISI_DETAIL}/{id}
if (!defined('ENDPOINT_DISPOSISI_DETAIL')) {
    define('ENDPOINT_DISPOSISI_DETAIL', '/disposisi/detail');
}

// Cancel disposition (Soft Delete)
// Usage: POST {API_BASE_URL}{ENDPOINT_DISPOSISI_CANCEL}
// Body: {id, alasan}
if (!defined('ENDPOINT_DISPOSISI_CANCEL')) {
    define('ENDPOINT_DISPOSISI_CANCEL', '/pimpinan/disposisi/cancel');
}

// Update/Correct disposition
// Usage: POST {API_BASE_URL}{ENDPOINT_DISPOSISI_UPDATE}
// Body: {id, tujuan, catatan, deadline, sifat_disposisi}
if (!defined('ENDPOINT_DISPOSISI_UPDATE')) {
    define('ENDPOINT_DISPOSISI_UPDATE', '/pimpinan/disposisi/update');
}

// Legacy compatibility (will be deprecated)
if (!defined('ENDPOINT_DISPOSISI')) {
    define('ENDPOINT_DISPOSISI', '/disposisi');
}

// ==========================================
// ENDPOINTS: DASHBOARD & UTILITIES
// ==========================================
// Get dashboard statistics for Pimpinan
if (!defined('ENDPOINT_DASHBOARD')) {
    define('ENDPOINT_DASHBOARD', '/pimpinan/dashboard');
}

// Get list of available disposition targets (users/units)
if (!defined('ENDPOINT_DAFTAR_TUJUAN')) {
    define('ENDPOINT_DAFTAR_TUJUAN', '/pimpinan/daftar-tujuan-disposisi');
}

/**
 * ==========================================
 * API CONTRACT EXPECTATIONS
 * ==========================================
 * 
 * All API responses MUST follow this structure:
 * 
 * SUCCESS (HTTP 200/201):
 * {
 *   "success": true,
 *   "message": "Data berhasil diambil",
 *   "data": {...}
 * }
 * 
 * ERROR (HTTP 4xx/5xx):
 * {
 *   "success": false,
 *   "message": "Error description",
 *   "errors": {...}
 * }
 * 
 * HTTP Status Codes:
 * - 200 OK: Success
 * - 201 Created: Resource created
 * - 400 Bad Request: Invalid input
 * - 401 Unauthorized: Invalid/expired token
 * - 404 Not Found: Resource not found
 * - 500 Server Error: Internal error
 * 
 * ⚠️ CRITICAL: Backend MUST NOT return:
 * - HTTP 200 with success: false (use proper 4xx/5xx instead)
 * - Empty data without error message
 * - Inconsistent data structure between list vs detail
 */

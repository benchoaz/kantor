<?php
/**
 * PHASE B PILOT - STEP B4: Audit Logger
 * 
 * Purpose: Consistent, audit-ready logging for Identity integration
 * 
 * Format: [TIMESTAMP] [APP] [UUID] [ACTION] [IP] [DETAILS]
 * 
 * @author Phase B Pilot Team
 * @date 2026-01-10
 */

/**
 * Log Identity-related events for audit trail
 * 
 * @param string $action Action identifier (e.g., 'login_success', 'token_invalid')
 * @param array $context Additional context data
 * @param string $level Log level: INFO, WARNING, ERROR
 */
function logIdentityEvent($action, $context = [], $level = 'INFO') {
    $logFile = __DIR__ . '/../logs/identity_audit.log';
    
    // Ensure log directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Build log entry
    $timestamp = date('Y-m-d H:i:s');
    $app = 'camat';
    $uuid = $_SESSION['uuid_user'] ?? 'unknown';
    $username = $_SESSION['username'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Combine context into string
    $details = [];
    foreach ($context as $key => $value) {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $details[] = "$key=" . substr($value, 0, 100); // Limit length
    }
    $detailsStr = implode(', ', $details);
    
    $logLine = sprintf(
        "[%s] [%s] [%s] [%s] [%s] [%s] %s\n",
        $timestamp,
        $level,
        $app,
        $uuid,
        $username,
        $ip,
        $action
    );
    
    if ($detailsStr) {
        $logLine = rtrim($logLine) . " | $detailsStr\n";
    }
    
    // Write to log file
    error_log($logLine, 3, $logFile);
    
    // Also log to PHP error log for critical events
    if ($level === 'ERROR') {
        error_log("[Identity Audit] $action | UUID: $uuid | $detailsStr");
    }
}

/**
 * Log successful login via Identity
 */
function logIdentityLogin($uuid, $username) {
    logIdentityEvent('login_identity_success', [
        'uuid' => $uuid,
        'username' => $username,
        'method' => 'identity_module'
    ], 'INFO');
}

/**
 * Log token validation failure
 */
function logTokenInvalid($reason = 'unknown') {
    logIdentityEvent('token_invalid', [
        'reason' => $reason
    ], 'WARNING');
}

/**
 * Log token verification timeout
 */
function logTokenTimeout() {
    logIdentityEvent('token_timeout', [
        'action' => 'graceful_allow',
        'note' => 'Identity Module unreachable, allowing access'
    ], 'WARNING');
}

/**
 * Log forced relogin
 */
function logForceRelogin($reason) {
    logIdentityEvent('force_relogin', [
        'reason' => $reason
    ], 'INFO');
}

/**
 * Log edge case detection
 */
function logEdgeCase($case, $details = []) {
    logIdentityEvent('edge_case_' . $case, $details, 'WARNING');
}

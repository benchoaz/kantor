<?php
/**
 * DEPLOYMENT VERIFICATION TEST
 * Test role-based disposisi components
 * 
 * Usage: https://api.sidiksae.my.id/test_deployment.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/core/DisposisiFlowValidator.php';

$results = [];

// Test 1: Flow Validator Class Loaded
try {
    $results['flow_validator_loaded'] = class_exists('DisposisiFlowValidator') ? 'PASS' : 'FAIL';
} catch (Exception $e) {
    $results['flow_validator_loaded'] = 'FAIL: ' . $e->getMessage();
}

// Test 2: Valid Flow (pimpinan → sekcam)
try {
    $flow = DisposisiFlowValidator::validateFlow('pimpinan', 'sekcam');
    $results['valid_flow_pimpinan_sekcam'] = $flow['valid'] ? 'PASS' : 'FAIL';
} catch (Exception $e) {
    $results['valid_flow_pimpinan_sekcam'] = 'FAIL: ' . $e->getMessage();
}

// Test 3: Valid Flow (pimpinan → kasi)
try {
    $flow = DisposisiFlowValidator::validateFlow('pimpinan', 'kasi');
    $results['valid_flow_pimpinan_kasi'] = $flow['valid'] ? 'PASS' : 'FAIL';
} catch (Exception $e) {
    $results['valid_flow_pimpinan_kasi'] = 'FAIL: ' . $e->getMessage();
}

// Test 4: Invalid Flow (pimpinan → staff) - Should FAIL
try {
    $flow = DisposisiFlowValidator::validateFlow('pimpinan', 'staff');
    $results['invalid_flow_blocked'] = !$flow['valid'] ? 'PASS' : 'FAIL (should block)';
} catch (Exception $e) {
    $results['invalid_flow_blocked'] = 'FAIL: ' . $e->getMessage();
}

// Test 5: Valid Flow (sekcam → kasi)
try {
    $flow = DisposisiFlowValidator::validateFlow('sekcam', 'kasi');
    $results['valid_flow_sekcam_kasi'] = $flow['valid'] ? 'PASS' : 'FAIL';
} catch (Exception $e) {
    $results['valid_flow_sekcam_kasi'] = 'FAIL: ' . $e->getMessage();
}

// Test 6: Invalid Flow (staff → anyone) - Should FAIL
try {
    $flow = DisposisiFlowValidator::validateFlow('staff', 'kasi');
    $results['staff_cannot_send'] = !$flow['valid'] ? 'PASS' : 'FAIL (staff should not send)';
} catch (Exception $e) {
    $results['staff_cannot_send'] = 'FAIL: ' . $e->getMessage();
}

// Test 7: Permission Check - Staff cannot CREATE
try {
    $perm = DisposisiFlowValidator::can('staff', 'CREATE');
    $results['staff_create_blocked'] = !$perm['allowed'] ? 'PASS' : 'FAIL (should block)';
} catch (Exception $e) {
    $results['staff_create_blocked'] = 'FAIL: ' . $e->getMessage();
}

// Test 8: Permission Check - Pimpinan can CREATE
try {
    $perm = DisposisiFlowValidator::can('pimpinan', 'CREATE');
    $results['pimpinan_create_allowed'] = $perm['allowed'] ? 'PASS' : 'FAIL';
} catch (Exception $e) {
    $results['pimpinan_create_allowed'] = 'FAIL: ' . $e->getMessage();
}

// Test 9: Audit Logger Class Loaded
try {
    require_once __DIR__ . '/core/DisposisiAuditLogger.php';
    $results['audit_logger_loaded'] = class_exists('DisposisiAuditLogger') ? 'PASS' : 'FAIL';
} catch (Exception $e) {
    $results['audit_logger_loaded'] = 'FAIL: ' . $e->getMessage();
}

// Test 10: Controller Updated
try {
    $controller_file = __DIR__ . '/controllers/DisposisiController.php';
    $controller_content = file_get_contents($controller_file);
    $has_flow_validator = strpos($controller_content, 'DisposisiFlowValidator') !== false;
    $has_audit_logger = strpos($controller_content, 'DisposisiAuditLogger') !== false;
    $has_from_role = strpos($controller_content, 'from_role') !== false;
    $has_to_role = strpos($controller_content, 'to_role') !== false;
    
    $results['controller_has_flow_validator'] = $has_flow_validator ? 'PASS' : 'FAIL';
    $results['controller_has_audit_logger'] = $has_audit_logger ? 'PASS' : 'FAIL';
    $results['controller_has_role_fields'] = ($has_from_role && $has_to_role) ? 'PASS' : 'FAIL';
} catch (Exception $e) {
    $results['controller_check'] = 'FAIL: ' . $e->getMessage();
}

// Summary
$total_tests = count($results);
$passed = count(array_filter($results, function($r) { return $r === 'PASS'; }));
$failed = $total_tests - $passed;

$summary = [
    'deployment_status' => ($failed === 0) ? 'SUCCESS' : 'PARTIAL',
    'total_tests' => $total_tests,
    'passed' => $passed,
    'failed' => $failed,
    'pass_rate' => round(($passed / $total_tests) * 100, 2) . '%'
];

echo json_encode([
    'summary' => $summary,
    'results' => $results,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);

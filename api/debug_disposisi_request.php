<?php
// Debug script - Save to api/debug_disposisi_request.php
// Call this BEFORE DisposisiController to see what's received

header('Content-Type: application/json');

$debug_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'php_input' => file_get_contents("php://input"),
    'post_data' => $_POST,
    'files' => $_FILES,
    'get_data' => $_GET,
    'headers' => getallheaders()
];

// Save to log file
$log_file = __DIR__ . '/debug_request.log';
file_put_contents($log_file, json_encode($debug_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n", FILE_APPEND);

// Also output
echo json_encode([
    'debug' => 'Request logged',
    'received_data' => $debug_data
], JSON_PRETTY_PRINT);

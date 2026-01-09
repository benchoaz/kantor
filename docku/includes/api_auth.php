<?php
// includes/api_auth.php

// Function to validate inbound API Key
function validateApiKey($pdo) {
    // 1. Check for Header or Query Param
    $apiKey = null;
    $headers = apache_request_headers();
    
    // Normalize headers (sometimes case-sensitive depending on server config)
    $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
    
    if (isset($normalizedHeaders['x-api-key'])) {
        $apiKey = $normalizedHeaders['x-api-key'];
    } elseif (isset($_GET['api_key'])) {
        $apiKey = $_GET['api_key'];
    }

    if (!$apiKey) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Missing API Key']);
        exit;
    }

    // 2. Validate against Database
    // We bind the apiKey to prevent SQL Injection
    $stmt = $pdo->prepare("SELECT id, label FROM integrasi_config WHERE inbound_key = ? AND is_active = 1 LIMIT 1");
    
    $stmt->execute([$apiKey]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid API Key']);
        exit;
    }
    
    // Return connection info (e.g. source Label)
    return $result;
}
?>

<?php
// api/v1/disposisi/receive.php

// CORS Headers for cross-origin API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../../config/database.php';
require_once '../../../includes/api_auth.php';

// Handle GET request for endpoint info (connectivity test)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Optional: Allow unauthenticated GET for basic info
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'BESUK SAE Integration API - Disposisi Endpoint',
        'version' => '1.0',
        'endpoint' => '/api/v1/disposisi/receive.php',
        'methods' => ['POST'],
        'authentication' => 'X-API-KEY header required',
        'documentation' => '/modules/integrasi/tutorial.php',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// 1. Authenticate Request
// validateApiKey now accepts $pdo from config/database.php
$source = validateApiKey($pdo);

// 2. Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Method Not Allowed',
        'allowed_methods' => ['POST'],
        'hint' => 'Use POST method with JSON payload'
    ]);
    exit;
}

// 3. Get Payload
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid JSON Payload',
        'hint' => 'Ensure request body contains valid JSON',
        'received_content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not specified'
    ]);
    exit;
}

// 4. Validate Required Fields
$requiredFields = ['external_id', 'perihal'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'message' => "Missing required field: $field",
            'required_fields' => $requiredFields,
            'received_fields' => array_keys($data)
        ]);
        exit;
    }
}

// 5. Default mapping logic
$targetUserId = 1; 

if (!empty($data['target_username'])) {
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmtUser->execute([$data['target_username']]);
    if ($rowUser = $stmtUser->fetch(PDO::FETCH_ASSOC)) {
        $targetUserId = $rowUser['id'];
    }
}

// 6. DB Transaction (Integrity check)
$pdo->beginTransaction();

try {
    // A. Check duplicates
    $stmtCheck = $pdo->prepare("SELECT id FROM disposisi WHERE external_id = ? LIMIT 1");
    $stmtCheck->execute([$data['external_id']]);
    if ($stmtCheck->fetchColumn()) {
        throw new Exception("Disposisi with external_id '{$data['external_id']}' already exists.");
    }

    // B. Insert into `disposisi`
    $payloadHash = hash('sha256', $rawInput); // SPBE Integrity Check
    $tglDisposisi = $data['tgl_disposisi'] ?? date('Y-m-d H:i:s');
    $instruksi = $data['instruksi'] ?? '';

    $stmtInsert = $pdo->prepare("INSERT INTO disposisi (external_id, perihal, instruksi, tgl_disposisi, source_json, payload_hash) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtInsert->execute([$data['external_id'], $data['perihal'], $instruksi, $tglDisposisi, $rawInput, $payloadHash]);
    
    $disposisiId = $pdo->lastInsertId();

    // C. Insert into `disposisi_penerima`
    $stmtRecipient = $pdo->prepare("INSERT INTO disposisi_penerima (disposisi_id, user_id, status) VALUES (?, ?, 'baru')");
    $stmtRecipient->execute([$disposisiId, $targetUserId]);
    
    // D. Trigger Notifications (Stub)
    // logNotification($pdo, $disposisiId, $targetUserId, 'internal');

    $pdo->commit();
    
    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Disposisi received successfully',
        'data' => [
            'besuksae_id' => $disposisiId,
            'assigned_to' => $targetUserId
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500); 
    if (strpos($e->getMessage(), 'already exists') !== false) {
        http_response_code(409);
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

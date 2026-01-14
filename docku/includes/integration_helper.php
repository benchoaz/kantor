<?php
// includes/integration_helper.php

/**
 * Trigger Outbound Webhook when disposition is executed
 */
function triggerOutboundWebhook($pdo, $disposisiId) {
    // 1. Get Disposisi External Info and Configuration
    $stmt = $pdo->prepare("SELECT d.external_id, ic.outbound_url, ic.outbound_key, ic.client_secret, ic.timeout, ic.label 
                           FROM disposisi d 
                           JOIN integrasi_config ic ON ic.label = 'SidikSae'
                           WHERE d.id = ? AND ic.is_active = 1 AND ic.outbound_url IS NOT NULL AND ic.outbound_url != ''
                           LIMIT 1");
    
    $stmt->execute([$disposisiId]);
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $row['outbound_url'];
        $key = $row['outbound_key'];
        $secret = $row['client_secret'];
        $timeout = intval($row['timeout'] ?? 10);
        
        $payload = json_encode([
            'external_id' => $row['external_id'],
            'status' => 'dilaksanakan',
            'updated_at' => date('Y-m-d H:i:s'),
            'notes' => 'Tindak lanjut telah didokumentasikan di BESUK SAE.'
        ]);
        
        // Curl Request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        
        $headers = [
            'Content-Type: application/json',
            'X-API-KEY: ' . $key
        ];
        if (!empty($secret)) {
            $headers[] = 'X-CLIENT-SECRET: ' . $secret;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For compatibility
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log the attempt
        file_put_contents(__DIR__ . '/../logs/integration.log', 
            date('Y-m-d H:i:s') . " [OUTBOUND] To: $url | ID: {$row['external_id']} | Code: $httpCode | Resp: $response\n", 
            FILE_APPEND);
            
        return ($httpCode >= 200 && $httpCode < 300);
    }
    
    return false;
}

/**
 * Sync all users from Docku to Camat Application
 * FIXED: Password field protection added + Safe mode by default
 */
function syncUsersToCamas($pdo, $forceSync = false) {
    // SAFETY: Skip auto-sync to prevent password overwrite
    if (!$forceSync) {
        return [
            'success' => true, 
            'message' => 'Sinkronisasi di-skip (mode aman). Gunakan tombol manual sync jika diperlukan.',
            'count' => 0,
            'skipped' => true
        ];
    }
    
    // HARDCODED Configuration - Correct API Endpoint and Key
    $url = 'https://api.sidiksae.my.id/api/v1/users/sync';
    $key = 'sk_live_docku_x9y8z7w6v5u4t3s2';
    $timeout = 10;

    // 2. Fetch Users - ONLY structural roles, EXCLUDE admin/operator/staff/camat
    $stmtUser = $pdo->query("
        SELECT id, username, nama, jabatan, role 
        FROM users 
        WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')
        AND username IS NOT NULL 
        AND username != ''
        ORDER BY id
    ");
    $users = $stmtUser->fetchAll(PDO::FETCH_ASSOC);

    // ✅ CRITICAL FIX: Explicitly remove password fields to prevent null overwrite
    foreach ($users as &$user) {
        unset($user['password']);
        unset($user['password_hash']);
    }

    $payload = json_encode(['users' => $users]);

    // 3. Send Request
    $headers = [
        'Content-Type: application/json',
        'X-API-KEY: ' . $key,
        'Accept: application/json'
    ];

    // Add Bearer Token if available in session
    if (isset($_SESSION['access_token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['access_token'];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Log the result with more details
    $logMsg = date('Y-m-d H:i:s') . " [SYNC_USERS] To: $url | Code: $httpCode | Count: " . count($users);
    if ($error) $logMsg .= " | CurlError: $error";
    $logMsg .= " | Response: $response";
    $logMsg .= " | Payload: " . json_encode(['users' => array_slice($users, 0, 3)]) . "...\n";
    
    file_put_contents(__DIR__ . '/../logs/integration.log', $logMsg, FILE_APPEND);

    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        return [
            'success' => true, 
            'message' => $data['message'] ?? 'Sinkronisasi berhasil.',
            'count' => count($users)
        ];
    }

    return [
        'success' => false, 
        'message' => 'API Error ('.$httpCode.'): ' . ($error ?: $response)
    ];
}
?>

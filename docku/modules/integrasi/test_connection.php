<?php
// modules/integrasi/test_connection.php
// AJAX endpoint untuk test koneksi API

header('Content-Type: application/json');

require_once '../../config/database.php';

// Access Control: Admin Only
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get integration ID
$integration_id = intval($_POST['id'] ?? 0);

if (!$integration_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid integration ID']);
    exit;
}

try {
    // Get integration config
    $stmt = $pdo->prepare("SELECT * FROM integrasi_config WHERE id = ? LIMIT 1");
    $stmt->execute([$integration_id]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        echo json_encode(['status' => 'error', 'message' => 'Integrasi tidak ditemukan']);
        exit;
    }
    
    if ($config['label'] === 'SidikSae' && !empty($config['outbound_url'])) {
        // --- TEST OUTBOUND CONNECTION TO SIDIKSAE ---
        $url = $config['outbound_url'];
        $key = $config['outbound_key'];
        $secret = $config['client_secret'];
        $timeout = intval($config['timeout'] ?? 10);
        
        $headers = [
            'Content-Type: application/json',
            'X-API-KEY: ' . $key,
            'Accept: application/json'
        ];
        if (!empty($secret)) {
            $headers[] = 'X-CLIENT-SECRET: ' . $secret;
        }
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal terhubung ke SidikSae: ' . $curlError,
                'http_code' => $httpCode,
                'url' => $url
            ]);
        } elseif ($httpCode >= 200 && $httpCode < 400) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Berhasil! Sistem dapat terhubung ke server SidikSae (HTTP ' . $httpCode . ').',
                'details' => json_decode($response, true) ?? $response,
                'url' => $url
            ]);
        } else {
            // Check if response is JSON
            $details = json_decode($response, true);
            $message = 'SidikSae menolak koneksi (HTTP ' . $httpCode . ').';
            
            if ($httpCode == 404) {
                // If the full URL 404s, let's try the domain root just to see if the server is there
                $parsedUrl = parse_url($url);
                $rootUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');
                
                $message = "Endpoint tidak ditemukan (HTTP 404). \n\n" . 
                           "Catatan: Terdapat respon dari server (" . ($parsedUrl['host'] ?? '') . "), " . 
                           "namun path '" . ($parsedUrl['path'] ?? '/') . "' tidak dikenali. \n\n" . 
                           "Saran: Coba gunakan URL root saja: " . $rootUrl;
            }
            
            echo json_encode([
                'status' => 'error',
                'message' => $message,
                'url' => $url,
                'response_raw' => substr($response, 0, 200),
                'details' => $details
            ]);
        }
        exit;
    }

    // --- TEST INBOUND CONNECTION (EXISTING LOGIC) ---
    // Build endpoint URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;
    
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $basePath = preg_replace('#/modules/integrasi/.*$#', '', $scriptPath);
    $endpointUrl = $basePath . '/api/v1/disposisi/receive.php';
    
    $testPayload = [
        'external_id' => 'TEST_' . time(),
        'perihal' => 'Test Koneksi dari BESUK SAE Settings',
        'instruksi' => 'Ini adalah test koneksi',
        'tgl_disposisi' => date('Y-m-d H:i:s'),
        '_test_mode' => true
    ];
    
    $ch = curl_init($baseUrl . $endpointUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($testPayload),
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-KEY: ' . $config['inbound_key']
        ]
    ]);
    
    $postResponse = curl_exec($ch);
    $postHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $postCurlError = curl_error($ch);
    curl_close($ch);
    
    $postData = json_decode($postResponse, true);
    
    if ($postHttpCode === 201 && isset($postData['status']) && $postData['status'] === 'success') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Koneksi Inbound berhasil! App dapat menerima data.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal memproses request inbound (HTTP ' . $postHttpCode . ')',
            'response' => $postData ?? $postResponse
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

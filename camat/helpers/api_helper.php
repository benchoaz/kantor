<?php
// FILE: helpers/api_helper.php

require_once __DIR__ . '/../config/api.php';

/**
 * Send a request to the API
 * 
 * @param string $method GET, POST, PUT, DELETE
 * @param string $endpoint e.g., '/auth/login'
 * @param array $data Payload for POST/PUT
 * @param string|null $token Bearer Token
 * @return array Response [success, data, message, http_code]
 */
function call_api($method, $endpoint, $data = [], $token = null) {
    // START: URL Construction Logic (Robust for multiple configs)
    $baseUrl = API_BASE_URL;
    
    // Check if API_BASE_URL is generic (ends in /api) and needs version
    if (defined('API_VERSION') && substr($baseUrl, -3) === 'api' && strpos($baseUrl, API_VERSION) === false) {
        $baseUrl .= '/' . API_VERSION;
    }
    
    $url = $baseUrl . $endpoint;
    // END: URL Construction Logic
    
    $curl = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: CamatApp/1.0'
    ];
    
    // Add API Key Header if defined
    if (defined('APP_API_KEY')) {
        $headers[] = 'X-API-KEY: ' . APP_API_KEY;
    }

    // Add Client ID Header if defined (MATCHING LEGACY CLIENT)
    if (defined('APP_CLIENT_ID')) {
        $headers[] = 'X-CLIENT-ID: ' . APP_CLIENT_ID;
    }
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => API_TIMEOUT,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers
    ];

    if ($method === 'POST' || $method === 'PUT') {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    // IF GET has params
    if ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
        $options[CURLOPT_URL] = $url;
    }

    // SSL Verify handling
    $options[CURLOPT_SSL_VERIFYPEER] = false;
    $options[CURLOPT_SSL_VERIFYHOST] = 0; 

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    
    curl_close($curl);

    if ($err) {
        return [
            'success' => false,
            'message' => 'cURL Error: ' . $err,
            'http_code' => 0
        ];
    }
    
    $decoded = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Invalid JSON Response from API',
            'raw_response' => $response,
            'http_code' => $http_code
        ];
    }

    $decoded['http_code'] = $http_code;
    
    return $decoded;
}

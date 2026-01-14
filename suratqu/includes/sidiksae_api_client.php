<?php
// includes/sidiksae_api_client.php

class SidikSaeApiClient {
    private $baseUrl;
    private $apiKey;
    
    public function __construct($config) {
        // Support both 'base_url' and 'api_url' (from integration.php)
        $url = $config['base_url'] ?? $config['api_url'] ?? '';
        $this->baseUrl = rtrim($url, '/');
        $this->apiKey = $config['api_key'] ?? '';
    }
    
    /**
     * Register Surat ke API Master Event Store (Step C1 Endpoint)
     */
    public function registerSurat($payload) {
        $url = $this->baseUrl . '/api/surat';
        
        // Transform payload to match C1 specs exactly
        $apiPayload = [
            'uuid' => $payload['uuid_surat'],
            'nomor_surat' => $payload['nomor_surat'],
            'tanggal_surat' => $payload['tanggal_surat'],
            'pengirim' => $payload['pengirim'],
            'perihal' => $payload['perihal'],
            'scan_surat' => $payload['file_pdf'], // Map file_pdf to scan_surat
            'is_final' => 1, // Wajib 1
            'source_app' => 'suratqu'
        ];

        return $this->sendRequest('POST', $url, $apiPayload);
    }
    
    /**
     * Create Disposisi (Step C4)
     */
    public function createDisposisi($data) {
        $url = $this->baseUrl . '/api/disposisi';
        return $this->sendRequest('POST', $url, $data);
    }
    
    /**
     * Get Surat Masuk for Pimpinan (Step C3 Endpoint)
     */
    public function getSuratMasuk($params = []) {
        $url = $this->baseUrl . '/api/pimpinan/surat-masuk';
        
        // Append query params
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->sendRequest('GET', $url);
    }

    private function sendRequest($method, $url, $data = null) {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'X-API-KEY: ' . $this->apiKey,
            'User-Agent: SuratQu/1.0'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout 10s
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($response === false) {
            return [
                'success' => false,
                'message' => 'Connection Error: ' . $error,
                'http_code' => 0
            ];
        }
        
        $decoded = json_decode($response, true);
        
        // C1 Outcome: 201 (Created) or 200 (Idempotent Success) are both SUCCESS
        $isSuccess = ($httpCode >= 200 && $httpCode < 300);
        
        if ($isSuccess) {
            return array_merge(['success' => true, 'http_code' => $httpCode], $decoded ?? []);
        } else {
            return [
                'success' => false, 
                'http_code' => $httpCode,
                'message' => $decoded['message'] ?? 'Unknown API Error',
                'data' => $decoded['data'] ?? null
            ];
        }
    }
}

<?php
/**
 * API Client Class
 * Menangani semua komunikasi dengan api.sidiksae.my.id
 */

class ApiClient {
    private $baseUrl;
    private $apiKey;
    private $clientId;
    private $clientSecret;
    private $token;

    public function __construct() {
        $this->baseUrl = API_BASE_URL . '/' . API_VERSION;
        $this->apiKey = API_KEY;
        $this->clientId = CLIENT_ID;
        $this->clientSecret = CLIENT_SECRET;
        
        // Ambil token dari session jika ada
        if (isset($_SESSION['api_token'])) {
            $this->token = $_SESSION['api_token'];
        }
    }

    /**
     * Set token autentikasi
     */
    public function setToken($token) {
        $this->token = $token;
        $_SESSION['api_token'] = $token;
    }

    /**
     * GET Request
     */
    public function get($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->request('GET', $url);
    }

    /**
     * POST Request
     */
    public function post($endpoint, $data = []) {
        $url = $this->baseUrl . $endpoint;
        return $this->request('POST', $url, $data);
    }

    /**
     * PUT Request
     */
    public function put($endpoint, $data = []) {
        $url = $this->baseUrl . $endpoint;
        return $this->request('PUT', $url, $data);
    }

    /**
     * Main request handler
     */
    private function request($method, $url, $data = null) {
        $ch = curl_init();

        // Determine content type
        $isMultipart = false;
        if ($data) {
            foreach ($data as $value) {
                if ($value instanceof CURLFile) {
                    $isMultipart = true;
                    break;
                }
            }
        }

        // Headers
        $headers = [
            'X-API-KEY: ' . $this->apiKey,
            'X-CLIENT-ID: ' . $this->clientId,
            'Accept: application/json'
        ];

        if (!$isMultipart) {
            $headers[] = 'Content-Type: application/json';
        }

        // Tambahkan Authorization header jika token tersedia
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        // cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Set method dan data
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $isMultipart ? $data : json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $isMultipart ? $data : json_encode($data));
            }
        }

        // Execute
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        // Handle errors
        if ($error) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $error
            ];
        }

        // Parse response
        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $result['data'] ?? [], // Ambil isi di dalam key 'data' jika ada
                'message' => $result['message'] ?? 'Success',
                'http_code' => $httpCode
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Request failed',
                'errors' => $result['errors'] ?? null,
                'http_code' => $httpCode
            ];
        }
    }

    /**
     * Login ke API
     */
    public function login($username, $password) {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'api_key' => $this->apiKey,
            'api_secret' => $this->clientSecret,
            'username' => $username,
            'password' => $password
        ];

        $response = $this->post('/auth/login', $data);
        
        if ($response['success'] && isset($response['data']['token'])) {
            $this->setToken($response['data']['token']);
        }
        
        return $response;
    }

    /**
     * Ambil profil user yang sedang login
     */
    public function getProfile() {
        // Mencoba endpoint pimpinan profile
        return $this->get('/pimpinan/profile');
    }

    /**
     * Update Password
     */
    public function updatePassword($currentPassword, $newPassword, $confirmPassword) {
        $data = [
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
            'new_password_confirmation' => $confirmPassword
        ];
        
        return $this->post('/pimpinan/change-password', $data);
    }

    /**
     * Logout dari API
     */
    public function logout() {
        $response = $this->post('/auth/logout');
        
        // Clear token dari session
        unset($_SESSION['api_token']);
        $this->token = null;
        
        return $response;
    }
}

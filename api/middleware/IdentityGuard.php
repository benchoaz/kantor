<?php
namespace App\Middleware;

/**
 * IdentityGuard Middleware
 * 
 * Filosofi Arsitektur:
 * 1. API adalah Penjaga Gerbang: Ia bertugas memverifikasi izin, bukan menebak siapa orangnya.
 * 2. Single Source of Truth: Data identitas hanya dikelola oleh modul 'id'. API tidak boleh 
 *    menyentuh database user lokal untuk urusan login agar sinkronisasi data tetap terjaga.
 * 3. UUID-First: Menggunakan UUID v5 karena bersifat immutable (tidak berubah). Internal ID (BIGINT)
 *    mungkin berubah jika ada migrasi database, tapi UUID tetap konsisten lintas aplikasi.
 */
class IdentityGuard {
    
    // URL Identity Service (Bisa ditaruh di .env)
    private static $identityUrl = "https://id.sidiksae.my.id/v1/auth/verify";

    public static function authenticate() {
        // 1. Ambil Header Standar
        $headers = getallheaders();
        $token = self::getBearerToken($headers);
        $appId = $headers['X-APP-ID'] ?? $headers['x-app-id'] ?? null;
        $appKey = $headers['X-APP-KEY'] ?? $headers['x-app-key'] ?? null;

        if (!$token || !$appId || !$appKey) {
            self::abort("Missing Authorization or Application Headers", 401);
        }

        // 2. Delegasikan Verifikasi ke Identity Service
        $verification = self::verifyToIdentity($token, $appId, $appKey);

        if ($verification['status'] !== 'success') {
            self::abort($verification['message'] ?? "Authentication failed", 401);
        }

        // 3. Inject Context ke Request Global
        // Kita menyimpan uuid_user dan scopes agar controller API bisa menggunakannya
        $_REQUEST['auth_user'] = [
            'uuid_user' => $verification['data']['uuid_user'],
            'scopes'    => $verification['data']['scopes'],
            'verified_at' => date('Y-m-d H:i:s')
        ];

        return true;
    }

    private static function verifyToIdentity($token, $appId, $appKey) {
        $ch = curl_init(self::$identityUrl);
        
        $headers = [
            "Authorization: Bearer $token",
            "X-APP-ID: $appId",
            "X-APP-KEY: $appKey",
            "Accept: application/json"
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Jangan biarkan API menunggu terlalu lama

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['status' => 'error', 'message' => 'Layanan autentikasi sedang tidak tersedia'];
        }

        return json_decode($response, true);
    }

    private static function getBearerToken($headers) {
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if ($auth && preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private static function abort($message, $code) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'code' => $code
        ]);
        exit;
    }
}

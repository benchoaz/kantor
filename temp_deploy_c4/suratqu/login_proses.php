<?php
// login_proses.php (Hardened with Identity Delegation)
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Load Integration Config for Identity Service
$intConfig = require 'config/integration.php';
$idConfig = $intConfig['sidiksae'];

if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['alert'] = ['msg' => 'Username dan Password wajib diisi!', 'type' => 'danger'];
        header("Location: login.php");
        exit;
    }

    try {
        // 1. DELEGATE AUTHENTICATION TO IDENTITY SERVICE
        $ch = curl_init($idConfig['identity_url'] . '/auth/login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'username' => $username,
            'password' => $password,
            'device_type' => 'web_suratqu'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-APP-ID: ' . $idConfig['app_id'],
            'X-APP-KEY: ' . $idConfig['api_key']
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        // 2. CHECK IDENTITY RESPONSE
        if ($httpCode === 200 && isset($result['status']) && $result['status'] === 'success') {
            // Identity confirmed! Now fetch local permissions
            $stmt = $db->prepare("SELECT u.*, j.can_verifikasi, j.can_tanda_tangan 
                                  FROM users u 
                                  LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan 
                                  WHERE u.username = ? AND u.is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // STRICT ENFORCEMENT for SuratQu
                // Allowed: Admin, Operator, and Staff-level (Kasi, Kasubag, Staf) for letter creation
                // Forbidden: Leadership (Camat, Sekcam) - use Camat App
                $allowedRoles = ['admin', 'operator', 'system_admin', 'kasi', 'kasubag', 'staf', 'pelaksana'];
                
                // Normalization for specific role names if needed (e.g. 'kasi_pemerintahan' -> contains 'kasi')
                $role = strtolower($user['role']);
                $isAllowed = false;
                foreach ($allowedRoles as $allowed) {
                    if (str_contains($role, $allowed)) {
                        $isAllowed = true;
                        break;
                    }
                }
                
                if (!$isAllowed) {
                    $_SESSION['alert'] = ['msg' => 'Akses Ditolak: Pimpinan (Camat/Sekcam) harap gunakan Aplikasi Camat.', 'type' => 'danger'];
                    header("Location: login.php");
                    exit;
                }

                // SUCCESS: Set Local Session
                $_SESSION['id_user']      = $user['id_user'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap']  = $user['nama_lengkap'];
                $_SESSION['role']         = $user['role'];
                $_SESSION['id_jabatan']    = $user['id_jabatan'];
                $_SESSION['can_verifikasi'] = $user['can_verifikasi'] ?? 0;
                $_SESSION['can_tanda_tangan'] = $user['can_tanda_tangan'] ?? 0;
                
                // Store Identity Tokens for API calls
                $_SESSION['access_token']  = $result['data']['access_token'];
                $_SESSION['refresh_token'] = $result['data']['refresh_token'];
                $_SESSION['uuid_user']     = $result['data']['uuid_user'];

                logActivity("User Login Berhasil (via Identity)");
                header("Location: index.php");
                exit;
            } else {
                // User authenticated in Identity but not found in local SuratQu DB (Sync required)
                $_SESSION['alert'] = ['msg' => 'Akun terverifikasi tapi data organisasi tidak ditemukan.', 'type' => 'warning'];
            }
        } else {
            // Authentication Failed
            $errMsg = $result['message'] ?? 'Username atau Password salah!';
            if ($httpCode === 0) $errMsg = 'Layanan autentikasi sedang tidak tersedia';
            
            $_SESSION['alert'] = ['msg' => $errMsg, 'type' => 'danger'];
        }

        header("Location: login.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['alert'] = ['msg' => 'Terjadi kesalahan sistem: ' . $e->getMessage(), 'type' => 'danger'];
        header("Location: login.php");
        exit;
    }
}
?>

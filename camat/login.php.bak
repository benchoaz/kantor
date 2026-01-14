<?php
/**
 * Login Page
 * Halaman login untuk autentikasi via API
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

startSession();

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Proses login
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validasi CSRF
    if (ENABLE_CSRF_PROTECTION) {
        if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            $error = 'Token keamanan tidak valid. Silakan refresh halaman.';
        }
    }
    
    if (!$error) {
        if (empty($username) || empty($password)) {
            $error = 'Username dan password wajib diisi';
        } else {
            // Login via API
            $api = new ApiClient();
            $result = $api->login($username, $password);
            
            if ($result['success'] && isset($result['data']['token'])) {
                // Token didapat! Sekarang ambil data user
                $userData = $result['data']['user'] ?? null;
                
                if (!$userData) {
                    // Coba panggil profile
                    $profileResult = $api->getProfile();
                    if ($profileResult['success']) {
                        $userData = $profileResult['data'];
                    }
                }
                
                if ($userData) {
                    $role = strtolower($userData['role'] ?? '');
                    $allowedRoles = ['pimpinan', 'sekcam', 'admin'];
                    
                    if (!in_array($role, $allowedRoles)) {
                        $error = 'Akses ditolak. Akun Anda memiliki role "' . $role . '", sedangkan panel ini khusus Pimpinan/Sekcam.';
                    } else {
                        loginUser($userData);
                        redirect('dashboard.php');
                    }
                } else {
                    // Jika profil tidak didapat, kita buat data session minimal dari username
                    $fallbackUser = [
                        'id' => $result['data']['user_id'] ?? 0,
                        'username' => $username,
                        'name' => 'Pimpinan',
                        'role' => 'pimpinan'
                    ];
                    loginUser($fallbackUser);
                    redirect('dashboard.php');
                }
            } else {
                // Tampilkan alasan pasti kenapa gagal
                $apiMsg = $result['message'] ?? 'Periksa koneksi internet Anda';
                $apiCode = $result['http_code'] ?? '000';
                
                if ($apiCode == 401) {
                    $error = 'Password yang Bapak masukkan tidak cocok dengan yang ada di database pusat.';
                } else if ($apiCode == 404) {
                    $error = 'Server API tidak ditemukan. Pastikan alamat di config sudah benar.';
                } else {
                    $error = "Gagal Masuk ($apiCode): $apiMsg";
                }
            }
        }
    }
}

$pageTitle = 'Login Executive';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#7A9D8C">
    <title><?php echo $pageTitle; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #7A9B8E;
            --primary-dark: #638075;
            --primary-light: #9BB8AC;
            --text-main: #333333;
            --text-muted: #888888;
            --white: #ffffff;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
            --shadow-soft: 0 10px 30px -5px rgba(122, 155, 142, 0.3);
            --radius-pill: 50px;
            --radius-card: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            min-height: 100dvh; /* Dynamic Viewport Height for mobile */
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #7A9B8E 0%, #5F7A6F 100%);
            color: var(--text-main);
            position: relative;
            overflow-x: hidden;
            padding: 20px;
        }

        /* Ambient Background Animations */
        .ambient-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            z-index: 0;
            animation: float 20s infinite ease-in-out;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            background: #9BB8AC;
            top: -50px;
            right: -50px;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 250px;
            height: 250px;
            background: #4A6359;
            bottom: -50px;
            left: -50px;
            animation-delay: -5s;
        }
        
        .shape-3 {
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.2);
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -50px) rotate(10deg); }
            66% { transform: translate(-20px, 20px) rotate(-10deg); }
        }

        /* Glassmorphism Card */
        .login-card {
            width: 100%;
            max-width: 360px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-soft);
            padding: 40px 30px;
            position: relative;
            z-index: 10;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Logos & Header */
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-wrapper {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            padding: 8px;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(-5deg);
            transition: transform 0.3s ease;
        }
        
        .logo-wrapper:hover {
            transform: rotate(0deg) scale(1.05);
        }

        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .title {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-dark);
            letter-spacing: -0.5px;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .input-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            margin-left: 15px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            opacity: 0.6;
            transition: opacity 0.3s;
        }

        .form-control {
            width: 100%;
            height: 52px;
            padding: 0 50px;
            background: #F0F4F2;
            border: 2px solid transparent;
            border-radius: var(--radius-pill);
            font-size: 15px;
            font-weight: 500;
            color: var(--text-main);
            outline: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .form-control:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(122, 155, 142, 0.15);
        }
        
        .form-control:focus + .input-icon {
            opacity: 1;
        }

        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 5px;
            display: flex;
            align-items: center;
        }

        .btn-submit {
            width: 100%;
            height: 54px;
            background: var(--primary);
            background: linear-gradient(135deg, #7A9B8E 0%, #638075 100%);
            color: white;
            border: none;
            border-radius: var(--radius-pill);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(122, 155, 142, 0.3);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-submit:active {
            transform: scale(0.96);
            box-shadow: 0 5px 10px rgba(122, 155, 142, 0.2);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 25px;
            text-align: left;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-error {
            background: #FFF5F5;
            color: #C53030;
            border: 1px solid #FC8181;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: rgba(0,0,0,0.4);
            font-weight: 500;
        }
        
        /* Mobile Balance Fix */
        @media (max-height: 700px) {
             .login-card {
                 padding: 30px 25px;
                 transform: scale(0.95);
             }
             .ambient-shape {
                 opacity: 0.4;
             }
        }
    </style>
</head>
<body>

    <!-- Ambient Background -->
    <div class="ambient-shape shape-1"></div>
    <div class="ambient-shape shape-2"></div>
    <div class="ambient-shape shape-3"></div>

    <div class="login-card">
        <div class="login-header">
            <div class="logo-wrapper">
                <img src="assets/img/logo.jpg" alt="Besuk Logo" class="logo-img">
            </div>
            <h1 class="title">C A M A T</h1>
            <p class="subtitle">Executive Access Panel</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php if (defined('ENABLE_CSRF_PROTECTION') && ENABLE_CSRF_PROTECTION): ?>
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $_SESSION[CSRF_TOKEN_NAME]; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="input-label">ID PENGGUNA</label>
                <div class="input-wrapper">
                    <input type="text" name="username" class="form-control" placeholder="Masukkan ID Login" required autocomplete="username">
                    <div class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="input-label">KATA SANDI</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                    <div class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">MASUK SEKARANG</button>
        </form>

        <div class="footer">
            BESUKSAE SYSTEM v2.0<br>
            &copy; 2026 Kecamatan Besuk
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('password');
            const icon = this.querySelector('svg');
            
            if (input.type === 'password') {
                input.type = 'text';
                // Change icon to eye-off
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
                this.style.opacity = '0.7';
            } else {
                input.type = 'password';
                // Change icon back to eye
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
                this.style.opacity = '0.4';
            }
        });
    </script>
</body>
</html>

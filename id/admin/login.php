<?php
/**
 * Identity Admin Portal - Login Page
 * Direct authentication for admin portal access
 */
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // Authenticate via Identity Service
        // Use domain name with standard DNS resolution (Test 5 confirmed working)
        $ch = curl_init('https://id.sidiksae.my.id/v1/auth/login');
        // REMOVED: CURLOPT_RESOLVE (caused 404 on this server)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'username' => $username,
            'password' => $password,
            'device_type' => 'admin_portal'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-APP-ID: admin_portal',
            'X-APP-KEY: admin_portal_secret_key_2026'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL validity for loopback
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'IdentityPortal/1.0 Internal');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrNo = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['status']) && $result['status'] === 'success') {
            // Set admin session
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_uuid'] = $result['data']['uuid_user'];
            $_SESSION['access_token'] = $result['data']['access_token'];
            $_SESSION['refresh_token'] = $result['data']['refresh_token'];
            $_SESSION['admin_last_activity'] = time();
            
            // TODO: Verify user role is admin (requires API endpoint to get user details)
            $_SESSION['admin_role'] = 'admin'; // Placeholder

            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'] ?? 'Login gagal. Periksa username dan password Anda.';
            if ($httpCode === 0) {
                 $error = 'Koneksi ke Auth Service Gagal (CURL Error #' . $curlErrNo . ': ' . $curlError . '). Hubungi Administrator.';
            }
        }
    } else {
        $error = 'Username dan password wajib diisi.';
    }
}

// Handle timeout/unauthorized messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'timeout') $error = 'Sesi Anda telah berakhir. Silakan login kembali.';
    if ($_GET['msg'] === 'unauthorized') $error = 'Akses ditolak. Hanya admin yang dapat mengakses portal ini.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | SidikSae Identity</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --danger: #ef4444;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: block;
        }
        .logo h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }
        .logo p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .alert {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            color: var(--text);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            color: var(--text);
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(15, 23, 42, 0.7);
        }
        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        .footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="bi bi-shield-lock"></i>
            <h1>Identity Admin Portal</h1>
            <p>SidikSae Centralized Management</p>
        </div>

        <?php if ($error): ?>
        <div class="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Masuk ke Portal</span>
            </button>
        </form>

        <div class="footer">
            <p>🔐 Secure Access Only · Administrator Panel</p>
        </div>
    </div>
</body>
</html>

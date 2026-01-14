<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SidikSae Identity</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 420px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .app-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 13px;
            color: #666;
        }
        
        .app-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #f5f5f5;
            border-radius: 20px;
            font-weight: 500;
            color: #667eea;
            margin-top: 8px;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: rgba(255,255,255,0.8);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🔐 SidikSae</h1>
            <p>Identity & Authentication</p>
        </div>
        
        <?php
        // Display error message if exists
        session_start();
        if (isset($_SESSION['login_error'])) {
            echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
            unset($_SESSION['login_error']);
        }
        
        // Display info about which app requested login
        $app = $_GET['app'] ?? 'unknown';
        $appNames = [
            'camat' => 'Aplikasi Camat',
            'suratqu' => 'SuratQu',
            'docku' => 'Docku',
            'api' => 'API'
        ];
        $appName = $appNames[$app] ?? 'Aplikasi';
        
        if ($app !== 'unknown') {
            echo '<div class="alert alert-info">Anda akan login ke <strong>' . htmlspecialchars($appName) . '</strong></div>';
        }
        ?>
        
        <form method="POST" action="process.php" id="loginForm">
            <input type="hidden" name="app" value="<?= htmlspecialchars($app) ?>">
            <input type="hidden" name="redirect_uri" value="<?= htmlspecialchars($_GET['redirect_uri'] ?? '') ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    autofocus
                    placeholder="Masukkan username Anda"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="Masukkan password Anda"
                >
            </div>
            
            <button type="submit" class="btn-login" id="btnLogin">
                Masuk
            </button>
        </form>
        
        <?php if ($app !== 'unknown'): ?>
        <div class="app-info">
            Login untuk akses
            <div class="app-badge"><?= htmlspecialchars($appName) ?></div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        SidikSae Identity Module v1.0 - Phase B Pilot<br>
        Secure Authentication for Government Apps
    </div>
    
    <script>
        // Simple form validation and UX
        const form = document.getElementById('loginForm');
        const btnLogin = document.getElementById('btnLogin');
        
        form.addEventListener('submit', function(e) {
            btnLogin.disabled = true;
            btnLogin.textContent = 'Memproses...';
        });
        
        // Auto-focus username field
        document.getElementById('username').focus();
    </script>
</body>
</html>

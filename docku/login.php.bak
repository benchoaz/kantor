<?php
/**
 * Login Page - BESUKSAE
 * Desain modern, ringan, dan responsive untuk Kantor Kecamatan Besuk.
 * Tanpa framework berat, cocok untuk shared hosting cPanel.
 */
require_once 'config/database.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT id, nama, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time(); // Set initial activity time
            header("Location: index.php");
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Silakan isi semua field.';
    }
}

// Check for timeout message
if (isset($_GET['msg']) && $_GET['msg'] === 'timeout') {
    $error = 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - BESUKSAE Kecamatan Besuk</title>
    
    <!-- Link Icons (Font Awesome CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #87A380; /* Sage Green Primary */
            --primary-hover: #6B8465;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.5);
            --text-main: #2d3436;
            --text-muted: #636e72;
            --sage-light: #F1F4F0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), 
                        url('assets/img/bg-login.png') no-repeat center center fixed;
            background-size: cover;
            overflow: hidden;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.8s ease-out;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 35px;
            padding: 45px 40px;
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden; /* Roundtangle siluet */
        }

        /* Siluet Inovatif di dalam Roundtangle (Card) */
        .login-card::before {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 120px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%2387A380' fill-opacity='0.2' d='M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,165.3C1248,149,1344,171,1392,181.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E") no-repeat bottom center;
            background-size: cover;
            z-index: -1;
        }

        .login-card::after {
            content: "";
            position: absolute;
            bottom: -20px;
            left: 0;
            width: 100%;
            height: 100px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%236B8465' fill-opacity='0.15' d='M0,160L60,170.7C120,181,240,203,360,186.7C480,171,600,117,720,122.7C840,128,960,192,1080,218.7C1200,245,1320,235,1380,229.3L1440,224L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z'%3E%3C/path%3E%3C/svg%3E") no-repeat bottom center;
            background-size: cover;
            z-index: -1;
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-logo img {
            width: 90px;
            height: auto;
            margin-bottom: 20px;
            filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.1));
        }

        .brand-logo h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary-hover);
            letter-spacing: 2px;
            margin: 0;
            text-transform: uppercase;
        }

        .brand-logo p {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 5px;
            font-weight: 500;
        }

        .error-alert {
            background: rgba(231, 76, 60, 0.1);
            color: #c0392b;
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            border: 1px solid rgba(231, 76, 60, 0.2);
            animation: shake 0.5s ease-in-out;
        }

        .error-alert i { margin-right: 12px; }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 8px;
            margin-left: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group-custom i.main-icon {
            position: absolute;
            left: 18px;
            color: var(--text-muted);
            font-size: 1rem;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px 14px 50px;
            border: 1.5px solid transparent;
            background: white;
            border-radius: 16px;
            font-size: 0.95rem;
            color: var(--text-main);
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 8px 20px rgba(135, 163, 128, 0.15);
        }

        .form-control:focus ~ i.main-icon {
            color: var(--primary-color);
        }

        /* Password Toggle Inovasi */
        .password-toggle {
            position: absolute;
            right: 18px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 5px;
            transition: all 0.2s;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
            transform: scale(1.1);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 0 5px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            font-size: 0.8rem;
            color: var(--text-muted);
            cursor: pointer;
            font-weight: 500;
        }

        .checkbox-label input {
            margin-right: 10px;
            accent-color: var(--primary-color);
            width: 16px; height: 16px;
        }

        .forgot-link {
            font-size: 0.8rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 700;
            transition: color 0.2s;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 25px rgba(135, 163, 128, 0.3);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(135, 163, 128, 0.4);
        }

        .btn-login:active {
            transform: translateY(1px);
        }

        footer {
            text-align: center;
            margin-top: 35px;
            color: var(--primary-hover);
            font-size: 0.75rem;
            font-weight: 600;
            opacity: 0.8;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 40px 30px;
                border-radius: 35px;
            }
            .brand-logo h1 { font-size: 1.4rem; }
            .btn-login { padding: 14px; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand-logo">
                <img src="assets/img/logo.png" alt="Logo Kecamatan Besuk">
                <h1>BESUK SAE</h1>
                <p>Melayani setulus hati</p>
            </div>

            <?php if ($error): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <div class="form-group">
                    <label for="username">PENGGUNA</label>
                    <div class="input-group-custom">
                        <i class="fas fa-user-circle main-icon"></i>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Masukan username" autocomplete="username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">KATA SANDI</label>
                    <div class="input-group-custom">
                        <i class="fas fa-key main-icon"></i>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Masukan password" autocomplete="current-password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        Tetap Masuk
                    </label>
                    <a href="#" class="forgot-link">Ganti Password?</a>
                </div>

                <button type="submit" class="btn-login">
                    MASUK SEKARANG <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <footer>
            &copy; <?= date('Y') ?> Pemerintah Kecamatan Besuk
        </footer>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // toggle the eye / eye slash icon
            this.classList.toggle('fa-eye-slash');
        });
    </script>

</body>
</html>

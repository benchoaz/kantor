<?php
// login.php
session_start();
if (isset($_SESSION['id_user'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SuratQu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: url('assets/img/login_bg.png') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', sans-serif;
            margin: 0;
            overflow: hidden;
        }

        /* Glassmorphism Card (Refined transparency) */
        .login-card {
            background: rgba(20, 20, 20, 0.45); /* More transparent to see forest */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 12px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4), inset 0 0 1px rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 380px;
            padding: 3rem 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: white;
            position: relative;
        }

        /* Close icon in top right corner of card */
        .close-icon {
            position: absolute;
            top: 15px;
            right: 20px;
            color: rgba(255, 255, 255, 0.3);
            font-size: 1.2rem;
            cursor: pointer;
        }

        .login-title {
            font-size: 2.8rem;
            font-weight: 300;
            margin-bottom: 2rem;
            letter-spacing: 1px;
            font-family: 'Times New Roman', serif; /* Matching the elegant serif in screenshot */
            color: rgba(255, 255, 255, 0.9);
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1); /* Subtle light glass inputs */
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 12px 18px;
            color: white !important;
            font-size: 14px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-control:focus {
            background: rgba(60, 60, 60, 0.8);
            border-color: rgba(52, 199, 89, 0.5);
            box-shadow: none;
            outline: none;
        }

        .btn-login {
            background: #508028; /* Forest green from screenshot */
            color: white;
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            font-weight: 500;
            border: none;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            background: #609030;
            transform: translateY(-1px);
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
        }

        .login-options a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
        }

        .form-check-input {
            background-color: rgba(0, 0, 0, 0.5);
            border-color: rgba(255, 255, 255, 0.2);
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #508028;
            border-color: #508028;
        }

        /* Alert styling for glassmorphism */
        .alert {
            background: rgba(255, 59, 48, 0.2);
            border: 1px solid rgba(255, 59, 48, 0.3);
            color: #ff9b9b;
            font-size: 12px;
            border-radius: 8px;
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body>

<div class="login-card mx-3">
    <div class="close-icon"><i class="fa-solid fa-times-circle"></i></div>
    
    <h1 class="login-title">SuratQu</h1>

    <?php if (isset($_SESSION['alert'])): ?>
        <div class="alert alert-dismissible fade show mb-4" role="alert">
            <?= $_SESSION['alert']['msg'] ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <form action="login_proses.php" method="POST">
        <div class="mb-1">
            <input type="text" name="username" class="form-control" placeholder="E-mail" required>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <button type="submit" name="login" class="btn btn-login">Log in</button>
        
        <div class="login-options">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="rememberCheck">
                <label class="form-check-label" for="rememberCheck">
                    Remember me
                </label>
            </div>
            <a href="#">Forgotten password</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

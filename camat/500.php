<?php
define('APP_INIT', true);
require_once 'config/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Kesalahan Sistem';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - <?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/design-system.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #FFF5F5;
            text-align: center;
            padding: var(--space-xl);
        }
        .error-container {
            max-width: 500px;
        }
        .error-code {
            font-family: var(--font-secondary);
            font-size: 8rem;
            font-weight: 800;
            color: #FED7D7;
            line-height: 1;
            margin-bottom: var(--space-md);
        }
        .error-title {
            font-size: var(--font-2xl);
            margin-bottom: var(--space-md);
            color: #C53030;
        }
        .error-text {
            font-size: var(--font-base);
            color: var(--medium-gray);
            margin-bottom: var(--space-xl);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: var(--white);
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-lg);
            box-shadow: var(--shadow-md);
            color: #E53E3E;
        }
    </style>
</head>
<body>
    <div class="error-container card">
        <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
        <div class="error-code">500</div>
        <h1 class="error-title">Kesalahan Sistem</h1>
        <p class="error-text">
            Terjadi kesalahan pada server kami. Silakan coba beberapa saat lagi atau hubungi administrator.
        </p>
        <a href="index.php" class="btn btn-primary btn-block">
            Kembali ke Dashboard
        </a>
    </div>
</body>
</html>

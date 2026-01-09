<?php
define('APP_INIT', true);
require_once 'config/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Halaman Tidak Ditemukan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/design-system.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--soft-gray);
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
            color: var(--sage-green-pale);
            line-height: 1;
            margin-bottom: var(--space-md);
        }
        .error-title {
            font-size: var(--font-2xl);
            margin-bottom: var(--space-md);
            color: var(--sage-green-dark);
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
            color: var(--sage-green);
        }
    </style>
</head>
<body>
    <div class="error-container card">
        <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="error-code">404</div>
        <h1 class="error-title">Halaman Tidak Ditemukan</h1>
        <p class="error-text">
            Maaf, halaman yang Anda cari tidak tersedia atau telah dipindahkan.
        </p>
        <a href="index.php" class="btn btn-primary btn-block">
            Kembali ke Dashboard
        </a>
    </div>
</body>
</html>

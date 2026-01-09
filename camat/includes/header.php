<?php
/**
 * Header Component
 * Komponen header untuk semua halaman
 */

// Pastikan user sudah login
if (!isLoggedIn()) {
    return;
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#7A9B8E">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/design-system.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    
    <!-- Favicon (placeholder) -->
    <link rel="icon" type="image/png" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect fill='%237A9B8E' width='100' height='100'/%3E%3Ctext y='75' font-size='75' fill='white'%3EP%3C/text%3E%3C/svg%3E">
</head>
<body>

<header class="top-header">
    <div class="header-container">
        <div class="header-brand">
            <div>
                <div class="header-logo">C A M A T</div>
                <div class="header-subtitle">Panel Kendali Pimpinan</div>
            </div>
        </div>
        
        <div class="header-user">
            <div class="user-info">
                <span class="user-name"><?php echo e($user['name']); ?></span>
                <span class="user-role"><?php echo getRoleDisplayName($user['role']); ?></span>
            </div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
</header>

<?php echo renderFlashMessage(); ?>

<main class="container">

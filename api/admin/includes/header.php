<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SidikSae API - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #2c3e50; color: white; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 10px 15px; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: white; }
        .content { padding: 20px; }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php if(isset($_SESSION['admin_logged_in'])): ?>
        <div class="sidebar d-flex flex-column flex-shrink-0 p-3" style="width: 250px;">
            <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <span class="fs-4 fw-bold">SidikSae Admin</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-gauge me-2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'user_form.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-users me-2"></i> Manajemen User
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="logout.php" class="d-flex align-items-center text-white text-decoration-none">
                    <i class="fa-solid fa-sign-out-alt me-2"></i> <strong>Sign out</strong>
                </a>
            </div>
        </div>
        <?php endif; ?>
        <div class="content flex-grow-1">

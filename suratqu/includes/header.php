<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuratQu - Sistem Informasi Persuratan Kecamatan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== SuratQu Theme (Matching Screenshot) ===== */
        :root {
            /* Primary Colors - Green */
            --color-primary: #34C759;      /* Bright Green */
            --color-primary-dark: #2EAF4F;
            --color-primary-light: #B8F5CD;
            --color-primary-soft: #E8F9ED;
            
            /* Input Fields - Lavender/Gray */
            --color-input-bg: #E8E8F8;     /* Light lavender like screenshot */
            --color-input-border: #D8D8E8;
            
            /* Backgrounds */
            --color-bg: #EAECEF;           /* Light grey background for app */
            --color-sidebar: #F2F4F7;      /* Very light grey for sidebar */
            --color-bg-soft: #F8F9FA;
            --color-card: #FFFFFF;
            
            /* Text */
            --color-text: #1C1C1E;
            --color-text-secondary: #6C6C70;
            --color-text-tertiary: #9C9C9F;
            
            /* Borders */
            --color-border: #E5E5EA;
            --color-border-light: #F0F0F5;
            
            /* Accent Colors */
            --color-blue: #5AC8FA;
            --color-orange: #FF9500;
            --color-red: #FF3B30;
            --color-yellow: #FFCC00;       /* For icons */
            
            /* Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 20px;
            --radius-pill: 50px;           /* Full rounded for buttons/menu */
            
            /* Shadows */
            --shadow-soft: 0 2px 12px rgba(0, 0, 0, 0.08);
            --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* Base Styles */
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            font-size: 15px;
            line-height: 1.5;
        }
        
        /* Sidebar - Grey Aesthetic */
        .sidebar { 
            background: var(--color-sidebar);
            min-height: 100vh;
            color: var(--color-text);
            transition: all 0.3s ease;
            border-right: 1px solid var(--color-border);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.02);
        }
        
        .sidebar h4, .sidebar i.fa-envelope-open-text {
            color: var(--color-primary) !important;
        }
        
        /* Nav Links - Green Rounded for Active/Hover */
        .nav-link { 
            color: var(--color-text-secondary);
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius-pill);
            margin-bottom: 6px;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .nav-link:hover { 
            color: var(--color-primary);
            background: var(--color-primary-soft);
        }
        
        .nav-link.active { 
            color: white !important;
            background: var(--color-primary) !important;
            box-shadow: 0 4px 12px rgba(52, 199, 89, 0.25);
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        /* Cards - Simple & Clean */
        .card-custom { 
            background: var(--color-card);
            border: none;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-card);
            transition: all 0.2s ease;
        }
        
        .card-custom:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12);
        }
        
        /* Navbar - Clean & Modern */
        .navbar-custom { 
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: none;
            border-bottom: 1px solid var(--color-border);
            padding: 1rem 1.5rem;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.05);
        }
        
        /* Buttons - Rounded Green */
        .btn-primary, .btn-success { 
            background: var(--color-primary);
            border: none;
            padding: 0.65rem 1.75rem;
            border-radius: var(--radius-pill);
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover, .btn-success:hover {
            background: var(--color-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--color-primary) 0%, #30D158 100%);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3);
        }
        
        .btn-outline-secondary {
            border: 1.5px solid var(--color-border);
            color: var(--color-text-secondary);
            background: white;
            border-radius: var(--radius-pill);
            font-weight: 500;
        }
        
        .btn-outline-secondary:hover {
            background: var(--color-bg);
            border-color: var(--color-primary-light);
            color: var(--color-text);
        }
        
        /* Form Controls - Lavender like screenshot */
        .form-control, .form-select {
            border: 1px solid var(--color-input-border);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            font-size: 14px;
            transition: all 0.2s ease;
            background: var(--color-input-bg);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(52, 199, 89, 0.1);
            background: white;
            outline: none;
        }
        
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 0.4rem;
        }
        
        /* Badges - Soft & Rounded */
        .badge {
            border-radius: 20px;
            padding: 0.35rem 0.75rem;
            font-weight: 600;
            font-size: 11px;
            letter-spacing: 0.02em;
        }
        
        .bg-success { background: var(--color-primary) !important; }
        .bg-primary { background: var(--color-blue) !important; }
        .bg-secondary { background: var(--color-secondary) !important; }
        .bg-warning { background: var(--color-orange) !important; }
        .bg-danger { background: var(--color-red) !important; }
        .bg-info { background: var(--color-blue) !important; }
        .bg-light { background: var(--color-gray-soft) !important; color: var(--color-text) !important; }
        
        /* Alerts - iOS Style */
        .alert {
            border: none;
            border-radius: var(--radius-md);
            border-left: 4px solid;
            box-shadow: var(--shadow-soft);
        }
        
        .alert-success {
            background: var(--color-primary-soft);
            border-left-color: var(--color-primary);
            color: var(--color-text);
        }
        
        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { padding-bottom: 70px; }
            
            body {
                background: var(--color-bg);
            }
            
            .card-custom {
                border-radius: var(--radius-sm);
                box-shadow: var(--shadow-soft);
            }
        }
        
        /* Offcanvas - Consistent with Sidebar */
        .offcanvas { 
            background: linear-gradient(180deg, rgba(52, 199, 89, 0.95) 0%, rgba(52, 199, 89, 0.92) 100%);
            backdrop-filter: var(--blur-glass);
            color: white;
            width: 280px !important;
        }
        
        .offcanvas .nav-link { 
            color: rgba(255,255,255,0.85);
        }
        
        /* Bottom Navigation - iPhone Style */
        .bottom-nav { 
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: none;
            border-top: 1px solid var(--color-border);
            box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.08);
            z-index: 1030;
            padding: 8px 0 max(8px, env(safe-area-inset-bottom));
        }
        
        @media (max-width: 768px) { 
            .bottom-nav { 
                display: flex;
                justify-content: space-around;
            }
        }
        
        .bottom-nav-item { 
            text-align: center;
            color: var(--color-text-secondary);
            text-decoration: none;
            font-size: 10px;
            font-weight: 500;
            padding: 4px;
            transition: all 0.2s ease;
        }
        
        .bottom-nav-item i { 
            display: block;
            font-size: 22px;
            margin-bottom: 2px;
        }
        
        .bottom-nav-item.active { 
            color: var(--color-primary);
            font-weight: 600;
        }
        
        /* Breadcrumb - Clean */
        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
            font-size: 13px;
        }
        
        .breadcrumb-item a {
            color: var(--color-text-secondary);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--color-primary);
            font-weight: 600;
        }
        
        /* Avatar - Soft */
        .rounded-circle {
            box-shadow: 0 2px 8px rgba(52, 199, 89, 0.2);
        }
        
        /* Tables */
        .table {
            border-collapse: separate;
            border-spacing: 0 4px;
        }
        
        .table thead th {
            background: var(--color-bg-soft);
            border: none;
            color: var(--color-text-secondary);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.75rem 1rem;
        }
        
        .table tbody tr {
            background: white;
            box-shadow: var(--shadow-soft);
        }
        
        .table tbody td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }
        
        /* Smooth Transitions */
        * {
            transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
        }
    </style>

</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Desktop -->
        <nav class="col-md-3 col-lg-2 d-none d-md-block sidebar p-4">
            <div class="d-flex align-items-center mb-5 px-2">
                <i class="fa-solid fa-envelope-open-text fa-2x me-2 text-warning"></i>
                <h4 class="mb-0 fw-bold">SuratQu</h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a>
                </li>
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'operator'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="surat_masuk.php"><i class="fa-solid fa-inbox me-2"></i> Surat Masuk</a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'disposisi.php' ? 'active' : '' ?>" href="disposisi.php"><i class="fa-solid fa-share-nodes me-2"></i> Disposisi</a>
                </li>
                <?php if ($_SESSION['can_verifikasi'] == 1 || $_SESSION['role'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'verifikasi_surat.php' ? 'active' : '' ?>" href="verifikasi_surat.php">
                        <i class="fa-solid fa-user-check me-2"></i> Verifikasi Surat
                        <?php
                        $count_verif = $db->query("SELECT COUNT(*) FROM surat_keluar WHERE status = 'verifikasi'")->fetchColumn();
                        if ($count_verif > 0):
                        ?>
                        <span class="badge bg-danger rounded-pill float-end mt-1"><?= $count_verif ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'surat_keluar.php' ? 'active' : '' ?>" href="surat_keluar.php">
                        <i class="fa-solid fa-paper-plane me-2"></i> Surat Keluar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : '' ?>" href="laporan.php">
                        <i class="fa-solid fa-book me-2"></i> Buku Agenda
                    </a>
                </li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : '' ?>" href="logs.php">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i> Log Aktivitas
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="mt-4 mb-2 ms-3">
                    <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Administrator</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>" href="users.php">
                        <i class="fa-solid fa-users me-2"></i> Manajemen Pengguna
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'jabatan.php' ? 'active' : '' ?>" href="jabatan.php">
                        <i class="fa-solid fa-sitemap me-2"></i> Struktur Organisasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'kop_surat.php' ? 'active' : '' ?>" href="kop_surat.php">
                        <i class="fa-solid fa-file-invoice me-2"></i> Pengaturan Kop
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'integrasi_sistem.php' ? 'active' : '' ?>" href="integrasi_sistem.php">
                        <i class="fa-solid fa-circle-nodes me-2"></i> Integrasi Sistem
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Offcanvas Sidebar Mobile -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar">
            <div class="offcanvas-header border-bottom border-secondary p-4">
                <h5 class="offcanvas-title fw-bold"><i class="fa-solid fa-envelope-open-text me-2 text-warning"></i> SuratQu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body p-4">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a></li>
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'operator'): ?>
                    <li class="nav-item"><a class="nav-link" href="surat_masuk.php"><i class="fa-solid fa-inbox me-2"></i> Surat Masuk</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="disposisi.php"><i class="fa-solid fa-share-nodes me-2"></i> Disposisi</a></li>
                    <li class="nav-item"><a class="nav-link" href="surat_keluar.php"><i class="fa-solid fa-paper-plane me-2"></i> Surat Keluar</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <nav class="navbar navbar-expand-lg navbar-custom sticky-top rounded-bottom shadow-sm mb-4 mx-n4 mx-md-0">
                <div class="container-fluid px-0">
                    <button class="btn btn-link d-md-none text-dark ps-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
                        <i class="fa-solid fa-bars-staggered fa-lg"></i>
                    </button>
                    <span class="navbar-brand d-md-none fw-bold ms-2">SuratQu</span>
                    <div class="ms-auto d-flex align-items-center">
                        <?php if (isset($_SESSION['alert'])): ?>
                            <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show mb-0 me-3 py-1 px-3 small" role="alert">
                                <?= $_SESSION['alert']['msg'] ?>
                                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['alert']); ?>
                        <?php endif; ?>
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama_lengkap'] ?? 'Admin') ?>&background=34C759&color=fff" alt="" width="32" height="32" class="rounded-circle me-2">
                                <span class="d-none d-sm-inline"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Administrator') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><a class="dropdown-item" href="profil.php"><i class="fa-solid fa-user me-2"></i> Profil</a></li>
                                <li><a class="dropdown-item" href="kop_surat.php"><i class="fa-solid fa-file-invoice me-2"></i> Kop Surat</a></li>
                                <li><a class="dropdown-item" href="integrasi_sistem.php"><i class="fa-solid fa-circle-nodes me-2"></i> Integrasi Sistem</a></li>
                                <li><a class="dropdown-item" href="status_integrasi.php"><i class="fa-solid fa-wifi me-2"></i> Status API</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="fa-solid fa-gear me-2"></i> Pengaturan</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Keluar</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

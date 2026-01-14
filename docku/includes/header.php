<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_login();

// Determine Base URL purely by directory depth from DOCUMENT_ROOT or relative paths
$script_name = $_SERVER['SCRIPT_NAME'];
$depth = substr_count($script_name, '/') - 1;
if ($depth < 0) $depth = 0;

// Robust detection: Look for assets folder
if (file_exists('assets/css/modern-style.css')) {
    $base_url = './';
} elseif (file_exists('../assets/css/modern-style.css')) {
    $base_url = '../';
} elseif (file_exists('../../assets/css/modern-style.css')) {
    $base_url = '../../';
} else {
    // Fallback to relative depth
    $base_url = str_repeat('../', $depth);
}
// Clean base_url
$base_url = rtrim($base_url, '/') . '/';
if ($base_url == '/') $base_url = './';

// Get user profile photo
$user_photo = null;
if (isset($_SESSION['user_id'])) {
    try {
        global $pdo;
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT foto_profil FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            if ($result && !empty($result['foto_profil']) && file_exists($base_url . 'uploads/profil/' . $result['foto_profil'])) {
                $user_photo = $base_url . 'uploads/profil/' . $result['foto_profil'];
            }
        }
    } catch (Exception $e) {
        error_log("Profile photo error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= $page_title ?? 'BESUKSAE' ?> - Dokumentasi Kecamatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?= $base_url ?>assets/css/modern-style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Desktop Navbar (Hidden on Mobile) -->
        <div class="container container-navbar">
            <a class="navbar-brand d-flex align-items-center" href="<?= $base_url ?>index.php">
                <div class="ms-1">
                    <div class="fw-bold lh-1" style="font-size: 1.1rem; letter-spacing: 0.5px;">BESUK SAE</div>
                    <div class="small fw-normal opacity-75" style="font-size: 0.7rem; letter-spacing: 0.2px;">Melayani setulus hati</div>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Integration: Notification Logic -->
                    <?php 
                    require_once __DIR__ . '/notification_helper.php';
                    global $pdo;
                    $unreadCount = 0;
                    if (isset($pdo)) {
                        try {
                            $unreadCount = getUnreadDispositionCount($pdo, $_SESSION['user_id'] ?? 0);
                        } catch (Exception $e) {
                            error_log("Notification Error in header: " . $e->getMessage());
                        }
                    }
                    ?>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_page == 'disposisi') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= $base_url ?>modules/disposisi/index.php">
                            <span>Disposisi</span>
                            <?php if($unreadCount > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= ($active_page == 'dashboard') ? 'active' : '' ?>" href="<?= $base_url ?>index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_page == 'kegiatan') ? 'active' : '' ?>" href="<?= $base_url ?>kegiatan.php">Kegiatan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_page == 'galeri') ? 'active' : '' ?>" href="<?= $base_url ?>galeri.php">Galeri</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_page == 'ekinerja') ? 'active' : '' ?>" href="<?= $base_url ?>ekinerja.php">e-Kinerja</a>
                    </li>
                    <?php if (is_management_role()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_page == 'rekapitulasi') ? 'active' : '' ?>" href="<?= $base_url ?>laporan_rekapitulasi.php">Rekap</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_page == 'output_kinerja') ? 'active' : '' ?>" href="<?= $base_url ?>output_kinerja.php">Output Kinerja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_page == 'users') ? 'active' : '' ?>" href="<?= $base_url ?>users.php">User</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($active_page == 'integrasi') ? 'active' : '' ?>" href="<?= $base_url ?>modules/integrasi/settings.php">Integrasi API</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav align-items-center">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if ($user_photo): ?>
                                <img src="<?= $user_photo ?>" alt="Profile" class="rounded-circle me-2" style="width: 36px; height: 36px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);">
                            <?php else: ?>
                                <div class="bg-secondary bg-opacity-25 rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person-fill fs-6"></i>
                                </div>
                            <?php endif; ?>
                            <span class="fw-semibold"><?= htmlspecialchars($_SESSION['nama']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li><h6 class="dropdown-header">Login: <?= ucfirst($_SESSION['role']) ?></h6></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>profil.php"><i class="bi bi-person-gear me-2"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $base_url ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Header (Elegant Sage Style) -->
    <header class="d-lg-none header-elegant shadow-sm">
        <div class="container d-flex justify-content-between align-items-center h-100 px-3">
            <a href="<?= $base_url ?>index.php" class="text-decoration-none d-flex align-items-center">
                <div class="ms-1">
                    <span class="brand fw-extrabold d-block lh-1" style="color: var(--text-main); letter-spacing: -0.5px; font-size: 1.2rem;">BESUK SAE</span>
                    <span class="small text-muted fw-bold d-block" style="font-size: 0.65rem; color: var(--primary-color) !important; letter-spacing: 0.2px; text-transform: lowercase;">Melayani setulus hati</span>
                </div>
            </a>
            <div class="d-flex align-items-center">
                <span class="header-greeting d-inline-block">Hi, <?= explode(' ', $_SESSION['nama'])[0] ?></span>
                <div class="dropdown">
                    <a href="#" class="text-dark fs-3 d-flex align-items-center" data-bs-toggle="dropdown">
                        <?php if ($user_photo): ?>
                            <img src="<?= $user_photo ?>" alt="Profile" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover; border: 2px solid var(--primary-color); opacity: 0.95;">
                        <?php else: ?>
                            <i class="bi bi-person-circle text-primary" style="opacity: 0.85; font-size: 1.8rem;"></i>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-3 p-2 rounded-4">
                        <li><div class="dropdown-header small fw-bold text-muted">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></div></li>
                        <li><a class="dropdown-item py-2 px-3 rounded-3" href="<?= $base_url ?>profil.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                        <li><hr class="dropdown-divider opacity-10"></li>
                        <li><a class="dropdown-item text-danger py-2 px-3 rounded-3 fw-bold" href="<?= $base_url ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Bottom Navigation Elegant Sage -->
    <nav class="bottom-nav d-lg-none shadow-lg">
        <?php if (is_management_role()): ?>
            <!-- Admin & Structural Bottom Nav -->
            <a href="<?= $base_url ?>index.php" class="bottom-nav-item <?= ($active_page == 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i>
                <span class="fw-bold">Beranda</span>
            </a>
            <a href="<?= $base_url ?>modules/disposisi/index.php" class="bottom-nav-item <?= ($active_page == 'disposisi') ? 'active' : '' ?>">
                <div class="position-relative d-inline-block">
                    <i class="bi bi-envelope-paper<?= ($active_page == 'disposisi') ? '-fill' : '' ?>"></i>
                    <?php if($unreadCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.55rem; padding: 0.25em 0.45em; border: 2px solid white;">
                            <?= $unreadCount ?>
                        </span>
                    <?php endif; ?>
                </div>
                <span class="fw-bold">Disposisi</span>
            </a>
            <a href="<?= $base_url ?>output_kinerja.php" class="bottom-nav-item <?= ($active_page == 'output_kinerja') ? 'active' : '' ?>">
                <i class="bi bi-list-check"></i>
                <span class="fw-bold">Output</span>
            </a>
            <a href="<?= $base_url ?>users.php" class="bottom-nav-item <?= ($active_page == 'users') ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span class="fw-bold">User</span>
            </a>
            <a href="<?= $base_url ?>modules/integrasi/settings.php" class="bottom-nav-item <?= ($active_page == 'integrasi') ? 'active' : '' ?>">
                <i class="bi bi-gear-fill"></i>
                <span class="fw-bold">Integrasi</span>
            </a>
        <?php else: ?>
            <!-- Staff Field Bottom Nav -->
            <a href="<?= $base_url ?>index.php" class="bottom-nav-item <?= ($active_page == 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-house-door<?= ($active_page == 'dashboard') ? '-fill' : '' ?>"></i>
                <span class="fw-bold">Beranda</span>
            </a>
            <a href="<?= $base_url ?>kegiatan.php" class="bottom-nav-item <?= ($active_page == 'kegiatan') ? 'active' : '' ?>">
                <i class="bi bi-calendar-event<?= ($active_page == 'kegiatan') ? '-fill' : '' ?>"></i>
                <span class="fw-bold">Kegiatan</span>
            </a>
            <div class="bottom-nav-center">
                <a href="<?= $base_url ?>camera.php" class="btn-fab-elegant">
                    <i class="bi bi-camera-fill"></i>
                </a>
            </div>
            <a href="<?= $base_url ?>modules/disposisi/index.php" class="bottom-nav-item <?= ($active_page == 'disposisi') ? 'active' : '' ?>">
                <div class="position-relative d-inline-block">
                    <i class="bi bi-envelope<?= ($active_page == 'disposisi') ? '-fill' : '' ?>"></i>
                    <?php if($unreadCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.55rem; padding: 0.25em 0.45em; border: 2px solid white;">
                            <?= $unreadCount ?>
                        </span>
                    <?php endif; ?>
                </div>
                <span class="fw-bold">Inbox</span>
            </a>
            <a href="<?= $base_url ?>profil.php" class="bottom-nav-item <?= ($active_page == 'profil') ? 'active' : '' ?>">
                <i class="bi bi-person-circle<?= ($active_page == 'profil') ? '-fill' : '' ?>"></i>
                <span class="fw-bold">Akun</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="container <?= ($active_page == 'dashboard') ? 'pt-2' : 'pt-0' ?>">

<?php
/**
 * Navigation Component
 * Bottom navigation untuk mobile
 */

// Tentukan halaman aktif
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="bottom-nav">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            </span>
            <span class="nav-label">BERANDA</span>
        </a>
        
        <a href="surat-masuk.php" class="nav-item <?php echo $currentPage === 'surat-masuk' || $currentPage === 'surat-detail' ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </span>
            <span class="nav-label">NASKAH</span>
        </a>
        
        <a href="disposisi.php" class="nav-item <?php echo $currentPage === 'disposisi' ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            </span>
            <span class="nav-label">INSTRUKSI</span>
        </a>
        
        <a href="laporan-disposisi.php" class="nav-item <?php echo $currentPage === 'laporan-disposisi' ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M9 15h6"></path><path d="M9 11h6"></path></svg>
            </span>
            <span class="nav-label">LAPORAN</span>
        </a>
        
        <a href="monitoring.php" class="nav-item <?php echo $currentPage === 'monitoring' ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
            </span>
            <span class="nav-label">MONITORING</span>
        </a>
        
        <a href="persetujuan-laporan.php" class="nav-item <?php echo $currentPage === 'persetujuan-laporan' ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </span>
            <span class="nav-label">VALIDASI</span>
        </a>
    </div>
</nav>

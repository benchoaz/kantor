<?php
define('APP_INIT', true);
require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAuth();

$pageTitle = 'Profil Pimpinan';
$user = getCurrentUser();
$api = new ApiClient();

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ENABLE_CSRF_PROTECTION && !verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlashMessage('error', 'Token keamanan tidak valid');
        redirect('profile.php');
    }

    // Aksi Ubah Foto
    if (isset($_FILES['photo'])) {
        $file = $_FILES['photo'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $dataPhoto = [
                'photo' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])
            ];
            
            $response = $api->post('/pimpinan/update-photo', $dataPhoto);
            
            if ($response['success']) {
                setFlashMessage('success', 'Foto profil berhasil diperbarui');
                if (isset($response['data']['photo_url'])) {
                    $_SESSION['photo_url'] = $response['data']['photo_url'];
                }
            } else {
                setFlashMessage('error', $response['message'] ?? 'Gagal mengunggah foto');
            }
        } else {
            setFlashMessage('error', 'Terjadi kesalahan saat mengunggah file');
        }
        redirect('profile.php');
    }
    
    // Aksi Ubah Password
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        
        if (strlen($newPass) < 6) {
            setFlashMessage('error', 'Password baru minimal 6 karakter');
        } elseif ($newPass !== $confirmPass) {
            setFlashMessage('error', 'Konfirmasi password tidak cocok');
        } else {
            $resp = $api->updatePassword($currentPass, $newPass, $confirmPass);
            if ($resp['success']) {
                setFlashMessage('success', 'Password berhasil diubah, Pak!');
            } else {
                $msg = $resp['message'] ?? 'Gagal mengubah password';
                if (isset($resp['errors'])) {
                    $errList = [];
                    foreach ($resp['errors'] as $e) {
                        $errList[] = is_array($e) ? implode(', ', $e) : $e;
                    }
                    $msg .= ': ' . implode('; ', $errList);
                }
                setFlashMessage('error', $msg);
            }
        }
        redirect('profile.php');
    }
}

// Get latest profile data
$response = $api->get('/pimpinan/profile');
if ($response['success'] && isset($response['data'])) {
    $profileData = $response['data'];
    // Update local session data to keep it fresh
    $_SESSION['name'] = $profileData['name'] ?? $_SESSION['name'];
    $_SESSION['photo_url'] = $profileData['photo_url'] ?? ($_SESSION['photo_url'] ?? null);
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Profil Pimpinan</h1>
    <p class="page-subtitle">Kelola informasi diri dan foto profil</p>
</div>

<div class="card">
    <div style="display: flex; flex-direction: column; align-items: center; padding: var(--space-xl) 0;">
        <div class="profile-avatar-container">
            <?php if (!empty($_SESSION['photo_url'])): ?>
                <img src="<?php echo e($_SESSION['photo_url']); ?>" alt="Profile" class="profile-avatar">
            <?php else: ?>
                <div class="profile-avatar-placeholder">
                    <?php echo substr($user['name'], 0, 1); ?>
                </div>
            <?php endif; ?>
            
            <form id="photoForm" method="POST" enctype="multipart/form-data" class="photo-upload-overlay">
                <?php echo csrfField(); ?>
                <input type="file" name="photo" id="photoInput" accept="image/*" onchange="document.getElementById('photoForm').submit()" style="display: none;">
                <label for="photoInput" class="upload-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                </label>
            </form>
        </div>
        
        <h2 style="margin: var(--space-lg) 0 var(--space-xs); font-family: var(--font-secondary);"><?php echo e($user['name']); ?></h2>
        <span class="badge badge-info"><?php echo getRoleDisplayName($user['role']); ?></span>
    </div>

    <div style="border-top: 1px solid var(--light-gray); padding-top: var(--space-xl); margin-top: var(--space-xl);">
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" value="<?php echo e($user['username']); ?>" disabled style="background: var(--bg-secondary); color: var(--text-tertiary);">
        </div>
        <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" class="form-control" value="<?php echo e($user['name']); ?>" disabled style="background: var(--bg-secondary); color: var(--text-tertiary);">
        </div>
    </div>
</div>

<div class="card" style="margin-top: var(--space-lg);">
    <div class="card-header">
        <h3 class="card-title">Keamanan Akun</h3>
    </div>
    
    <form method="POST" action="profile.php">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="change_password">
        
        <div class="form-group">
            <label class="form-label" for="current_password">Password Saat Ini</label>
            <input type="password" name="current_password" id="current_password" class="form-control" placeholder="••••••••" required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="new_password">Password Baru</label>
            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="••••••••" required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-md);">
            Ubah Password
        </button>
    </form>
</div>
<div style="height: 20px;"></div>

<style>
.profile-avatar-container {
    position: relative;
    width: 150px;
    height: 150px;
}

.profile-avatar, .profile-avatar-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--white);
    box-shadow: var(--shadow-lg);
}

.profile-avatar-placeholder {
    background: linear-gradient(135deg, var(--sage-green) 0%, var(--sage-green-dark) 100%);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
    font-weight: 800;
    font-family: var(--font-secondary);
}

.photo-upload-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
}

.upload-btn {
    width: 44px;
    height: 44px;
    background: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--sage-green-dark);
    cursor: pointer;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
    border: none;
}

.upload-btn:hover {
    transform: scale(1.1);
    color: var(--sage-green);
    box-shadow: var(--shadow-lg);
}
</style>

<?php include 'includes/footer.php'; ?>

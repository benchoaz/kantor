<?php
// upload_foto_profil.php - AJAX handler for profile photo upload
require_once 'config/database.php';
require_once 'includes/auth.php';
require_login();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'photo_url' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Get current photo
        $stmt = $pdo->prepare("SELECT foto_profil FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $old_photo = $user['foto_profil'] ?? null;
        
        // Validate file upload
        if (!isset($_FILES['foto_profil']) || $_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Gagal mengupload file. Silakan coba lagi.');
        }
        
        $file_tmp = $_FILES['foto_profil']['tmp_name'];
        $file_size = $_FILES['foto_profil']['size'];
        $file_ext = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg'];
        
        // Validate file type
        if (!in_array($file_ext, $allowed)) {
            throw new Exception('Format foto tidak didukung. Gunakan JPG atau PNG.');
        }
        
        // Validate file size (max 2MB)
        if ($file_size > 2 * 1024 * 1024) {
            throw new Exception('Ukuran foto terlalu besar. Maksimal 2MB.');
        }
        
        // Generate unique filename
        $new_name = 'profil_' . $user_id . '_' . time() . '.' . $file_ext;
        $upload_dir = 'uploads/profil/';
        
        // Create directory if not exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
            throw new Exception('Gagal menyimpan foto. Silakan coba lagi.');
        }
        
        // Update database
        $stmt = $pdo->prepare("UPDATE users SET foto_profil = ? WHERE id = ?");
        $stmt->execute([$new_name, $user_id]);
        
        // Delete old photo if exists
        if ($old_photo && file_exists($upload_dir . $old_photo)) {
            unlink($upload_dir . $old_photo);
        }
        
        $response['success'] = true;
        $response['message'] = 'Foto profil berhasil diperbarui!';
        $response['photo_url'] = $upload_dir . $new_name . '?v=' . time();
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);

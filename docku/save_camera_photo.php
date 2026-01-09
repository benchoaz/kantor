<?php
// save_camera_photo.php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Increase memory limit for image processing if needed
ini_set('memory_limit', '256M');

header('Content-Type: application/json');

// Manual role check instead of require_role to ensure JSON response
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Sesi berakhir. Silakan login kembali.']);
    exit;
}

if (!has_role(['admin', 'operator', 'pimpinan', 'staff'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin untuk menyimpan foto.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['image'])) {
    echo json_encode(['success' => false, 'message' => 'Image data missing']);
    exit;
}

$imageData = $input['image'];
$lat = $input['lat'] ?? null;
$lon = $input['lon'] ?? null;

// Clean base64 string
$imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
$imageData = str_replace(' ', '+', $imageData);
$decoded = base64_decode($imageData);
if ($decoded === false) {
    error_log("save_camera_photo.php: Base64 decode failed");
    echo json_encode(['success' => false, 'message' => 'Base64 decode failed']);
    exit;
}

$fileSize = strlen($decoded);
error_log("save_camera_photo.php: Decoded size: " . $fileSize . " bytes");

$fileHash = md5($decoded);
// Optional duplicate check
try {
    $stmt = $pdo->prepare("SELECT file FROM foto_kegiatan WHERE file_hash = ? LIMIT 1");
    $stmt->execute([$fileHash]);
    $existing = $stmt->fetch();
    if ($existing) {
        error_log("save_camera_photo.php: Duplicate detected: " . $existing['file']);
        echo json_encode(['success' => true, 'filename' => $existing['file'], 'is_duplicate' => true]);
        exit;
    }
} catch (Throwable $e) {
    error_log("save_camera_photo.php: DB Check Error: " . $e->getMessage());
}

$uploadDir = 'uploads/foto/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        error_log("save_camera_photo.php: Failed to create dir: " . $uploadDir);
    }
}

$fileName = 'CAM_' . date('YmdHis') . '_' . uniqid() . '.jpg';
$filePath = $uploadDir . $fileName;

if (!file_put_contents($filePath, $decoded)) {
    $err = error_get_last();
    error_log("save_camera_photo.php: Failed to save file. OS Error: " . json_encode($err));
    echo json_encode(['success' => false, 'message' => 'Failed to save file: ' . ($err['message'] ?? 'Unknown error')]);
    exit;
}
// Finalize file save
if (!file_put_contents($filePath, $decoded)) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file di server.']);
    exit;
}

echo json_encode(['success' => true, 'filename' => $fileName]);
?>

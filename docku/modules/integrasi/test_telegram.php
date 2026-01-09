<?php
// modules/integrasi/test_telegram.php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

// 1. Check if token exists
$stmt = $pdo->prepare("SELECT outbound_key FROM integrasi_config WHERE label = 'Telegram' AND is_active = 1 LIMIT 1");
$stmt->execute();
$token = $stmt->fetchColumn();

if (!$token) {
    echo json_encode(['status' => 'error', 'message' => 'Token belum disimpan atau tidak aktif.']);
    exit;
}

// 2. Check if current admin has telegram_id
$stmtUser = $pdo->prepare("SELECT telegram_id FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$chatId = $stmtUser->fetchColumn();

if (!$chatId) {
    echo json_encode(['status' => 'error', 'message' => 'Anda belum mendaftarkan Chat ID di menu Profil.']);
    exit;
}

// 3. Try sending a test message
$message = "🛠 <b>BESUK SAE BOT TEST</b>\n\n";
$message .= "Selamat! Bot Telegram Anda telah berhasil terhubung dengan sistem BESUK SAE.\n\n";
$message .= "<b>Host:</b> " . $_SERVER['HTTP_HOST'] . "\n";
$message .= "<b>Waktu:</b> " . date('d/m/Y H:i:s') . "\n";

$url = "https://api.telegram.org/bot{$token}/sendMessage";
$data = [
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'HTML'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resData = json_decode($response, true);

if ($httpCode === 200 && isset($resData['ok']) && $resData['ok'] === true) {
    echo json_encode([
        'status' => 'success', 
        'message' => 'Pesan test telah dikirim ke Telegram Anda. Periksa HP Anda!'
    ]);
} else {
    $errorMsg = $resData['description'] ?? 'Gagal menghubungi server Telegram.';
    echo json_encode([
        'status' => 'error', 
        'message' => 'Telegram Error: ' . $errorMsg
    ]);
}

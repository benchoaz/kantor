<?php
// includes/notification_helper.php

/**
 * Get count of unread dispositions for a user
 * Updated to use status_followup for Camat integration
 */
function getUnreadDispositionCount($pdo, $userId) {
    if (!$userId) return 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM disposisi_penerima WHERE user_id = ? AND status_followup IN ('pending', 'in_progress')");
    $stmt->execute([$userId]);
    return intval($stmt->fetchColumn());
}

/**
 * Get latest unread disposition for popup
 */
function getLatestUnreadDisposition($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT d.id, d.perihal, d.instruksi, dp.id as penerima_id 
        FROM disposisi d 
        JOIN disposisi_penerima dp ON d.id = dp.disposisi_id 
        WHERE dp.user_id = ? AND dp.status = 'baru' 
        ORDER BY d.created_at DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Mark disposition as read
 */
function markDispositionAsRead($pdo, $penerimaId, $userId) {
    $stmt = $pdo->prepare("UPDATE disposisi_penerima SET status = 'dibaca', tgl_dibaca = NOW() WHERE id = ? AND user_id = ? AND status = 'baru'");
    return $stmt->execute([$penerimaId, $userId]);
}

/**
 * Generate WhatsApp Link (helper)
 */
function generateWaLink($phone, $text) {
    // Basic sanitization
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    }
    return "https://wa.me/" . $phone . "?text=" . urlencode($text);
}

/**
 * Send Telegram Notification to Leadership (Admins)
 */
function sendTelegramNotification($pdo, $message) {
    // 1. Get Telegram Bot Token
    $stmt = $pdo->prepare("SELECT outbound_key FROM integrasi_config WHERE label = 'Telegram' AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $botToken = $stmt->fetchColumn();

    if (!$botToken) return false;

    // 2. Get all Admins with Telegram ID
    $stmtAdmins = $pdo->prepare("SELECT telegram_id FROM users WHERE role = 'admin' AND telegram_id IS NOT NULL AND telegram_id != ''");
    $stmtAdmins->execute();
    $admins = $stmtAdmins->fetchAll(PDO::FETCH_COLUMN);

    if (empty($admins)) return false;

    // 3. Send via Telegram API
    foreach ($admins as $chatId) {
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
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
        curl_close($ch);
        
        // Log locally if needed
        error_log("Telegram Send to $chatId: $response");
    }

    return true;
}
?>

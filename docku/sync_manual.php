<?php
// sync_manual.php - Manual User Sync Tool
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/integration_helper.php';
require_role(['admin']);

// ✅ IMPORTANT: Use forceSync=true untuk manual sync
// Auto-sync di user_edit dan user_tambah sudah dimatikan untuk safety
$result = syncUsersToCamas($pdo, true);

if ($result['success']) {
    if (isset($result['skipped']) && $result['skipped']) {
        $msg = urlencode("Sinkronisasi di-skip (mode aman)");
        header("Location: users.php?msg=info&txt=$msg");
    } else {
        $msg = urlencode($result['message'] . " (" . $result['count'] . " user)");
        header("Location: users.php?msg=synced&txt=$msg");
    }
} else {
    $error = urlencode($result['message']);
    header("Location: users.php?msg=sync_error&txt=$error");
}
exit;


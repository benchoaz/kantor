<?php
// fix_login.php
// Upload file ini ke cPanel, lalu buka di browser: domain.com/fix_login.php
// HAPUS FILE INI SETELAH SELESAI!

require_once 'config/database.php';

$new_password = password_hash('admin123', PASSWORD_DEFAULT);
$username = 'admin';

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$new_password, $username]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Password admin berhasil direset menjadi: <b>admin123</b><br>";
        echo "Silakan login kembali dan <b>SEGERA HAPUS</b> file fix_login.php ini dari cPanel.";
    } else {
        echo "❌ User 'admin' tidak ditemukan. Pastikan Anda sudah import database.sql";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

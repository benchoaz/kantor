<?php
// tools/reset_admin.php
require_once '../config/database.php';

$username = 'admin';
$new_password = 'admin123';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hash, $username]);
    
    if ($stmt->rowCount() > 0) {
        echo "<h1>Sukses!</h1>";
        echo "<p>Password untuk user <strong>admin</strong> telah direset menjadi: <strong>admin123</strong></p>";
    } else {
        // Jika user admin tidak ditemukan, coba buat baru
        $stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Administrator', 'admin', $hash, 'admin']);
        echo "<h1>User Berhasil Dibuat!</h1>";
        echo "<p>User <strong>admin</strong> tidak ditemukan sebelumnya, sekarang telah dibuat dengan password: <strong>admin123</strong></p>";
    }
    echo '<p><a href="../login.php">Ke Halaman Login</a></p>';
} catch (Exception $e) {
    echo "<h1>Gagal!</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

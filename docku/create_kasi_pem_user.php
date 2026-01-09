<?php
/**
 * create_kasi_pem_user.php
 * Script untuk otomatis membuat user Kasi Pemerintahan di Docku
 */

require_once 'config/database.php';

echo "=== CREATE USER KASI PEMERINTAHAN ===\n\n";

// 1. Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute(['kasi_pem']);
if ($stmt->fetch()) {
    echo "❌ User 'kasi_pem' sudah ada!\n";
    exit;
}

// 2. Insert user
try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO users (username, nama, password, role, jabatan, nip, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        'kasi_pem',                                                          // username
        'BENI TRISNA WIJAYA',                                               // nama
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',   // password = "password"
        'pimpinan',                                                         // role
        'Kasi Pemerintahan',                                                // jabatan
        '198205192010011010'                                                // nip
    ]);
    
    $userId = $pdo->lastInsertId();
    $pdo->commit();
    
    echo "✅ User berhasil dibuat!\n\n";
    
    // 3. Show created user
    $stmt = $pdo->prepare("SELECT id, username, nama, jabatan, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Detail user:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Username: {$user['username']}\n";
    echo "  Nama: {$user['nama']}\n";
    echo "  Jabatan: {$user['jabatan']}\n";
    echo "  Role: {$user['role']}\n";
    echo "  Password: password (default)\n\n";
    
    echo "🎯 Langkah selanjutnya:\n";
    echo "  1. Login ke Docku sebagai admin\n";
    echo "  2. Buka User Management\n";
    echo "  3. Klik tombol 'Sinkron ke Pimpinan'\n";
    echo "  4. User akan ter-sync ke API Camat\n\n";
    
    echo "⚠️ PENTING:\n";
    echo "  - Password default: 'password'\n";
    echo "  - Instruksikan user untuk ganti password setelah login\n\n";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "=== SELESAI ===\n";
?>

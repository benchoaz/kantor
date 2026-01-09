<?php
// fix_kosi_pem.php - Fix user kosi_pem
require_once 'config/database.php';

echo "=== FIX USER KOSI_PEM ===\n\n";

// 1. Cek user kosi_pem
$stmt = $pdo->prepare("SELECT id, username, nama, jabatan, role FROM users WHERE username = ?");
$stmt->execute(['kosi_pem']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "❌ User 'kosi_pem' tidak ditemukan!\n";
    exit;
}

echo "User ditemukan:\n";
echo "  ID: {$user['id']}\n";
echo "  Username: {$user['username']}\n";
echo "  Nama: {$user['nama']}\n";
echo "  Jabatan: {$user['jabatan']}\n";
echo "  Role: {$user['role']}\n\n";

// 2. Cek apakah role-nya masuk filter sync
$excludedRoles = ['admin', 'operator', 'staff', 'camat'];
if (in_array($user['role'], $excludedRoles)) {
    echo "❌ MASALAH: Role '{$user['role']}' di-EXCLUDE dari sync!\n";
    echo "   User dengan role ini TIDAK akan di-sync ke API.\n\n";
    echo "💡 SOLUSI: Ubah role menjadi 'pimpinan'\n\n";
    
    // Tawarkan fix
    echo "Jalankan command ini untuk fix:\n";
    echo "UPDATE users SET role = 'pimpinan' WHERE id = {$user['id']};\n\n";
} else {
    echo "✅ Role '{$user['role']}' OK, seharusnya ikut sync.\n\n";
}

// 3. Cek apakah username kosong atau NULL
if (empty($user['username'])) {
    echo "❌ MASALAH: Username kosong/NULL!\n\n";
} else {
    echo "✅ Username OK.\n\n";
}

// 4. Info typo
echo "⚠️ CATATAN: Username adalah 'kosi_pem' (dengan huruf O)\n";
echo "   Jika ingin ganti jadi 'kasi_pem' (dengan huruf A):\n";
echo "   UPDATE users SET username = 'kasi_pem' WHERE id = {$user['id']};\n\n";

echo "=== SELESAI ===\n";
?>

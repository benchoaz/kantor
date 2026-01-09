<?php
// check_kasi_pemerintahan.php - Debug script untuk cek user Kasi Pemerintahan
require_once 'config/database.php';

echo "=== CHECK USER KASI PEMERINTAHAN ===\n\n";

// 1. Cek user dengan jabatan Kasi Pemerintahan
echo "1. User dengan jabatan 'Kasi Pemerintahan':\n";
$stmt = $pdo->query("SELECT id, username, nama, jabatan, role FROM users WHERE jabatan LIKE '%Pemerintahan%'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "   ❌ TIDAK ADA user dengan jabatan Kasi Pemerintahan!\n\n";
} else {
    foreach ($users as $user) {
        echo "   ✓ ID: {$user['id']}, Username: {$user['username']}, Nama: {$user['nama']}, Jabatan: {$user['jabatan']}, Role: {$user['role']}\n";
    }
    echo "\n";
}

// 2. Cek semua user yang seharusnya di-sync (sesuai filter)
echo "2. User yang seharusnya di-sync (exclude admin/operator/staff/camat):\n";
$stmt = $pdo->query("
    SELECT id, username, nama, jabatan, role 
    FROM users 
    WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')
    AND username IS NOT NULL 
    AND username != ''
    ORDER BY id
");
$syncable = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "   Total: " . count($syncable) . " user\n";
foreach ($syncable as $user) {
    echo "   - {$user['username']} ({$user['nama']}) - {$user['jabatan']} [{$user['role']}]\n";
}
echo "\n";

// 3. Cek user dengan nama mengandung "pemerintahan"
echo "3. User dengan nama mengandung 'pemerintahan':\n";
$stmt = $pdo->query("SELECT id, username, nama, jabatan, role FROM users WHERE nama LIKE '%pemerintahan%'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "   ❌ Tidak ada\n\n";
} else {
    foreach ($users as $user) {
        echo "   ✓ ID: {$user['id']}, Username: {$user['username']}, Nama: {$user['nama']}, Jabatan: {$user['jabatan']}, Role: {$user['role']}\n";
    }
    echo "\n";
}

// 4. Cek semua jabatan Kasi yang ada
echo "4. Semua jabatan Kasi yang ada:\n";
$stmt = $pdo->query("SELECT DISTINCT jabatan FROM users WHERE jabatan LIKE 'Kasi%' ORDER BY jabatan");
$jabatans = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($jabatans as $jabatan) {
    echo "   - $jabatan\n";
}
echo "\n";

// 5. Cek user dengan username mengandung "pem"
echo "5. User dengan username mengandung 'pem':\n";
$stmt = $pdo->query("SELECT id, username, nama, jabatan, role FROM users WHERE username LIKE '%pem%'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "   ❌ Tidak ada\n\n";
} else {
    foreach ($users as $user) {
        echo "   ✓ ID: {$user['id']}, Username: {$user['username']}, Nama: {$user['nama']}, Jabatan: {$user['jabatan']}, Role: {$user['role']}\n";
    }
    echo "\n";
}

echo "=== SELESAI ===\n";
?>

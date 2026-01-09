<?php
// test_sync_dry_run.php - Test script untuk verifikasi sync logic
require_once 'config/database.php';

echo "=== DOCKU SYNC TEST (DRY RUN) ===\n\n";

// Simulate the query that will be used
$stmtUser = $pdo->query("
    SELECT id, username, nama, jabatan, role 
    FROM users 
    WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')
    ORDER BY id
");
$users = $stmtUser->fetchAll(PDO::FETCH_ASSOC);

echo "Total users yang akan disinkronkan: " . count($users) . "\n\n";

echo "Data yang akan dikirim ke API:\n";
echo "URL: https://api.sidiksae.my.id/api/v1/users/sync\n";
echo "API Key: sk_live_docku_x9y8z7w6v5u4t3s2\n\n";

echo "Sample users (first 5):\n";
foreach (array_slice($users, 0, 5) as $idx => $user) {
    echo ($idx + 1) . ". ID: {$user['id']}, Username: {$user['username']}, Nama: {$user['nama']}, Jabatan: {$user['jabatan']}, Role: {$user['role']}\n";
}

if (count($users) > 5) {
    echo "... dan " . (count($users) - 5) . " user lainnya\n";
}

echo "\nPayload JSON (first 3 users):\n";
$samplePayload = ['users' => array_slice($users, 0, 3)];
echo json_encode($samplePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n=== TEST SELESAI ===\n";
echo "Untuk test REAL sync, silakan klik tombol 'Sinkron Ke Pimpinan' di halaman users.php\n";
?>

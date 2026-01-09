<?php
// tools/seed_users.php
require_once __DIR__ . '/../config/database.php';

echo "Seeding Users...\n";

$default_pass = 'besuk123';
$hashed_pass = password_hash($default_pass, PASSWORD_DEFAULT);

$users = [
    // Format: [username, nama, role, jabatan, bidang_id]
    
    // Sekretariat (ID 1)
    ['sekcam', 'Sekretaris Camat', 'pimpinan', 'Sekretaris Camat', 1],
    ['kasi_keu', 'Kasubbag Perencanaan & Keuangan', 'pimpinan', 'Kasubbag Perencanaan dan Keuangan', 1],
    ['kasi_umum', 'Kasubbag Umum & Kepegawaian', 'pimpinan', 'Kasubbag Umum dan Kepegawaian', 1],
    ['staff_keu', 'Staf Perencanaan & Keuangan', 'operator', 'Staf Sekretariat', 1],
    ['staff_umum', 'Staf Umum & Kepegawaian', 'operator', 'Staf Sekretariat', 1],

    // Pemerintahan (ID 2) - Assuming none for now based on request, or maybe add later.

    // Ekonomi & Pembangunan (ID 3)
    ['kasi_ebang', 'Kasi Ekonomi & Pembangunan', 'pimpinan', 'Kasi Ekonomi dan Pembangunan', 3],
    ['staff_ebang', 'Staf Ekonomi & Pembangunan', 'operator', 'Staf Ekonomi dan Pembangunan', 3],

    // Kesejahteraan Rakyat (ID 4)
    ['kasi_kesra', 'Kasi Kesejahteraan Rakyat', 'pimpinan', 'Kasi Kesejahteraan Rakyat', 4],
    ['staff_kesra', 'Staf Kesejahteraan Rakyat', 'operator', 'Staf Kesejahteraan Rakyat', 4],

    // Trantibum (ID 5)
    ['kasi_trantib', 'Kasi Trantibum', 'pimpinan', 'Kasi Trantibum', 5],
    ['staff_trantib', 'Staf Trantibum', 'operator', 'Staf Trantibum', 5],

    // Global Operator (All Bidang)
    ['operator', 'Operator Dokumentasi', 'operator', 'Operator Dokumentasi', null],
];

$count = 0;
foreach ($users as $u) {
    try {
        // Check if exists
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$u[0]]);
        $exists = $check->fetch();

        if ($exists) {
            // Update existing
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, role = ?, jabatan = ?, bidang_id = ?, password = ? WHERE username = ?");
            $stmt->execute([
                $u[1], 
                $u[2], 
                $u[3], 
                $u[4],
                $hashed_pass, // Reset password to default as requested "ulangi"
                $u[0]
            ]);
            echo "[UPDATED] {$u[0]} (Bidang ID: {$u[4]})\n";
        } else {
            // Insert new
            $stmt = $pdo->prepare("INSERT INTO users (username, nama, role, jabatan, bidang_id, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $u[0], 
                $u[1], 
                $u[2], 
                $u[3],
                $u[4],
                $hashed_pass
            ]);
            echo "[CREATED] {$u[0]} (Bidang ID: {$u[4]})\n";
        }
        $count++;
    } catch (PDOException $e) {
        echo "[ERROR] {$u[0]}: " . $e->getMessage() . "\n";
    }
}

echo "Done. Created $count users.\n";

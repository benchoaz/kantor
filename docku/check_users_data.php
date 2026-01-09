<?php
// check_users_data.php - Check actual users data in production DB
require_once 'config/database.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== PRODUCTION DATABASE USERS CHECK ===\n\n";

// Get all structural users (what will be synced)
$query = "
    SELECT id, username, nama, jabatan, role 
    FROM users 
    WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')
    AND username IS NOT NULL 
    AND username != ''
    ORDER BY id
";

echo "Query:\n$query\n\n";

$stmt = $pdo->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total users that WILL be synced: " . count($users) . "\n\n";

echo "=== USERS LIST ===\n";
foreach ($users as $idx => $user) {
    $usernameSafe = json_encode($user['username']); // Shows whitespace, special chars
    $usernameLen = strlen($user['username']);
    
    echo ($idx + 1) . ". ID: {$user['id']}\n";
    echo "   Username: {$usernameSafe} (length: {$usernameLen})\n";
    echo "   Nama: {$user['nama']}\n";
    echo "   Jabatan: {$user['jabatan']}\n";
    echo "   Role: {$user['role']}\n";
    
    // Check for problematic usernames
    if ($user['username'] === '') {
        echo "   ⚠️  WARNING: Empty string username!\n";
    }
    if (trim($user['username']) === '') {
        echo "   ⚠️  WARNING: Username is whitespace only!\n";
    }
    if (trim($user['username']) !== $user['username']) {
        echo "   ⚠️  WARNING: Username has leading/trailing whitespace!\n";
    }
    echo "\n";
}

echo "\n=== CHECK FOR EXCLUDED USERS ===\n";

// Check users that are EXCLUDED (should not sync)
$excludedQuery = "
    SELECT id, username, nama, role
    FROM users 
    WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')
    AND (username IS NULL OR username = '')
";

$stmtExcluded = $pdo->query($excludedQuery);
$excluded = $stmtExcluded->fetchAll(PDO::FETCH_ASSOC);

if (count($excluded) > 0) {
    echo "Found " . count($excluded) . " users that are EXCLUDED due to empty username:\n\n";
    foreach ($excluded as $idx => $user) {
        echo ($idx + 1) . ". ID: {$user['id']}, Username: " . json_encode($user['username']) . ", Nama: {$user['nama']}, Role: {$user['role']}\n";
    }
} else {
    echo "✅ No users with empty username found (good!)\n";
}

echo "\n=== PAYLOAD PREVIEW ===\n";
echo "This is what will be sent to API:\n\n";
echo json_encode(['users' => $users], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n=== END CHECK ===\n";
?>

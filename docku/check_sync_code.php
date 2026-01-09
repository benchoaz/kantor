<?php
// check_sync_code.php - Verify production file content
header('Content-Type: text/plain');

echo "=== PRODUCTION FILE VERIFICATION ===\n\n";

$file = __DIR__ . '/includes/integration_helper.php';

if (!file_exists($file)) {
    echo "❌ FILE NOT FOUND: $file\n";
    exit;
}

echo "✅ File exists: $file\n";
echo "File size: " . filesize($file) . " bytes\n";
echo "Last modified: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
echo "MD5: " . md5_file($file) . "\n\n";

// Read the relevant section
$content = file_get_contents($file);

echo "=== CHECKING FILTERS ===\n\n";

// Check 1: Username field in SELECT
if (strpos($content, 'SELECT id, username, nama, jabatan, role') !== false) {
    echo "✅ Username field found in SELECT\n";
} else {
    echo "❌ Username field MISSING in SELECT\n";
}

// Check 2: Role filter
if (strpos($content, "WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')") !== false) {
    echo "✅ Role filter found\n";
} else {
    echo "❌ Role filter MISSING\n";
}

// Check 3: Username NOT NULL filter
if (strpos($content, "username IS NOT NULL") !== false) {
    echo "✅ Username IS NOT NULL filter found\n";
} else {
    echo "❌ Username IS NOT NULL filter MISSING\n";
}

// Check 4: Username not empty filter
if (strpos($content, "username != ''") !== false || strpos($content, 'username != ""') !== false) {
    echo "✅ Username != '' filter found\n";
} else {
    echo "❌ Username != '' filter MISSING\n";
}

// Check 5: Correct API URL
if (strpos($content, 'https://api.sidiksae.my.id/api/v1/users/sync') !== false) {
    echo "✅ Correct API URL found\n";
} else {
    echo "❌ Correct API URL MISSING\n";
}

echo "\n=== QUERY SECTION ===\n\n";

// Extract the query section
if (preg_match('/stmtUser = \$pdo->query\(".*?"\);/s', $content, $matches)) {
    echo $matches[0] . "\n";
} else {
    echo "❌ Could not find query section\n";
}

echo "\n=== END VERIFICATION ===\n";
?>

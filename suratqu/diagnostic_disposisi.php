<?php
/**
 * DIAGNOSTIC: SuratQu HTTP 500 Error
 * Check disposisi.php dependencies and database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== SURATQU DISPOSISI.PHP DIAGNOSTIC ===\n\n";

// 1. Check session
echo "1. SESSION CHECK:\n";
session_start();
if (isset($_SESSION['id_user'])) {
    echo "   ✅ Session active, user ID: " . $_SESSION['id_user'] . "\n";
} else {
    echo "   ❌ No session - user not logged in\n";
    echo "   → Login required before accessing disposisi.php\n";
}
echo "\n";

// 2. Check database connection
echo "2. DATABASE CONFIG CHECK:\n";
if (file_exists('config/database.php')) {
    echo "   ✅ config/database.php exists\n";
    require_once 'config/database.php';
    
    // Check if $db or $conn variable exists
    if (isset($db)) {
        echo "   ✅ \$db variable available\n";
        $connection = $db;
    } elseif (isset($conn)) {
        echo "   ⚠️  \$conn variable (not \$db)\n";
        $connection = $conn;
    } elseif (isset($pdo)) {
        echo "   ⚠️  \$pdo variable (not \$db)\n";
        $connection = $pdo;
    } else {
        echo "   ❌ No database variable found!\n";
        echo "   → Check config/database.php returns \$db\n";
        $connection = null;
    }
} else {
    echo "   ❌ config/database.php NOT FOUND\n";
    $connection = null;
}
echo "\n";

// 3. Test database query
if ($connection) {
    echo "3. DATABASE QUERY TEST:\n";
    try {
        // Test simple query
        $stmt = $connection->prepare("SELECT COUNT(*) as total FROM disposisi");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "   ✅ Disposisi table accessible\n";
        echo "   Total records: " . ($result['total'] ?? 0) . "\n";
        
        // Check columns exist
        $stmt = $connection->query("SHOW COLUMNS FROM disposisi");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   Columns: " . implode(', ', $columns) . "\n";
        
        // Check required columns
        $required = ['id_disposisi', 'id_sm', 'pengirim_id', 'penerima_id', 'instruksi'];
        $missing = array_diff($required, $columns);
        if (empty($missing)) {
            echo "   ✅ All required columns present\n";
        } else {
            echo "   ❌ Missing columns: " . implode(', ', $missing) . "\n";
        }
        
    } catch (PDOException $e) {
        echo "   ❌ Database error: " . $e->getMessage() . "\n";
    }
} else {
    echo "3. DATABASE QUERY TEST: SKIPPED (no connection)\n";
}
echo "\n";

// 4. Check file includes
echo "4. FILE DEPENDENCY CHECK:\n";
$files = ['includes/functions.php', 'includes/header.php', 'includes/footer.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ $file NOT FOUND\n";
    }
}
echo "\n";

// 5. Check disposisi.php syntax
echo "5. PHP SYNTAX CHECK:\n";
$output = shell_exec('php -l disposisi.php 2>&1');
if (strpos($output, 'No syntax errors') !== false) {
    echo "   ✅ No syntax errors\n";
} else {
    echo "   ❌ Syntax error:\n";
    echo "   " . trim($output) . "\n";
}
echo "\n";

// 6. Error log check
echo "6. ERROR LOG CHECK:\n";
$logPaths = [
    'storage/logs/error.log',
    'error.log',
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log'
];

$foundLog = false;
foreach ($logPaths as $logPath) {
    if (file_exists($logPath) && is_readable($logPath)) {
        echo "   Found: $logPath\n";
        $lines = file($logPath);
        $recent = array_slice($lines, -20);
        
        // Filter for disposisi errors
        $relevantErrors = array_filter($recent, function($line) {
            return stripos($line, 'disposisi') !== false || 
                   stripos($line, 'fatal') !== false ||
                   stripos($line, 'error') !== false;
        });
        
        if (!empty($relevantErrors)) {
            echo "\n   Recent errors:\n";
            echo "   " . str_repeat("-", 70) . "\n";
            foreach (array_slice($relevantErrors, -5) as $error) {
                echo "   " . trim($error) . "\n";
            }
            echo "   " . str_repeat("-", 70) . "\n";
        } else {
            echo "   No relevant errors in last 20 lines\n";
        }
        $foundLog = true;
        break;
    }
}

if (!$foundLog) {
    echo "   ⚠️  No error log found or not readable\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n\n";

// RECOMMENDATIONS
echo "RECOMMENDATIONS:\n";
if (!isset($_SESSION['id_user'])) {
    echo "1. ❌ Login to SuratQu first\n";
}
if (!isset($db)) {
    echo "2. ❌ Fix database.php to export \$db variable\n";
    echo "   Ensure: return \$db; or global \$db;\n";
}

echo "\nTo run this diagnostic on production:\n";
echo "1. Upload to suratqu/diagnostic_disposisi.php\n";
echo "2. Access: https://suratqu.sidiksae.my.id/diagnostic_disposisi.php\n";
echo "3. Share output for analysis\n";

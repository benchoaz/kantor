<?php
/**
 * ===============================================
 * SURATQU UPLOAD DEBUG TOOL
 * Check why surat upload not registering to API
 * ===============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  SURATQU UPLOAD DEBUG TOOL                                    ║\n";
echo "║  Diagnosis: Why surat not registering to API                  ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Connect to SuratQu database
try {
    $connSQ = new PDO(
        "mysql:host=localhost;dbname=sidiksae_suratqu;charset=utf8mb4",
        "sidiksae_suratqu",
        "your_password_here" // UPDATE THIS
    );
    $connSQ->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to SuratQu database\n\n";
} catch (PDOException $e) {
    die("❌ Cannot connect to SuratQu database: " . $e->getMessage() . "\n");
}

// ============================================
// CHECK 1: Recent Uploads
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "CHECK 1: Recent Surat Uploads in SuratQu\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$stmt = $connSQ->query("
    SELECT 
        uuid, 
        no_agenda, 
        no_surat,
        perihal,
        file_path,
        status,
        created_at
    FROM surat_masuk 
    WHERE DATE(created_at) >= CURDATE() - INTERVAL 1 DAY
    ORDER BY created_at DESC 
    LIMIT 10
");

$recentUploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($recentUploads) > 0) {
    echo "Found " . count($recentUploads) . " recent uploads:\n\n";
    
    foreach ($recentUploads as $i => $upload) {
        echo ($i + 1) . ". {$upload['no_agenda']}\n";
        echo "   UUID: {$upload['uuid']}\n";
        echo "   Nomor: {$upload['no_surat']}\n";
        echo "   Perihal: " . substr($upload['perihal'], 0, 50) . "...\n";
        echo "   File: {$upload['file_path']}\n";
        echo "   Status: {$upload['status']}\n";
        echo "   Created: {$upload['created_at']}\n";
        
        // Check if file exists
        $filePath = __DIR__ . '/' . $upload['file_path'];
        if (file_exists($filePath)) {
            $fileSize = filesize($filePath);
            echo "   ✅ File exists (" . number_format($fileSize / 1024, 2) . " KB)\n";
        } else {
            echo "   ❌ File NOT FOUND: $filePath\n";
        }
        echo "\n";
    }
} else {
    echo "❌ No uploads found in last 24 hours\n";
    echo "   → Upload a test surat first!\n\n";
}

// ============================================
// CHECK 2: Integration Configuration
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "CHECK 2: API Integration Configuration\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$configFile = __DIR__ . '/config/integration.php';
if (file_exists($configFile)) {
    echo "✅ Config file exists: $configFile\n";
    $config = require $configFile;
    
    echo "\nAPI Settings:\n";
    echo "  - API URL: " . ($config['sidiksae']['api_url'] ?? 'NOT SET') . "\n";
    echo "  - API Version: " . ($config['sidiksae']['api_version'] ?? 'NOT SET') . "\n";
    echo "  - API Key: " . (isset($config['sidiksae']['api_key']) ? substr($config['sidiksae']['api_key'], 0, 20) . '...' : 'NOT SET') . "\n";
    echo "  - Client ID: " . ($config['sidiksae']['client_id'] ?? 'NOT SET') . "\n";
    echo "  - Enabled: " . (($config['sidiksae']['enabled'] ?? false) ? '✅ YES' : '❌ NO') . "\n";
    echo "  - Timeout: " . ($config['sidiksae']['timeout'] ?? 'NOT SET') . " seconds\n";
    echo "  - Source Base URL: " . ($config['source']['base_url'] ?? 'NOT SET') . "\n";
    
    if (!($config['sidiksae']['enabled'] ?? false)) {
        echo "\n❌ CRITICAL: API Integration is DISABLED!\n";
        echo "   → Enable it in config/integration.php\n";
    }
} else {
    echo "❌ Config file NOT FOUND: $configFile\n";
    echo "   → Create config/integration.php first!\n";
}

echo "\n";

// ============================================
// CHECK 3: Integration Log Table
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "CHECK 3: Integration Log Table\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $stmt = $connSQ->query("SHOW TABLES LIKE 'integrasi_docku_log'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Log table exists\n";
        
        // Check recent logs
        $stmt = $connSQ->query("
            SELECT * FROM integrasi_docku_log 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($logs) > 0) {
            echo "Recent API calls:\n\n";
            foreach ($logs as $i => $log) {
                echo ($i + 1) . ". Status: {$log['status']}\n";
                echo "   Response Code: {$log['response_code']}\n";
                echo "   Time: {$log['created_at']}\n";
                
                $payload = json_decode($log['payload'], true);
                if ($payload) {
                    echo "   UUID: " . ($payload['uuid_surat'] ?? 'N/A') . "\n";
                }
                
                $response = json_decode($log['response_body'], true);
                if ($response && !($response['success'] ?? false)) {
                    echo "   ❌ Error: " . ($response['error'] ?? 'Unknown') . "\n";
                }
                echo "\n";
            }
        } else {
            echo "⚠️  No logs found - No API calls made yet\n\n";
        }
    } else {
        echo "❌ Log table does NOT exist\n";
        echo "   → Run: suratqu/migrations/fix_error_500.sql\n\n";
    }
} catch (PDOException $e) {
    echo "❌ Error checking log table: " . $e->getMessage() . "\n\n";
}

// ============================================
// CHECK 4: Error Log File
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "CHECK 4: PHP Error Logs\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$errorLogPaths = [
    __DIR__ . '/storage/logs/error.log',
    __DIR__ . '/error.log',
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log'
];

$foundLog = false;
foreach ($errorLogPaths as $logPath) {
    if (file_exists($logPath) && is_readable($logPath)) {
        echo "✅ Found log: $logPath\n";
        $foundLog = true;
        
        // Read last 50 lines
        $lines = file($logPath);
        $recentLines = array_slice($lines, -50);
        
        // Filter for relevant errors
        $relevantErrors = array_filter($recentLines, function($line) {
            return stripos($line, 'surat') !== false || 
                   stripos($line, 'api') !== false ||
                   stripos($line, 'fatal') !== false ||
                   stripos($line, 'warning') !== false;
        });
        
        if (count($relevantErrors) > 0) {
            echo "\nRecent relevant errors:\n";
            echo str_repeat("-", 70) . "\n";
            foreach (array_slice($relevantErrors, -10) as $error) {
                echo trim($error) . "\n";
            }
            echo str_repeat("-", 70) . "\n";
        } else {
            echo "  No recent errors found\n";
        }
        break;
    }
}

if (!$foundLog) {
    echo "⚠️  No error log files found or not readable\n";
}

echo "\n";

// ============================================
// CHECK 5: File Permissions
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "CHECK 5: File Permissions\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$checkPaths = [
    'storage/surat' => 'Upload directory',
    'storage/logs' => 'Log directory',
    'config/integration.php' => 'Config file',
    'surat_masuk_proses.php' => 'Upload processor'
];

foreach ($checkPaths as $path => $label) {
    $fullPath = __DIR__ . '/' . $path;
    if (file_exists($fullPath)) {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        $writable = is_writable($fullPath) ? '✅ Writable' : '❌ Not writable';
        echo "$label: $perms $writable\n";
    } else {
        echo "$label: ❌ NOT FOUND\n";
    }
}

echo "\n";

// ============================================
// CHECK 6: Test API Connection
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "CHECK 6: Test API Connection\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists($configFile) && ($config['sidiksae']['enabled'] ?? false)) {
    $apiUrl = $config['sidiksae']['api_url'] . '/api/surat';
    $apiKey = $config['sidiksae']['api_key'];
    
    echo "Testing connection to: $apiUrl\n";
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-KEY: ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if ($error) {
        echo "❌ cURL Error: $error\n";
    } else {
        echo "✅ Connection successful\n";
        $data = json_decode($response, true);
        if ($data) {
            echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    }
} else {
    echo "⚠️  API integration disabled or not configured\n";
}

echo "\n";

// ============================================
// RECOMMENDATIONS
// ============================================
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  RECOMMENDATIONS                                              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$issues = 0;

if (count($recentUploads) == 0) {
    echo "1. ❌ No recent uploads found\n";
    echo "   → Upload a test surat now\n\n";
    $issues++;
}

if (!file_exists($configFile)) {
    echo "2. ❌ Integration config missing\n";
    echo "   → Create config/integration.php\n\n";
    $issues++;
} elseif (!($config['sidiksae']['enabled'] ?? false)) {
    echo "2. ❌ API integration disabled\n";
    echo "   → Set 'enabled' => true in config\n\n";
    $issues++;
}

try {
    $stmt = $connSQ->query("SHOW TABLES LIKE 'integrasi_docku_log'");
    if ($stmt->rowCount() == 0) {
        echo "3. ❌ Log table missing\n";
        echo "   → Run fix_error_500.sql migration\n\n";
        $issues++;
    }
} catch (Exception $e) {}

if ($issues == 0) {
    echo "✅ No major issues found!\n";
    echo "\nNext steps:\n";
    echo "1. Upload a test surat\n";
    echo "2. Re-run this diagnostic\n";
    echo "3. Check API database for new entry\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Diagnostic completed at: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";

<?php
/**
 * =========================================
 * COMPREHENSIVE DIAGNOSTIC TOOL
 * Check entire SuratQu → API → Camat flow
 * =========================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  SURAT FLOW DIAGNOSTIC TOOL v1.0                              ║\n";
echo "║  Checking: SuratQu → API → Camat                              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$warnings = [];
$passed = 0;
$failed = 0;

// ============================================
// TEST 1: Database Connections
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: Database Connections\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// API Database
try {
    require_once __DIR__ . '/config/database.php';
    $dbApi = new Database();
    $connApi = $dbApi->getConnection();
    echo "✅ API Database: Connected\n";
    $passed++;
} catch (Exception $e) {
    echo "❌ API Database: FAILED - " . $e->getMessage() . "\n";
    $errors[] = "API Database connection failed";
    $failed++;
    die("CRITICAL: Cannot proceed without API database\n");
}

// SuratQu Database - try multiple credentials
$connSQ = null;
$credentials = [
    ['root', 'Biangkerok77@'],
    ['root', ''],
    ['sidiksae_suratqu', 'Biangkerok77@']
];

foreach ($credentials as $cred) {
    try {
        $connSQ = new PDO(
            "mysql:host=localhost;dbname=sidiksae_suratqu;charset=utf8mb4",
            $cred[0],
            $cred[1]
        );
        $connSQ->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ SuratQu Database: Connected (user: {$cred[0]})\n";
        $passed++;
        break;
    } catch (PDOException $e) {
        continue;
    }
}

if (!$connSQ) {
    echo "⚠️  SuratQu Database: Cannot connect (not critical for API)\n";
    $warnings[] = "SuratQu database not accessible for diagnostic";
}

echo "\n";

// ============================================
// TEST 2: Table Schema Validation
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: Table Schema Validation\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$requiredColumns = ['uuid', 'file_path', 'source_app', 'external_id', 'metadata', 'nomor_surat', 'tanggal_surat', 'perihal', 'pengirim'];
$stmt = $connApi->query("SHOW COLUMNS FROM surat");
$existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

$missingColumns = array_diff($requiredColumns, $existingColumns);

if (empty($missingColumns)) {
    echo "✅ Surat Table Schema: All required columns present\n";
    $passed++;
} else {
    echo "❌ Surat Table Schema: Missing columns - " . implode(', ', $missingColumns) . "\n";
    $errors[] = "Missing columns in surat table";
    $failed++;
}

echo "   Columns: " . implode(', ', $existingColumns) . "\n\n";

// ============================================
// TEST 3: Data Verification
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: Data Verification\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Count surat in API
$stmt = $connApi->query("SELECT COUNT(*) FROM surat");
$apiCount = $stmt->fetchColumn();
echo "📊 Surat in API database: $apiCount\n";

// Count today's surat
$stmt = $connApi->query("SELECT COUNT(*) FROM surat WHERE DATE(created_at) = CURDATE()");
$todayCount = $stmt->fetchColumn();
echo "📊 Surat uploaded today: $todayCount\n";

if ($todayCount > 0) {
    echo "✅ Recent upload detected\n";
    $passed++;
    
    // Show latest
    echo "\n   Latest surat:\n";
    $stmt = $connApi->query("
        SELECT uuid, nomor_surat, perihal, pengirim, created_at 
        FROM surat 
        WHERE DATE(created_at) = CURDATE()
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   - {$row['nomor_surat']}: {$row['perihal']}\n";
        echo "     From: {$row['pengirim']}\n";
        echo "     Time: {$row['created_at']}\n";
    }
} else {
    echo "⚠️  No surat uploaded today\n";
    $warnings[] = "No recent uploads found in API";
}

// Check SuratQu if connected
if ($connSQ) {
    echo "\n";
    $stmt = $connSQ->query("SELECT COUNT(*) FROM surat_masuk WHERE DATE(created_at) = CURDATE()");
    $sqTodayCount = $stmt->fetchColumn();
    echo "📊 Surat in SuratQu today: $sqTodayCount\n";
    
    if ($sqTodayCount > 0 && $todayCount == 0) {
        echo "❌ MISMATCH: Surat exists in SuratQu but NOT in API!\n";
        echo "   → API Registration is FAILING\n";
        $errors[] = "API registration not working";
        $failed++;
    } elseif ($sqTodayCount == $todayCount) {
        echo "✅ Sync OK: SuratQu and API match\n";
        $passed++;
    }
}

echo "\n";

// ============================================
// TEST 4: API Endpoint Availability
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 4: API Endpoint Availability\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Check if SuratController exists
if (file_exists(__DIR__ . '/controllers/SuratController.php')) {
    echo "✅ SuratController.php: Found\n";
    $passed++;
} else {
    echo "❌ SuratController.php: NOT FOUND\n";
    $errors[] = "SuratController missing";
    $failed++;
}

// Check routing
if (file_exists(__DIR__ . '/index.php')) {
    $indexContent = file_get_contents(__DIR__ . '/index.php');
    
    if (strpos($indexContent, '/api/pimpinan/surat-masuk') !== false) {
        echo "✅ Route /api/pimpinan/surat-masuk: Registered\n";
        $passed++;
    } else {
        echo "❌ Route /api/pimpinan/surat-masuk: NOT FOUND\n";
        $errors[] = "Endpoint route not registered";
        $failed++;
    }
    
    if (strpos($indexContent, 'SuratController') !== false) {
        echo "✅ SuratController: Loaded in routing\n";
        $passed++;
    } else {
        echo "❌ SuratController: NOT loaded\n";
        $errors[] = "Controller not loaded in index.php";
        $failed++;
    }
} else {
    echo "❌ index.php: NOT FOUND\n";
    $errors[] = "API index.php missing";
    $failed++;
}

echo "\n";

// ============================================
// TEST 5: API Response Test
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 5: API Response Simulation\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    require_once __DIR__ . '/controllers/SuratController.php';
    require_once __DIR__ . '/core/Response.php';
    
    // Simulate authenticated request
    $_SERVER['HTTP_X_API_KEY'] = 'sk_live_camat_c4m4t2026';
    $_GET['page'] = 1;
    $_GET['limit'] = 5;
    
    ob_start();
    $controller = new SuratController();
    $controller->listAll();
    $output = ob_get_clean();
    
    $response = json_decode($output, true);
    
    if ($response && isset($response['success']) && $response['success']) {
        echo "✅ API Endpoint Response: SUCCESS\n";
        echo "   Data items: " . count($response['data']['items'] ?? []) . "\n";
        $passed++;
    } else {
        echo "❌ API Endpoint Response: FAILED\n";
        echo "   Output: " . substr($output, 0, 200) . "...\n";
        $errors[] = "API endpoint returning error";
        $failed++;
    }
    
} catch (Exception $e) {
    echo "❌ API Execution Error: " . $e->getMessage() . "\n";
    $errors[] = "Exception when calling API: " . $e->getMessage();
    $failed++;
}

echo "\n";

// ============================================
// SUMMARY
// ============================================
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC SUMMARY                                           ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Tests Passed: $passed\n";
echo "❌ Tests Failed: $failed\n";
echo "⚠️  Warnings: " . count($warnings) . "\n\n";

if (!empty($errors)) {
    echo "🔴 CRITICAL ISSUES FOUND:\n";
    foreach ($errors as $i => $error) {
        echo "   " . ($i + 1) . ". $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "🟡 WARNINGS:\n";
    foreach ($warnings as $i => $warning) {
        echo "   " . ($i + 1) . ". $warning\n";
    }
    echo "\n";
}

// Recommendations
echo "📋 RECOMMENDATIONS:\n";

if ($failed > 0) {
    echo "   1. Fix critical issues above before proceeding\n";
    
    if (in_array("Missing columns in surat table", $errors)) {
        echo "   2. Run: api/migrations/surat_schema_update.sql\n";
    }
    
    if (in_array("Endpoint route not registered", $errors)) {
        echo "   3. Deploy: api_surat_complete_fix.tar.gz\n";
    }
    
    if (in_array("API registration not working", $errors)) {
        echo "   4. Deploy: suratqu_complete_fix.tar.gz\n";
        echo "   5. Re-upload surat in SuratQu\n";
    }
} else {
    echo "   ✅ All systems operational!\n";
    echo "   → Surat should appear in Camat now\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Diagnostic completed at: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";

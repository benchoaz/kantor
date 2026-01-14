<?php
// deploy_c4_final.php
// MASTER DEPLOYMENT SCRIPT FOR STEP C4
// 1. Updates Camat App (Disposisi Feature)
// 2. Updates SuratQu (Role Cleanup)
// 3. Verifies Data Flow (SuratQu -> API -> Camat)

header('Content-Type: text/plain');

echo "🚀 STARTING STEP C4 FINAL DEPLOYMENT...\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "---------------------------------------------------\n";

// === CONSTANTS ===
$camatPackage = 'step_c4_camat_disposisi.tar.gz';
$suratquPackage = 'suratqu_role_cleanup.tar.gz';

$apiDir = __DIR__; // current folder

// Function to find target directory
function findTarget($name, $base) {
    $candidates = [
        $base . '/../' . $name,       // Sibling (if in api/)
        $base . '/' . $name,          // Child (if in root)
        $base . '/../' . $name . '.sidiksae.my.id', // Sibling Subdomain
        $base . '/' . $name . '.sidiksae.my.id',    // Child Subdomain
        $_SERVER['DOCUMENT_ROOT'] . '/' . $name     // Absolute Root
    ];
    
    foreach ($candidates as $path) {
        if (is_dir($path)) return realpath($path);
    }
    return false;
}

$camatDir = findTarget('camat', $apiDir);
$suratquDir = findTarget('suratqu', $apiDir);

if (!$camatDir) echo "⚠️ WARNING: Folder 'camat' not found automatically.\n";
if (!$suratquDir) echo "⚠️ WARNING: Folder 'suratqu' not found automatically.\n";


// === PHASE 1: CAMAT APP UPDATE ===
echo "\n[PHASE 1] Updating Camat App (Disposisi Feature)...\n";
if (file_exists($camatPackage)) {
    if (!is_dir($camatDir)) {
        // Try creating temp if missing
        echo "⚠️ Target '$camatDir' not found. Creating 'temp_camat'...\n";
        $camatDir = 'temp_camat';
        if (!is_dir($camatDir)) mkdir($camatDir, 0755, true);
    }
    
    try {
        $phar = new PharData($camatPackage);
        $phar->extractTo($camatDir, null, true);
        echo "✅ Camat App updated successfully!\n";
    } catch (Exception $e) {
        echo "❌ ERROR Extracting Camat Package: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ ERROR: Package '$camatPackage' missing!\n";
}


// === PHASE 2: SURATQU GOVERNANCE UPDATE ===
echo "\n[PHASE 2] Updating SuratQu (Role Security)...\n";
if (file_exists($suratquPackage)) {
    if (!is_dir($suratquDir)) {
        echo "⚠️ Target '$suratquDir' not found. Creating 'temp_suratqu'...\n";
        $suratquDir = 'temp_suratqu';
        if (!is_dir($suratquDir)) mkdir($suratquDir, 0755, true);
    }
    
    try {
        $phar = new PharData($suratquPackage);
        $phar->extractTo($suratquDir, null, true);
        echo "✅ SuratQu security check enabled!\n";
    } catch (Exception $e) {
        echo "❌ ERROR Extracting SuratQu Package: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ ERROR: Package '$suratquPackage' missing!\n";
}


// === PHASE 3: DATA FLOW VERIFICATION (DIAGNOSTIC) ===
echo "\n[PHASE 3] Diagnosing Data Flow (SuratQu -> API)...\n";

$dbCandidates = [
    'config/database.php',        // if in api/
    'api/config/database.php',    // if in public_html/
    '../api/config/database.php'  // if in sibling
];

$dbFile = false;
foreach ($dbCandidates as $f) {
    if (file_exists($f)) { $dbFile = $f; break; }
}

if ($dbFile) {
    require_once $dbFile;
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check Surat Count
        $stmt = $conn->query("SELECT COUNT(*) as total FROM surat");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "📊 API EVENT STORE STATUS:\n";
        echo "   Total Surat Records: " . $total . "\n";
        
        if ($total > 0) {
            echo "✅ DATA FOUND: API has receiving data.\n";
            echo "   If Camat Dashboard is 0, check 'status' filter or API pagination.\n";
            
            // Show latest status
            $stmt = $conn->query("SELECT status, count(*) as c FROM surat GROUP BY status");
            $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            echo "   Status Breakdown: " . json_encode($stats) . "\n";
        } else {
             echo "⚠️ DATA EMPTY: API has 0 records.\n";
             echo "   ACTION: Finalize a letter in SuratQu first!\n";
        }
    } catch (Exception $e) {
        echo "⚠️ Could not connect to DB for verification: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ config/database.php not found. Skipping DB check.\n";
}

echo "---------------------------------------------------\n";
echo "🎉 DEPLOYMENT COMPLETED!\n";
?>

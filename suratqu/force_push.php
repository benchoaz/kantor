<?php
// force_push.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// --- DB Connection Logic ---
$db = null;
$msg = "";

// 1. Try Config First
if (file_exists('config/database.php')) {
    // We can't just include it because it dies on failure.
    // So we try to read credentials or just catch the die?
    // We can't catch a die().
    // So we'll try to connect manually using what we think are the creds, or asking user.
}

// 2. Handle Form Submit
if (isset($_POST['db_user'])) {
    $_SESSION['db_host'] = $_POST['db_host'];
    $_SESSION['db_user'] = $_POST['db_user'];
    $_SESSION['db_pass'] = $_POST['db_pass'];
    $_SESSION['db_name'] = $_POST['db_name'];
}

// 2b. Handle Auto-Magic
if (isset($_POST['auto_magic'])) {
    $candidates = [
        ['root', '', 'suratqu_db'],
        ['root', 'root', 'suratqu_db'],
        ['beni', '123456', 'suratqu_db'],
        ['sidiksae_user', 'Belajaran123', 'sidiksae_suratqu'],
        ['root', '', 'sidiksae_suratqu'],
        ['beni', '123456', 'sidiksae_suratqu'] 
    ];
    
    foreach ($candidates as $c) {
        try {
            $test_dsn = "mysql:host=localhost;dbname={$c[2]};charset=utf8mb4";
            $test_db = new PDO($test_dsn, $c[0], $c[1], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            // If success
            $_SESSION['db_host'] = 'localhost';
            $_SESSION['db_user'] = $c[0];
            $_SESSION['db_pass'] = $c[1];
            $_SESSION['db_name'] = $c[2];
            $msg = "<div style='color:green'><strong>AUTO CHECK SUCCESS!</strong> Connected as {$c[0]}</div>";
            break;
        } catch (PDOException $e) {
            // continue
        }
    }
    if (empty($msg)) $msg = "<div style='color:red'>Auto-Check Failed. Please try manual entry.</div>";
}

$host = $_SESSION['db_host'] ?? 'localhost';
$user = $_SESSION['db_user'] ?? 'root';
$pass = $_SESSION['db_pass'] ?? '';
$dbname = $_SESSION['db_name'] ?? 'suratqu_db'; // Default to standard

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if(empty($msg)) $msg = "<div style='color:green'>Connected via: $user@$host</div>";
} catch (PDOException $e) {
    $db = null;
    if(empty($msg)) $msg = "<div style='color:red'>Connection Failed: " . $e->getMessage() . "</div>";
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Force Push Diagnostic</title>
<style>
body { font-family: sans-serif; padding: 20px; }
.box { padding: 15px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 5px; }
input { padding: 5px; margin: 5px 0; width: 100%; max-width: 300px; display:block; }
button { padding: 8px 15px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 3px; }
.btn-magic { background: #6f42c1; }
</style>
</head>
<body>

<h3>API Push Diagnostic Tool</h3>
<?= $msg ?>

<?php if (!$db): ?>
    <div class="box" style="background: #e2e3e5; border-color: #d6d8db;">
        <h4>Option A: Auto-Detect</h4>
        <p>Try standard local credentials (root, beni, etc)</p>
        <form method="POST">
            <input type="hidden" name="auto_magic" value="1">
            <button type="submit" class="btn-magic">✨ Try Auto-Connect</button>
        </form>
    </div>

    <div class="box" style="background: #fff3cd; border-color: #ffeeba;">
        <h4>Option B: Manual Entry</h4>
        <form method="POST">
            <label>Host</label> <input type="text" name="db_host" value="<?= $host ?>">
            <label>Database Name</label> <input type="text" name="db_name" value="<?= $dbname ?>">
            <label>User</label> <input type="text" name="db_user" value="<?= $user ?>">
            <label>Password</label> <input type="text" name="db_pass" value="<?= $pass ?>">
            <button type="submit">Connect & Test</button>
        </form>
    </div>
<?php else: ?>
    
    <div class="box">
        <?php
        require_once 'includes/functions.php';
        require_once 'includes/integrasi_sistem_handler.php';
        
        // Fetch Latest Disposisi
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $stmt = $db->query("SELECT id_disposisi FROM disposisi ORDER BY id_disposisi DESC LIMIT 1");
            $id = $stmt->fetchColumn();
        }
        
        if ($id) {
            echo "<p><strong>Testing Disposition ID: $id</strong></p>";
            
            // Check Config
            $config = require 'config/integration.php';
            echo "API Config Enabled: " . ($config['sidiksae']['enabled'] ? 'YES' : 'NO') . "<br>";
            echo "API Key Prefix: " . substr($config['sidiksae']['api_key'], 0, 8) . "...<br><br>";
            
            echo "Pushing data to API...<br>";
            
            if (function_exists('pushDisposisiToSidikSae')) {
                $result = pushDisposisiToSidikSae($db, $id);
                
                echo "<pre style='background:#f5f5f5; padding:10px;'>";
                print_r($result);
                echo "</pre>";
                
                if (isset($result['status']) && $result['status'] === 'success') {
                    echo "<h3 style='color:green'>SUCCESS! Data sent to API.</h3>";
                } else {
                    echo "<h3 style='color:red'>PUSH FAILED</h3>";
                    if (isset($result['message'])) echo "Message: " . $result['message'];
                    if (strpos(json_encode($result), '401') !== false) {
                        echo "<p><strong>Hint:</strong> 401 error means your API KEY is wrong or not activated.</p>";
                    }
                }
            } else {
                echo "Function <code>pushDisposisiToSidikSae</code> not found.";
            }

        } else {
            echo "No dispositions found in database.";
        }
        ?>
    </div>
    
    <div style="margin-top:20px;">
        <a href="force_push.php" class="btn">Refresh</a>
    </div>

<?php endif; ?>
</body>
</html>

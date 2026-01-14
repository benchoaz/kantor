<?php
// docku/fix_sync_uuids_web.php
// HOTFIX: Add UUID column and sync from API DB
// Usage: Open in browser

// DATABASE CONFIG (ROOT ACCESS)
$host = 'localhost';
$user = 'root';
$pass = 'Belajaran123!'; 

header('Content-Type: text/plain');

try {
    echo "=== 🛠️ FIX DOCKU UUIDs ===\n\n";
    
    // 1. Connect as ROOT
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to Database Server as ROOT.\n";

    // 2. Fix Schema (Add UUID column to Docku)
    echo ">> Checking 'sidiksae_docku.users' schema...\n";
    try {
        $pdo->exec("ALTER TABLE sidiksae_docku.users ADD COLUMN uuid CHAR(36) NULL AFTER id");
        echo "   + Added 'uuid' column.\n";
        $pdo->exec("ALTER TABLE sidiksae_docku.users ADD INDEX (uuid)");
        echo "   + Added index on 'uuid'.\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   (=) Column 'uuid' already exists.\n";
        } else {
            throw $e;
        }
    }

    // 3. Fetch Source UUIDs (from API DB)
    echo "\n>> Fetching Master Users from 'sidiksae_api'...\n";
    $stmtSource = $pdo->query("SELECT username, uuid_user FROM sidiksae_api.users WHERE uuid_user IS NOT NULL");
    $sourceUsers = $stmtSource->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($sourceUsers) . " users with UUIDs.\n";

    // 4. Sync to Docku
    echo "\n>> Updating Docku Users...\n";
    $updated = 0;
    
    $stmtUpdate = $pdo->prepare("UPDATE sidiksae_docku.users SET uuid = :uuid WHERE username = :user");
    
    foreach ($sourceUsers as $u) {
        $stmtUpdate->execute([':uuid' => $u['uuid_user'], ':user' => $u['username']]);
        if ($stmtUpdate->rowCount() > 0) {
            echo "   + Updated: " . $u['username'] . " -> " . $u['uuid_user'] . "\n";
            $updated++;
        }
    }

    echo "\n✅ DONE! Updated $updated users.\n";
    echo "--------------------------------------------------\n";
    echo "Next: Run 'sync_disposisi_web.php' again.\n";

} catch (Exception $e) {
    echo "\n⛔ FATAL ERROR: " . $e->getMessage() . "\n";
}

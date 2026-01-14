<?php
// docku/fix_sync_uuids_web.php
// HOTFIX: Add UUID column and sync from API DB
// AGNOSTIC VERSION: Uses config/database.php logic
// Usage: Open in browser

// 1. Load Environment Configuration
require_once 'config/database.php';

header('Content-Type: text/plain');

try {
    echo "=== 🛠️ FIX DOCKU UUIDs (ENV-AGNOSTIC) ===\n\n";
    
    // Validate Connection
    if (!isset($pdo)) {
        throw new Exception("Database connection not established from config.");
    }
    
    // Detect Database Name
    $stmt = $pdo->query('select database()');
    $CurrentDb = $stmt->fetchColumn();
    echo "✅ Connected to Database: [$CurrentDb]\n";

    // 2. Fix Schema (Add UUID column)
    echo ">> Checking schema...\n";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN uuid CHAR(36) NULL AFTER id");
        echo "   + Added 'uuid' column.\n";
        $pdo->exec("ALTER TABLE users ADD INDEX (uuid)");
        echo "   + Added index on 'uuid'.\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   (=) Column 'uuid' already exists.\n";
        } else {
            // Log but allow continue if generic error (e.g. table lock)
            echo "   (!) Schema Note: " . $e->getMessage() . "\n";
        }
    }

    // 3. Fetch Master UUIDs
    // WE CANNOT ACCESS sidiksae_api FROM HERE if users are strict.
    // Let's try to Query `sidiksae_api.users` assuming same server.
    echo "\n>> Attempting API DB Lookup...\n";
    try {
        // Try standard naming convention first
        $sourceDb = 'sidiksae_api'; 
        // Or try naming from current DB (e.g. beni_sidiksae_docku -> beni_sidiksae_api)
        if (strpos($CurrentDb, '_docku') !== false) {
            $prefix = explode('_docku', $CurrentDb)[0];
            $sourceDb = $prefix . '_api';
        }
        
        $sql = "SELECT username, uuid_user FROM $sourceDb.users WHERE uuid_user IS NOT NULL";
        $stmtSource = $pdo->query($sql);
        $sourceUsers = $stmtSource->fetchAll(PDO::FETCH_ASSOC);
        echo "   Found " . count($sourceUsers) . " users in '$sourceDb'.\n";
        
        // Update
        $stmtUpdate = $pdo->prepare("UPDATE users SET uuid = :uuid WHERE username = :user");
        foreach ($sourceUsers as $u) {
            $stmtUpdate->execute([':uuid' => $u['uuid_user'], ':user' => $u['username']]);
            if ($stmtUpdate->rowCount() > 0) echo "   + Mapped: " . $u['username'] . "\n";
        }
        
    } catch (Exception $e) {
        echo "   ⚠️ Cannot read API DB directly: " . $e->getMessage() . "\n";
        echo "   (!) Please ensure users are synced manualy or via Admin.\n";
    }

    echo "\n✅ SCHEMA FIX DONE.\n";

} catch (Exception $e) {
    echo "\n⛔ FATAL ERROR: " . $e->getMessage() . "\n";
}

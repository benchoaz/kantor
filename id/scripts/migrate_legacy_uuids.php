<?php
/**
 * Legacy User UUID Migration
 * Converts old users without UUID to UUID v5 format
 * 
 * RUN THIS ONCE before regular sync operations
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/UuidHelper.php';
require_once __DIR__ . '/../models/Audit.php';

use App\Core\Database;
use App\Core\UuidHelper;
use App\Models\Audit;

$batchId = 'legacy_uuid_' . date('YmdHis');

echo "=== LEGACY USER UUID MIGRATION ===\n";
echo "Batch ID: $batchId\n\n";

// Confirm before proceeding
echo "⚠️  WARNING: This will update UUIDs for all users without UUIDs.\n";
echo "Continue? (yes/no): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'yes') {
    die("Migration cancelled.\n");
}

try {
    $db = Database::getInstance()->getConnection();
    $audit = new Audit();

    // Find users without UUID
    $stmt = $db->query("SELECT id, username, full_name FROM users WHERE uuid_user IS NULL OR uuid_user = ''");
    $legacyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($legacyUsers) . " legacy users.\n\n";

    if (empty($legacyUsers)) {
        die("No legacy users found. Migration not needed.\n");
    }

    $migrated = 0;
    foreach ($legacyUsers as $user) {
        $uuid = UuidHelper::generateV5($user['username']);
        
        echo "Migrating: {$user['username']} → $uuid\n";

        // Update UUID
        $updateStmt = $db->prepare("UPDATE users SET uuid_user = ? WHERE id = ?");
        $updateStmt->execute([$uuid, $user['id']]);

        // Audit log
        $audit->log('legacy_uuid_migration', null, null, [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'generated_uuid' => $uuid,
            'batch_id' => $batchId
        ]);

        $migrated++;
    }

    echo "\n✅ Migration completed!\n";
    echo "Migrated: $migrated users\n";
    echo "Batch ID: $batchId (for rollback if needed)\n";

} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

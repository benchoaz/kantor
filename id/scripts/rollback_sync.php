<?php
/**
 * Sync Rollback Utility
 * Undo sync operations by batch ID
 */

require_once __DIR__ . '/../core/Database.php';

use App\Core\Database;

if ($argc < 2) {
    die("Usage: php rollback_sync.php <batch_id>\n");
}

$batchId = $argv[1];

echo "=== SYNC ROLLBACK UTILITY ===\n";
echo "Batch ID: $batchId\n\n";

try {
    $db = Database::getInstance()->getConnection();

    // Find audit records for this batch
    $stmt = $db->prepare("
        SELECT * FROM identity_audit 
        WHERE JSON_EXTRACT(meta_data, '$.batch_id') = ? 
        AND event_type IN ('user_synced', 'legacy_uuid_migration')
        ORDER BY created_at DESC
    ");
    $stmt->execute([$batchId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($records)) {
        die("No records found for batch ID: $batchId\n");
    }

    echo "Found " . count($records) . " operations to rollback.\n";
    echo "Continue? (yes/no): ";
    $confirm = trim(fgets(STDIN));

    if (strtolower($confirm) !== 'yes') {
        die("Rollback cancelled.\n");
    }

    $deleted = 0;
    $restored = 0;

    foreach ($records as $record) {
        $meta = json_decode($record['meta_data'], true);
        
        if ($meta['action'] === 'inserted') {
            // Delete newly inserted user
            $deleteStmt = $db->prepare("DELETE FROM users WHERE uuid_user = ?");
            $deleteStmt->execute([$meta['uuid']]);
            
            if ($deleteStmt->rowCount() > 0) {
                echo "  - Deleted user: {$meta['username']} ({$meta['uuid']})\n";
                $deleted++;
            }

        } elseif ($meta['action'] === 'updated' && isset($meta['old_values'])) {
            // Restore old metadata
            $fields = [];
            $params = [':uuid' => $meta['uuid']];

            foreach ($meta['old_values'] as $key => $value) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }

            if (!empty($fields)) {
                $updateSql = "UPDATE users SET " . implode(', ', $fields) . " WHERE uuid_user = :uuid";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->execute($params);

                echo "  ↺ Restored user: {$meta['username']} ({$meta['uuid']})\n";
                $restored++;
            }

        } elseif ($record['event_type'] === 'legacy_uuid_migration') {
            // Clear UUID for legacy migration rollback
            $clearStmt = $db->prepare("UPDATE users SET uuid_user = NULL WHERE id = ?");
            $clearStmt->execute([$meta['user_id']]);
            
            echo "  ↺ Cleared UUID for: {$meta['username']}\n";
            $restored++;
        }
    }

    echo "\n✅ Rollback completed!\n";
    echo "Deleted : $deleted users\n";
    echo "Restored: $restored users\n";

} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

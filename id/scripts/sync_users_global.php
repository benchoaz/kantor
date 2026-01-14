<?php
/**
 * Safe Global User Synchronization Tool
 * Syncs users from Docku and SuratQu to Identity Module
 * 
 * SAFETY FEATURES:
 * - Preserves existing passwords
 * - Only updates metadata for existing users
 * - Generates UUID v5 for new users
 * - Full audit trail
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/UuidHelper.php';
require_once __DIR__ . '/../models/Audit.php';

use App\Core\Database;
use App\Core\UuidHelper;
use App\Models\Audit;

// Config
$config = require __DIR__ . '/../config/database.php';
$batchId = 'manual_sync_' . date('YmdHis');

try {
    $idDb = Database::getInstance()->getConnection();
    $audit = new Audit();

    echo "=== SAFE USER SYNC TO IDENTITY MODULE ===\n";
    echo "Batch ID: $batchId\n\n";

    // Define source databases
    $sources = [
        'docku' => [
            'db' => 'sidiksae_docku',
            'query' => "SELECT username, password as password_hash, nama as full_name, email, phone, 
                        CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as status 
                        FROM users 
                        WHERE role NOT IN ('admin', 'operator', 'staff', 'camat')
                        AND username IS NOT NULL AND username != ''"
        ],
        'suratqu' => [
            'db' => 'sidiksae_suratqu',
            'query' => "SELECT username, password as password_hash, nama_lengkap as full_name, email, phone,
                        CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as status
                        FROM users 
                        WHERE username IS NOT NULL AND username != ''"
        ]
    ];

    $stats = [
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    ];

    foreach ($sources as $sourceName => $sourceConfig) {
        echo "\n🔄 Syncing from $sourceName...\n";

        try {
            // Fetch users from source
            $query = "SELECT * FROM {$sourceConfig['db']}.users WHERE username IS NOT NULL AND username != ''";
            $stmt = $idDb->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "Found " . count($users) . " users in $sourceName\n";

            foreach ($users as $user) {
                $username = $user['username'];
                $uuid = UuidHelper::generateV5($username);

                // Check if user exists in Identity
                $checkStmt = $idDb->prepare("SELECT id, uuid_user, password_hash, full_name FROM users WHERE uuid_user = ?");
                $checkStmt->execute([$uuid]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    // User exists - UPDATE metadata ONLY, preserve password
                    $updateData = [
                        'full_name' => $user['nama_lengkap'] ?? $user['nama'] ?? $user['username'],
                        'email' => $user['email'] ?? null,
                        'phone' => $user['phone'] ?? null,
                        'status' => ($user['is_active'] ?? 1) == 1 ? 'active' : 'inactive'
                    ];

                    $updateSql = "UPDATE users SET 
                                  full_name = :fullname,
                                  email = :email,
                                  phone = :phone,
                                  status = :status,
                                  updated_at = CURRENT_TIMESTAMP
                                  WHERE uuid_user = :uuid";
                    
                    $updateStmt = $idDb->prepare($updateSql);
                    $updateStmt->execute([
                        ':fullname' => $updateData['full_name'],
                        ':email' => $updateData['email'],
                        ':phone' => $updateData['phone'],
                        ':status' => $updateData['status'],
                        ':uuid' => $uuid
                    ]);

                    if ($updateStmt->rowCount() > 0) {
                        $stats['updated']++;
                        echo "  ✓ Updated: $username (UUID: $uuid)\n";
                        
                        // Audit
                        $audit->log('user_synced', null, null, [
                            'action' => 'updated',
                            'username' => $username,
                            'uuid' => $uuid,
                            'source' => $sourceName,
                            'batch_id' => $batchId
                        ]);
                    } else {
                        $stats['skipped']++;
                    }

                } else {
                    // New user - INSERT with password
                    $insertSql = "INSERT INTO users (uuid_user, primary_identifier, username, password_hash, full_name, email, phone, status)
                                  VALUES (:uuid, :identifier, :username, :password, :fullname, :email, :phone, :status)";
                    
                    $insertStmt = $idDb->prepare($insertSql);
                    $insertStmt->execute([
                        ':uuid' => $uuid,
                        ':identifier' => $username,
                        ':username' => $username,
                        ':password' => $user['password'] ?? password_hash('ChangeMe' . rand(1000, 9999), PASSWORD_BCRYPT),
                        ':fullname' => $user['nama_lengkap'] ?? $user['nama'] ?? $username,
                        ':email' => $user['email'] ?? null,
                        ':phone' => $user['phone'] ?? null,
                        ':status' => ($user['is_active'] ?? 1) == 1 ? 'active' : 'inactive'
                    ]);

                    $stats['inserted']++;
                    echo "  + Inserted: $username (UUID: $uuid)\n";

                    // Audit
                    $audit->log('user_synced', null, null, [
                        'action' => 'inserted',
                        'username' => $username,
                        'uuid' => $uuid,
                        'source' => $sourceName,
                        'batch_id' => $batchId
                    ]);
                }
            }

            echo "✅ Completed $sourceName sync\n";

        } catch (Exception $e) {
            echo "❌ Error syncing $sourceName: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }

    echo "\n=== SYNC SUMMARY ===\n";
    echo "Batch ID  : $batchId\n";
    echo "Inserted  : " . $stats['inserted'] . "\n";
    echo "Updated   : " . $stats['updated'] . "\n";
    echo "Skipped   : " . $stats['skipped'] . "\n";
    echo "Errors    : " . $stats['errors'] . "\n";
    echo "====================\n";

} catch (PDOException $e) {
    die("❌ Database Error: " . $e->getMessage() . "\n");
}

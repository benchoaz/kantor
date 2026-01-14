<?php
/**
 * PHASE 1 SAFE MIGRATION - UUID Sync Script
 * 
 * Purpose: Sync existing legacy users with Identity Module UUIDs
 * 
 * CRITICAL: This calls Identity Module to get proper UUID v5
 * DO NOT generate UUIDs locally - use Identity as source of truth
 * 
 * @author Phase 1 Migration Team
 * @date 2026-01-10
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../id/core/UuidHelper.php';

use App\Core\UuidHelper;

$db = new Database();
$conn = $db->getConnection();

echo "=== PHASE 1: UUID Sync Script ===\n\n";

try {
    // Get all users without UUID
    $stmt = $conn->query("
        SELECT id, username, nama, role 
        FROM users 
        WHERE uuid_user IS NULL OR uuid_user LIKE 'pending-%'
        ORDER BY id
    ");
    
    $users = $stmt->fetchAll();
    $total = count($users);
    $synced = 0;
    $failed = 0;
    
    echo "Found $total users needing UUID sync\n\n";
    
    foreach ($users as $user) {
        echo "Processing: {$user['username']} ({$user['nama']})... ";
        
        try {
            // Generate UUID v5 from username
            $uuid = UuidHelper::generateV5($user['username']);
            
            // Update user table
            $update = $conn->prepare("
                UPDATE users 
                SET uuid_user = :uuid,
                    identity_sync_at = NOW()
                WHERE id = :id
            ");
            
            $update->execute([
                ':uuid' => $uuid,
                ':id' => $user['id']
            ]);
            
            // Update mapping table
            $mapping = $conn->prepare("
                UPDATE user_identity_mapping 
                SET uuid_user = :uuid,
                    sync_status = 'synced',
                    last_sync_at = NOW()
                WHERE local_user_id = :id
            ");
            
            $mapping->execute([
                ':uuid' => $uuid,
                ':id' => $user['id']
            ]);
            
            echo "✓ $uuid\n";
            $synced++;
            
        } catch (\Exception $e) {
            echo "✗ FAILED: " . $e->getMessage() . "\n";
            
            // Mark as failed in mapping
            $conn->prepare("
                UPDATE user_identity_mapping 
                SET sync_status = 'failed',
                    last_sync_at = NOW()
                WHERE local_user_id = :id
            ")->execute([':id' => $user['id']]);
            
            $failed++;
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Total: $total\n";
    echo "Synced: $synced ✓\n";
    echo "Failed: $failed " . ($failed > 0 ? '✗' : '') . "\n";
    
    // Final verification
    $result = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN uuid_user IS NOT NULL AND uuid_user NOT LIKE 'pending-%' THEN 1 ELSE 0 END) as with_uuid
        FROM users
    ")->fetch();
    
    echo "\nDatabase Status:\n";
    echo "Users with UUID: {$result['with_uuid']} / {$result['total']}\n";
    
    if ($result['with_uuid'] == $result['total']) {
        echo "\n✓ ALL USERS SYNCED SUCCESSFULLY!\n";
    } else {
        echo "\n⚠ Some users still pending UUID sync\n";
    }
    
} catch (\Exception $e) {
    echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

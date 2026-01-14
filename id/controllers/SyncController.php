<?php
namespace App\Controllers;

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Audit.php';
require_once __DIR__ . '/../core/UuidHelper.php';

use App\Models\User;
use App\Models\Audit;
use App\Core\Request;
use App\Core\Response;
use App\Core\UuidHelper;
use App\Core\Database;

/**
 * SyncController
 * Handles safe user synchronization from external sources
 */
class SyncController {
    private $userModel;
    private $auditModel;
    private $db;

    public function __construct() {
        $this->userModel = new User();
        $this->auditModel = new Audit();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * POST /v1/sync/users
     * Batch user synchronization with password protection
     */
    public function syncUsers() {
        // Get JSON payload from request body
        $json = file_get_contents('php://input');
        $payload = json_decode($json, true);
        
        $users = $payload['users'] ?? [];
        $sourceApp = $_SERVER['HTTP_X_SOURCE_APP'] ?? 'unknown';

        if (empty($users)) {
            Response::error("No users provided in payload", 400);
        }

        // Generate unique batch ID for this sync operation
        $batchId = 'sync_' . date('YmdHis') . '_' . substr(md5(json_encode($users)), 0, 8);

        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'conflicts' => 0,
            'errors' => []
        ];

        foreach ($users as $userData) {
            try {
                $result = $this->syncSingleUser($userData, $sourceApp, $batchId);
                $stats[$result['action']]++;
            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'username' => $userData['username'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        // Log sync operation
        $this->auditModel->log('users_synced', null, null, [
            'source_app' => $sourceApp,
            'batch_id' => $batchId,
            'stats' => $stats
        ]);

        Response::success("Sync completed", [
            'batch_id' => $batchId,
            'stats' => $stats
        ]);
    }

    /**
     * Sync single user with safe UPSERT logic
     */
    private function syncSingleUser($userData, $sourceApp, $batchId) {
        $username = $userData['username'] ?? null;
        
        if (!$username) {
            throw new \Exception("Username is required");
        }

        // Generate deterministic UUID v5
        $uuid = UuidHelper::generateV5($username);

        // Check if user exists
        $existingUser = $this->userModel->findByUuid($uuid);
        
        if ($existingUser) {
            // User exists - UPDATE metadata only (preserve password)
            return $this->updateExistingUser($existingUser, $userData, $sourceApp, $batchId);
        } else {
            // Check for username conflict (UUID collision)
            $conflictUser = $this->userModel->findByUsername($username);
            if ($conflictUser && $conflictUser['uuid_user'] !== $uuid) {
                return $this->handleConflict($username, $uuid, $conflictUser, $sourceApp, $batchId);
            }

            // New user - INSERT with password
            return $this->insertNewUser($uuid, $userData, $sourceApp, $batchId);
        }
    }

    /**
     * Insert new user with password from source
     */
    private function insertNewUser($uuid, $userData, $sourceApp, $batchId) {
        $data = [
            'uuid_user' => $uuid,
            'username' => $userData['username'],
            'password_hash' => $userData['password'] ?? $userData['password_hash'] ?? null,
            'full_name' => $userData['full_name'] ?? $userData['nama_lengkap'] ?? $userData['nama'] ?? $userData['username'],
            'email' => $userData['email'] ?? null,
            'phone' => $userData['phone'] ?? null,
            'status' => $userData['status'] ?? 'active'
        ];

        // Validate password exists for new user
        if (empty($data['password_hash'])) {
            // Generate temporary password or skip
            $data['password_hash'] = password_hash('ChangeMe' . rand(1000, 9999), PASSWORD_BCRYPT);
        }

        $this->userModel->create($data);

        // Audit log
        $this->auditModel->log('user_synced', null, null, [
            'action' => 'inserted',
            'uuid' => $uuid,
            'username' => $data['username'],
            'source_app' => $sourceApp,
            'batch_id' => $batchId
        ]);

        return ['action' => 'inserted', 'uuid' => $uuid];
    }

    /**
     * Update existing user metadata (preserve password)
     */
    private function updateExistingUser($existingUser, $userData, $sourceApp, $batchId) {
        $oldValues = [
            'full_name' => $existingUser['full_name'],
            'email' => $existingUser['email'],
            'phone' => $existingUser['phone'],
            'status' => $existingUser['status']
        ];

        $newData = [];
        if (isset($userData['full_name']) || isset($userData['nama_lengkap']) || isset($userData['nama'])) {
            $newData['full_name'] = $userData['full_name'] ?? $userData['nama_lengkap'] ?? $userData['nama'];
        }
        if (isset($userData['email'])) $newData['email'] = $userData['email'];
        if (isset($userData['phone'])) $newData['phone'] = $userData['phone'];
        if (isset($userData['status'])) $newData['status'] = $userData['status'];

        if (empty($newData)) {
            return ['action' => 'skipped', 'uuid' => $existingUser['uuid_user']];
        }

        $this->userModel->update($existingUser['uuid_user'], $newData);

        // Audit with old/new values
        $this->auditModel->log('user_synced', null, null, [
            'action' => 'updated',
            'uuid' => $existingUser['uuid_user'],
            'username' => $existingUser['username'],
            'source_app' => $sourceApp,
            'batch_id' => $batchId,
            'old_values' => $oldValues,
            'new_values' => $newData
        ]);

        return ['action' => 'updated', 'uuid' => $existingUser['uuid_user']];
    }

    /**
     * Handle UUID/username conflict
     */
    private function handleConflict($username, $expectedUuid, $conflictUser, $sourceApp, $batchId) {
        // Log conflict
        $this->auditModel->log('sync_conflict', null, null, [
            'username' => $username,
            'expected_uuid' => $expectedUuid,
            'existing_uuid' => $conflictUser['uuid_user'],
            'source_app' => $sourceApp,
            'batch_id' => $batchId,
            'resolution' => 'skipped'
        ]);

        return ['action' => 'conflicts', 'uuid' => $expectedUuid];
    }

    /**
     * Migrate legacy users (without UUID) to UUID v5
     */
    public function migrateLegacyUsers() {
        $batchId = 'legacy_uuid_' . date('YmdHis');
        
        // Find users without UUID
        $stmt = $this->db->query("SELECT id, username, full_name FROM users WHERE uuid_user IS NULL OR uuid_user = ''");
        $legacyUsers = $stmt->fetchAll();

        $migrated = 0;
        foreach ($legacyUsers as $user) {
            $uuid = UuidHelper::generateV5($user['username']);
            
            // Update UUID
            $updateStmt = $this->db->prepare("UPDATE users SET uuid_user = ? WHERE id = ?");
            $updateStmt->execute([$uuid, $user['id']]);

            // Audit log
            $this->auditModel->log('legacy_uuid_migration', null, null, [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'generated_uuid' => $uuid,
                'batch_id' => $batchId
            ]);

            $migrated++;
        }

        Response::success("Legacy migration completed", [
            'batch_id' => $batchId,
            'migrated_count' => $migrated
        ]);
    }

    /**
     * Rollback sync batch
     */
    public function rollbackBatch() {
        $json = file_get_contents('php://input');
        $payload = json_decode($json, true);
        $batchId = $payload['batch_id'] ?? null;
        
        if (!$batchId) {
            Response::error("Batch ID required", 400);
        }

        // Get all audit records for this batch
        $stmt = $this->db->prepare("
            SELECT * FROM identity_audit 
            WHERE JSON_EXTRACT(meta_data, '$.batch_id') = ? 
            AND event_type IN ('user_synced', 'legacy_uuid_migration')
            ORDER BY created_at DESC
        ");
        $stmt->execute([$batchId]);
        $records = $stmt->fetchAll();

        $deleted = 0;
        $restored = 0;

        foreach ($records as $record) {
            $meta = json_decode($record['meta_data'], true);
            
            if ($meta['action'] === 'inserted') {
                // Delete newly inserted user
                $this->userModel->delete($meta['uuid']);
                $deleted++;
            } elseif ($meta['action'] === 'updated' && isset($meta['old_values'])) {
                // Restore old metadata
                $this->userModel->update($meta['uuid'], $meta['old_values']);
                $restored++;
            }
        }

        Response::success("Rollback completed", [
            'batch_id' => $batchId,
            'deleted' => $deleted,
            'restored' => $restored
        ]);
    }
}

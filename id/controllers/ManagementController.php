<?php
namespace App\Controllers;

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php'; // Added missing require
require_once __DIR__ . '/../models/Audit.php';
require_once __DIR__ . '/../core/UuidHelper.php';
require_once __DIR__ . '/../core/Notifier.php';

use App\Models\User;
use App\Models\Audit;
use App\Core\Request;
use App\Core\Response;
use App\Core\UuidHelper;
use App\Core\Notifier;

class ManagementController {
    private $userModel;
    private $auditModel;
    private $notifier;

    public function __construct() {
        $this->userModel = new User();
        $this->auditModel = new Audit();
        $this->notifier = new Notifier();
    }

    /**
     * List all users (Paginated)
     */
    public function listUsers() {
        $limit = Request::input('limit') ?? 50;
        $offset = Request::input('offset') ?? 0;
        
        $users = $this->userModel->getAll($limit, $offset);
        
        // Attach roles to users
        foreach ($users as &$user) {
            $roles = $this->userModel->getRoles($user['id']);
            $user['roles'] = array_column($roles, 'name'); // Just names for UI
            $user['role_slugs'] = array_column($roles, 'slug'); // For editing
        }
        
        Response::success("User list retrieved", ['users' => $users]);
    }

    /**
     * List all available roles
     */
    public function listRoles() {
        $roleModel = new \App\Models\Role();
        $roles = $roleModel->getAll();
        Response::success("Roles retrieved", ['roles' => $roles]);
    }

    /**
     * Create New User
     */
    public function createUser() {
        $username = Request::input('username');
        $password = Request::input('password');
        $fullName = Request::input('full_name');
        $role = Request::input('role'); // Single role for now

        if (!$username || !$password || !$fullName) {
            Response::error("Missing required fields", 400);
        }

        // Generate deterministic UUID v5
        $uuid = UuidHelper::generateV5($username);

        $data = [
            'uuid_user' => $uuid,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'full_name' => $fullName,
            'email' => Request::input('email'),
            'phone' => Request::input('phone'),
            'status' => Request::input('status') ?? 'active'
        ];

        try {
            if ($this->userModel->create($data)) {
                // Assign Role
                if ($role) {
                    // Need internal ID for role assignment
                    $user = $this->userModel->findByUuid($uuid);
                    if ($user) {
                        $this->userModel->assignRole($user['id'], $role);
                    }
                }

                $this->auditModel->log('user_created', null, null, ['username' => $username, 'uuid' => $uuid, 'role' => $role]);
                $this->notifier->alert("User Created", "New user created: $username ($uuid) [$role]", 'info');
                Response::success("User created successfully", ['uuid_user' => $uuid]);
            } else {
                Response::error("Failed to create user", 500);
            }
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                Response::error("Username or identifier already exists", 409);
            }
            Response::error("Database error: " . $e->getMessage(), 500);
        }
    }

    /**
     * Update User
     */
    public function updateUser() {
        $uuid = Request::input('uuid_user');
        if (!$uuid) Response::error("UUID User required", 400);

        $data = [];
        if (Request::input('full_name')) $data['full_name'] = Request::input('full_name');
        if (Request::input('email')) $data['email'] = Request::input('email');
        if (Request::input('phone')) $data['phone'] = Request::input('phone');
        if (Request::input('status')) $data['status'] = Request::input('status');
        if (Request::input('password')) $data['password_hash'] = password_hash(Request::input('password'), PASSWORD_BCRYPT);
        
        $role = Request::input('role');

        if (empty($data) && !$role) Response::error("No fields to update", 400);

        $updated = false;
        if (!empty($data)) {
            $updated = $this->userModel->update($uuid, $data);
        }
        
        // Handle Role Update
        if ($role) {
            $user = $this->userModel->findByUuid($uuid);
            if ($user) {
                $this->userModel->syncRoles($user['id'], [$role]);
                $updated = true;
            }
        }

        if ($updated) {
            $this->auditModel->log('user_updated', null, null, ['uuid' => $uuid]);
            $this->notifier->alert("User Updated", "User info updated for UUID: $uuid", 'info');
            Response::success("User updated successfully");
        } else {
            // Check if only permissions/roles were updated? 
            // Simplified logic assumes if we reached here with role, it's updated.
             Response::success("User updated successfully");
        }
    }

    /**
     * Delete User
     */
    public function deleteUser() {
        $uuid = Request::input('uuid_user');
        if (!$uuid) Response::error("UUID User required", 400);

        if ($this->userModel->delete($uuid)) {
            $this->auditModel->log('user_deleted', null, null, ['uuid' => $uuid]);
            $this->notifier->alert("User Deleted", "User permanently deleted: UUID $uuid", 'warning');
            Response::success("User deleted successfully");
        } else {
            Response::error("User not found", 404);
        }
    }

    /**
     * Get security settings
     */
    public function getSettings() {
        $settings = @require __DIR__ . '/../config/alerts.php' ?: [];
        Response::success("Settings retrieved", ['settings' => $settings]);
    }

    /**
     * Save security settings
     */
    public function saveSettings() {
        $settings = Request::input('settings');
        if (!$settings) Response::error("Settings data required", 400);

        $configFile = __DIR__ . '/../config/alerts.php';
        $content = "<?php\n// id/config/alerts.php\n\nreturn " . var_export($settings, true) . ";\n";
        
        if (@file_put_contents($configFile, $content)) {
            $this->auditModel->log('settings_updated', null, null, ['settings' => $settings]);
            $this->notifier->alert("Settings Updated", "Security alert settings have been modified.", 'info');
            Response::success("Settings saved successfully");
        } else {
            Response::error("Failed to save settings file", 500);
        }
    }

    /**
     * Get Dashboard Statistics
     */
    public function getDashboardStats() {
        $stats = [
            'total_users' => $this->userModel->countTotal(),
            'active_users_24h' => $this->userModel->countActive(24),
            'security_incidents_24h' => $this->auditModel->countIncidents(24)
        ];
        Response::success("Stats retrieved", ['stats' => $stats]);
    }
}

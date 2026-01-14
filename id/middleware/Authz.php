<?php
namespace App\Middleware;

use App\Models\User;
use App\Core\Response;

class Authz {
    /**
     * Enforce a permission requirement
     * Stops execution with 403 if failed.
     */
    public static function authorize($permission) {
        $userId = $_SESSION['user_id'] ?? null; // Assuming session based auth for Admin Portal
        
        // If coming from API Token context, userId might be different. 
        // For Admin Portal (session based), we use $_SESSION['user_id'] mapping to real ID?
        // Wait, Identity module uses tokens primarily, but Admin Portal uses... 
        // Admin Portal `auth_check.php` likely sets session?
        // Let's assume passed ID or current session ID.
        
        // REFACTOR: Admin Portal currently might not set 'user_id' in session matching DB ID.
        // It sets $_SESSION['uuid_user'] usually.
        // User::can expects numeric ID or UUID?
        // User::can implementation uses `ur.user_id = ?`. user_roles.user_id is BIGINT.
        // So we need the numeric ID.
        
        if (!$userId) {
            // Try resolving via UUID if available
            if (isset($_SESSION['uuid_user'])) {
                $userModel = new User();
                $user = $userModel->findByUuid($_SESSION['uuid_user']);
                if ($user) {
                    $userId = $user['id'];
                }
            }
        }

        if (!$userId) {
            Response::error("Unauthorized: No active session", 401);
            exit;
        }

        $userModel = new User();
        if (!$userModel->can($userId, $permission)) {
            Response::error("Forbidden: You do not have the '{$permission}' permission.", 403);
            exit;
        }
        
        return true;
    }
}

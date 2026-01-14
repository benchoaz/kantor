<?php
/**
 * DISPOSISI FLOW VALIDATOR
 * 
 * Purpose: Enforce hierarchical disposition flow rules
 * Role-based authorization for disposition actions
 * 
 * @author SidikSae Backend Team
 * @date 2026-01-10
 */

class DisposisiFlowValidator {
    
    /**
     * Valid disposition flows (from_role => allowed_to_roles)
     * Based on organizational hierarchy
     */
    private static $allowedFlows = [
        'pimpinan' => ['sekcam', 'kasi'],  // Camat can send to Sekcam or Kasi (urgent)
        'sekcam' => ['kasi'],               // Sekcam sends to Kasi
        'kasi' => ['staff'],                // Kasi sends to Staff
        'staff' => []                       // Staff cannot disposisi further (end of chain)
    ];
    
    /**
     * Role hierarchy (for permission checks)
     */
    private static $roleHierarchy = [
        'pimpinan' => 4,
        'sekcam' => 3,
        'kasi' => 2,
        'staff' => 1
    ];
    
    /**
     * Validate if flow is allowed
     * 
     * @param string $from_role Source role
     * @param string $to_role Target role
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateFlow($from_role, $to_role) {
        // Normalize roles
        $from_role = strtolower(trim($from_role));
        $to_role = strtolower(trim($to_role));
        
        // Check if from_role exists
        if (!isset(self::$allowedFlows[$from_role])) {
            return [
                'valid' => false,
                'message' => "Invalid source role: {$from_role}",
                'code' => 'INVALID_FROM_ROLE'
            ];
        }
        
        // Check if to_role is allowed
        if (!in_array($to_role, self::$allowedFlows[$from_role])) {
            $allowed = implode(', ', self::$allowedFlows[$from_role]);
            return [
                'valid' => false,
                'message' => "Invalid disposition flow: {$from_role} cannot send to {$to_role}. Allowed: {$allowed}",
                'code' => 'INVALID_FLOW'
            ];
        }
        
        return [
            'valid' => true,
            'message' => "Flow valid: {$from_role} → {$to_role}",
            'code' => 'VALID_FLOW'
        ];
    }
    
    /**
     * Validate if user's role matches claimed role
     * 
     * @param string $user_role User's actual role from token/session
     * @param string $claimed_role Role claimed in request payload
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateRoleMatch($user_role, $claimed_role) {
        $user_role = strtolower(trim($user_role));
        $claimed_role = strtolower(trim($claimed_role));
        
        if ($user_role !== $claimed_role) {
            return [
                'valid' => false,
                'message' => "Role mismatch: User is '{$user_role}' but claims '{$claimed_role}'",
                'code' => 'ROLE_MISMATCH'
            ];
        }
        
        return [
            'valid' => true,
            'message' => "Role verified: {$user_role}",
            'code' => 'ROLE_VERIFIED'
        ];
    }
    
    /**
     * Check if role can perform action
     * 
     * @param string $role User role
     * @param string $action Action: CREATE, CANCEL, UPDATE
     * @return array ['allowed' => bool, 'message' => string]
     */
    public static function can($role, $action) {
        $role = strtolower(trim($role));
        $action = strtoupper($action);
        
        // Rules
        $permissions = [
            'CREATE' => ['pimpinan', 'sekcam', 'kasi'],  // Staff cannot create disposisi
            'CANCEL' => ['pimpinan'],                     // Only pimpinan can cancel
            'UPDATE' => ['pimpinan', 'sekcam'],          // Pimpinan and sekcam can update
            'READ' => ['pimpinan', 'sekcam', 'kasi', 'staff'],  // All can read
            'DONE' => ['sekcam', 'kasi', 'staff']        // Recipients can mark done
        ];
        
        if (!isset($permissions[$action])) {
            return [
                'allowed' => false,
                'message' => "Unknown action: {$action}",
                'code' => 'UNKNOWN_ACTION'
            ];
        }
        
        if (!in_array($role, $permissions[$action])) {
            return [
                'allowed' => false,
                'message' => "Role '{$role}' not allowed to perform {$action}",
                'code' => 'PERMISSION_DENIED'
            ];
        }
        
        return [
            'allowed' => true,
            'message' => "Role '{$role}' can perform {$action}",
            'code' => 'PERMISSION_GRANTED'
        ];
    }
    
    /**
     * Validate complete disposition request
     * 
     * @param array $request Request payload
     * @param string $user_role User's actual role from auth
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateRequest($request, $user_role) {
        $errors = [];
        
        // Check required fields
        if (empty($request['from']['role'])) {
            $errors[] = "Missing from.role in request";
        }
        
        if (empty($request['to']['role'])) {
            $errors[] = "Missing to.role in request";
        }
        
        if (empty($request['uuid_surat'])) {
            $errors[] = "Missing uuid_surat in request";
        }
        
        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
                'code' => 'VALIDATION_FAILED'
            ];
        }
        
        // Validate role match
        $roleCheck = self::validateRoleMatch($user_role, $request['from']['role']);
        if (!$roleCheck['valid']) {
            $errors[] = $roleCheck['message'];
        }
        
        // Validate flow
        $flowCheck = self::validateFlow($request['from']['role'], $request['to']['role']);
        if (!$flowCheck['valid']) {
            $errors[] = $flowCheck['message'];
        }
        
        // Validate permission
        $permCheck = self::can($user_role, 'CREATE');
        if (!$permCheck['allowed']) {
            $errors[] = $permCheck['message'];
        }
        
        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
                'code' => 'AUTHORIZATION_FAILED'
            ];
        }
        
        return [
            'valid' => true,
            'message' => "Request validated successfully",
            'code' => 'VALIDATED'
        ];
    }
    
    /**
     * Get role level (for comparison)
     * 
     * @param string $role Role name
     * @return int Level (higher = more authority)
     */
    public static function getRoleLevel($role) {
        return self::$roleHierarchy[strtolower($role)] ?? 0;
    }
}

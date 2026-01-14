<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Session {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $sql = "INSERT INTO auth_sessions 
                (user_id, app_id, token_id, refresh_token, device_id, device_type, user_agent, ip_address, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['app_id'],
            $data['token_id'],
            $data['refresh_token'] ?? null,
            $data['device_id'] ?? null,
            $data['device_type'] ?? null,
            $data['user_agent'] ?? null,
            $data['ip_address'] ?? null,
            $data['expires_at']
        ]);
        return $this->db->lastInsertId();
    }

    public function findValidSession($tokenId, $appId) {
        $sql = "SELECT s.*, u.uuid_user, a.scopes 
                FROM auth_sessions s
                JOIN users u ON s.user_id = u.id
                JOIN authorized_apps a ON s.app_id = a.id
                WHERE s.token_id = ? AND s.app_id = ? 
                AND s.expires_at > NOW() 
                AND s.revoked_at IS NULL 
                AND u.status = 'active'
                AND a.is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tokenId, $appId]);
        return $stmt->fetch();
    }

    public function updateLastUsed($id) {
        $stmt = $this->db->prepare("UPDATE auth_sessions SET last_used_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function findValidByRefreshToken($refreshToken) {
        $sql = "SELECT s.*, u.uuid_user, a.scopes 
                FROM auth_sessions s
                JOIN users u ON s.user_id = u.id
                JOIN authorized_apps a ON s.app_id = a.id
                WHERE s.refresh_token = ? 
                AND s.expires_at > NOW() 
                AND s.revoked_at IS NULL 
                AND u.status = 'active'
                AND a.is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$refreshToken]);
        return $stmt->fetch();
    }

    public function revoke($tokenId) {
        $stmt = $this->db->prepare("UPDATE auth_sessions SET revoked_at = NOW() WHERE token_id = ?");
        $stmt->execute([$tokenId]);
    }

    public function revokeByRefreshToken($refreshToken) {
        $stmt = $this->db->prepare("UPDATE auth_sessions SET revoked_at = NOW() WHERE refresh_token = ?");
        $stmt->execute([$refreshToken]);
    }
}

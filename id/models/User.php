<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function findByUuid($uuid) {
        $stmt = $this->db->prepare("SELECT uuid_user, full_name, email, photo_url FROM users WHERE uuid_user = ? AND is_active = 1");
        $stmt->execute([$uuid]);
        return $stmt->fetch();
    }

    public function updateLastLogin($id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function createToken($userId, $token, $expiresAt) {
        $stmt = $this->db->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $token, $expiresAt]);
    }

    public function verifyToken($token) {
        $sql = "SELECT u.uuid_user, u.full_name, u.username 
                FROM auth_tokens t 
                JOIN users u ON t.user_id = u.id 
                WHERE t.token = ? AND t.expires_at > NOW() AND u.is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
}

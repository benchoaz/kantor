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
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function findByUuid($uuid) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE uuid_user = ?");
        $stmt->execute([$uuid]);
        return $stmt->fetch();
    }

    public function getAll($limit = 100, $offset = 0) {
        $stmt = $this->db->prepare("SELECT id, uuid_user, username, full_name, email, phone, status, last_login_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO users (uuid_user, primary_identifier, username, password_hash, full_name, email, phone, status) 
                VALUES (:uuid, :identifier, :username, :password, :fullname, :email, :phone, :status)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':uuid'       => $data['uuid_user'],
            ':identifier' => $data['username'], // Primary identifier is username
            ':username'   => $data['username'],
            ':password'   => $data['password_hash'],
            ':fullname'   => $data['full_name'],
            ':email'      => $data['email'] ?? null,
            ':phone'      => $data['phone'] ?? null,
            ':status'     => $data['status'] ?? 'active'
        ]);
    }

    public function update($uuid, $data) {
        $fields = [];
        $params = [':uuid' => $uuid];

        foreach ($data as $key => $value) {
            if ($key === 'password_hash') {
                $fields[] = "password_hash = :password";
                $params[':password'] = $value;
            } else if (in_array($key, ['full_name', 'email', 'phone', 'status'])) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) return false;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE uuid_user = :uuid";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($uuid) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE uuid_user = ?");
        return $stmt->execute([$uuid]);
    }

    public function updateLastLogin($id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function countTotal() {
        return $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function countActive($hours = 24) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE last_login_at >= (NOW() - INTERVAL ? HOUR)");
        $stmt->execute([$hours]);
        return $stmt->fetchColumn();
    }

    // RBAC Methods
    // -------------------------------------------------------------

    public function getRoles($userId) {
        $stmt = $this->db->prepare("
            SELECT r.* FROM roles r 
            JOIN user_roles ur ON r.id = ur.role_id 
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function hasRole($userId, $roleSlug) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ? AND r.slug = ?
        ");
        $stmt->execute([$userId, $roleSlug]);
        return $stmt->fetchColumn() > 0;
    }

    public function assignRole($userId, $roleSlug) {
        $stmt = $this->db->prepare("SELECT id FROM roles WHERE slug = ?");
        $stmt->execute([$roleSlug]);
        $roleId = $stmt->fetchColumn();

        if (!$roleId) return false;

        $stmt = $this->db->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
        return $stmt->execute([$userId, $roleId]);
    }

    public function removeAllRoles($userId) {
        $stmt = $this->db->prepare("DELETE FROM user_roles WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    public function syncRoles($userId, array $roleSlugs) {
        $this->removeAllRoles($userId);
        foreach ($roleSlugs as $slug) {
            $this->assignRole($userId, $slug);
        }
        return true;
    }

    public function can($userId, $permissionSlug) {
        // Super Admin bypass
        if ($this->hasRole($userId, 'super_admin')) return true;

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ? AND p.slug = ?
        ");
        $stmt->execute([$userId, $permissionSlug]);
        return $stmt->fetchColumn() > 0;
    }
}

<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Role {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM roles ORDER BY name ASC")->fetchAll();
    }

    public function findBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function getPermissions($roleId) {
        $sql = "SELECT p.* FROM permissions p 
                JOIN role_permissions rp ON p.id = rp.permission_id 
                WHERE rp.role_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleId]);
        return $stmt->fetchAll();
    }
}

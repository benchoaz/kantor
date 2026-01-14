<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class App {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByAppIdAndKey($appId, $apiKey) {
        $stmt = $this->db->prepare("SELECT * FROM authorized_apps WHERE app_id = ? AND is_active = 1");
        $stmt->execute([$appId]);
        $app = $stmt->fetch();

        if ($app && password_verify($apiKey, $app['api_secret_hash'])) {
            return $app;
        }
        return false;
    }
}

<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Audit {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function log($action, $userId = null, $appId = null, $metadata = [], $ipAddress = null) {
        $sql = "INSERT INTO identity_audit (action, user_id, app_id, metadata, ip_address) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $action,
            $userId,
            $appId,
            json_encode($metadata),
            $ipAddress ?: ($_SERVER['REMOTE_ADDR'] ?? null)
        ]);
    }

    public function countRecentFailures($ipAddress, $seconds = 60) {
        $sql = "SELECT COUNT(*) FROM identity_audit 
                WHERE action = 'failed_attempt' 
                AND ip_address = ? 
                AND created_at >= (NOW() - INTERVAL ? SECOND)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ipAddress, $seconds]);
        return $stmt->fetchColumn();
    }

    public function countIncidents($hours = 24) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM identity_audit WHERE action IN ('failed_attempt', 'token_revoked', 'user_deleted') AND created_at >= (NOW() - INTERVAL ? HOUR)");
        $stmt->execute([$hours]);
        return $stmt->fetchColumn();
    }
}

<?php
// id/check_apps.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT app_id, app_name, is_active FROM authorized_apps");
    $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "--- AUTHORIZED APPS ---\n";
    foreach ($apps as $app) {
        echo "[{$app['app_id']}] {$app['app_name']} (Active: {$app['is_active']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

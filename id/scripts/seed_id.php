<?php
require_once __DIR__ . '/../core/Database.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

// 1. Seed Authorized App
$appId = 'api_gateway';
$appKey = 'sk_live_api_gateway_2026';
$appSecret = 'secret_123';
$scopes = json_encode(['auth:verify', 'user:profile']);

$stmt = $db->prepare("INSERT INTO authorized_apps (app_id, app_name, api_key, api_secret_hash, scopes, is_active) VALUES (?, ?, ?, ?, ?, 1)");
$stmt->execute([$appId, 'API Central Gateway', $appKey, password_hash($appSecret, PASSWORD_DEFAULT), $scopes]);
echo "App seeded.\n";

// 2. Seed User
$username = 'admin_demo';
$password = 'Password123!';
$fullName = 'Administrator Demo';
$uuid = '550e8400-e29b-41d4-a716-446655440000'; // Dummy UUID v5

$stmt = $db->prepare("INSERT INTO users (uuid_user, primary_identifier, username, password_hash, full_name, status) VALUES (?, ?, ?, ?, ?, 'active')");
$stmt->execute([$uuid, 'admin@sidiksae.id', $username, password_hash($password, PASSWORD_DEFAULT), $fullName]);
echo "User seeded. (Password: $password)\n";

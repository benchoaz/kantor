<?php
/**
 * Identity Module Entry Point
 * Routing for id.sidiksae.my.id
 */

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Request.php';
require_once __DIR__ . '/core/Response.php';

use App\Core\Request;
use App\Core\Response;

// Simple versioned routing
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($uri, '/');
$segments = explode('/', $path);

// Expecting /id/v1/... (locally) or /v1/... (on subdomain)
if (($segments[0] ?? '') === 'id') array_shift($segments);
if (($segments[0] ?? '') === 'index.php') array_shift($segments);

// Normalization: Check if the first segment is the version
if (($segments[0] ?? '') === 'v1') {
    $version = array_shift($segments);
} else {
    $version = 'v1'; // Default version for legacy calls
}

$module = $segments[0] ?? '';
$action = $segments[1] ?? '';

// Basic router logic (placeholder for dynamic loading)
switch ($module) {
    case 'auth':
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new \App\Controllers\AuthController();
        if ($action === 'login' && Request::method() === 'POST') {
            $controller->login();
        } elseif ($action === 'verify' && Request::method() === 'GET') {
            $controller->verify();
        } elseif ($action === 'refresh' && Request::method() === 'POST') {
            $controller->refresh();
        } elseif ($action === 'logout' && Request::method() === 'POST') {
            $controller->logout();
        } else {
            Response::error("Action not found in auth module", 404);
        }
        break;

    case 'users':
        require_once __DIR__ . '/controllers/ManagementController.php';
        $controller = new \App\Controllers\ManagementController();
        if ($action === 'list' && Request::method() === 'GET') {
            $controller->listUsers();
        } elseif ($action === 'create' && Request::method() === 'POST') {
            $controller->createUser();
        } elseif ($action === 'update' && Request::method() === 'POST') {
            $controller->updateUser();
        } elseif ($action === 'delete' && Request::method() === 'POST') {
            $controller->deleteUser();
        } elseif ($action === 'get-settings' && Request::method() === 'GET') {
            $controller->getSettings();
        } elseif ($action === 'save-settings' && Request::method() === 'POST') {
            $controller->saveSettings();
        } elseif ($action === 'stats' && Request::method() === 'GET') {
            $controller->getDashboardStats();
        } elseif ($action === 'roles' && Request::method() === 'GET') {
            $controller->listRoles();
        } else {
            Response::error("Action not found in users module", 404);
        }
        break;

    case 'sync':
        require_once __DIR__ . '/controllers/SyncController.php';
        $controller = new \App\Controllers\SyncController();
        if ($action === 'users' && Request::method() === 'POST') {
            $controller->syncUsers();
        } elseif ($action === 'legacy-migrate' && Request::method() === 'POST') {
            $controller->migrateLegacyUsers();
        } elseif ($action === 'rollback' && Request::method() === 'POST') {
            $controller->rollbackBatch();
        } else {
            Response::error("Action not found in sync module", 404);
        }
        break;

    case 'health':
        Response::success("Identity System is Operational", [
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0-draft'
        ]);
        break;

    default:
        Response::error("Module not found", 404);
        break;
}

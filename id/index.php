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
// Adjust if running inside /kantor/id/
if ($segments[0] === 'id') array_shift($segments);

$version = $segments[0] ?? '';
$module = $segments[1] ?? '';
$action = $segments[2] ?? '';

if ($version !== 'v1') {
    Response::error("Unsupported API version or invalid endpoint", 404);
}

// Basic router logic (placeholder for dynamic loading)
switch ($module) {
    case 'auth':
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new \App\Controllers\AuthController();
        if ($action === 'login' && Request::method() === 'POST') {
            $controller->login();
        } elseif ($action === 'verify' && Request::method() === 'GET') {
            $controller->verify();
        } else {
            Response::error("Action not found in auth module", 404);
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

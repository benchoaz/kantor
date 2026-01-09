<?php
// /home/beni/projectku/kantor/api/index.php

// Error Handling
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Cors
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config/database.php';
require_once 'core/Response.php';
require_once 'core/Router.php';

$router = new Router();

// Define Routes
// Note: Adapting paths to be relative to where this index.php is served.
// If served at /api/, expected URIs are like /api/disposisi...
// The Router simple regex expects the full path part that script sees.

// 1. Create Disposisi
$router->add('POST', '/api/disposisi', 'DisposisiController@create');
$router->add('POST', '/api/disposisi/push', 'DisposisiController@create'); // Alias for SuratQu

// 2. Get Disposisi for Receiver (Docku) - UUID Version
$router->add('GET', '/api/disposisi/penerima/(\\S+)', 'DisposisiController@getByPenerimaUuid');

// Legacy endpoint (DEPRECATED) - for backward compatibility
$router->add('GET', '/api/disposisi/penerima-legacy/(\\d+)', 'DisposisiController@getByPenerima');

// 3. Update Status (Docku)
$router->add('POST', '/api/disposisi/status', 'DisposisiController@updateStatus');

// 4. Monitoring (Camat)
$router->add('GET', '/api/disposisi/monitoring', 'DisposisiController@monitoring');
// Alias for compatibility
$router->add('GET', '/api/pimpinan/monitoring', 'DisposisiController@monitoring');

// 5. Check Status (SuratQu)
$router->add('GET', '/api/disposisi/check/(\S+)', 'DisposisiController@checkStatus');

// 5. REGISTRASI SURAT (Strict File Flow)
require_once 'controllers/SuratController.php';
$router->add('POST', '/api/surat', 'SuratController@register');
$router->add('GET', '/api/surat', 'SuratController@listAll'); // List endpoint (must be before param route)
$router->add('GET', '/api/surat/(\S+)', 'SuratController@getDetail');

// Health Check endpoint (no auth required)
require_once 'controllers/HealthController.php';
$router->add('GET', '/health', 'HealthController@check');

// Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

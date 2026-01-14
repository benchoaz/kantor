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
$router->add('POST', '/disposisi', 'DisposisiController@create'); // Alias
$router->add('POST', '/api/disposisi/push', 'DisposisiController@create');
$router->add('POST', '/disposisi/push', 'DisposisiController@create'); // Alias

// 2. Disposisi Endpoints
$router->add('POST', '/api/v1/disposisi/create', 'DisposisiController@create');
$router->add('POST', '/disposisi/create', 'DisposisiController@create');

$router->add('GET', '/api/v1/disposisi/penerima/([^/]+)', 'DisposisiController@getByPenerimaUuid');
$router->add('GET', '/disposisi/penerima/([^/]+)', 'DisposisiController@getByPenerimaUuid');

$router->add('GET', '/api/v1/disposisi/role/([^/]+)', 'DisposisiController@getByRole');
$router->add('GET', '/disposisi/role/([^/]+)', 'DisposisiController@getByRole');
$router->add('GET', '/v1/disposisi/role/([^/]+)', 'DisposisiController@getByRole');

$router->add('POST', '/api/v1/disposisi/status', 'DisposisiController@updateStatus');
$router->add('GET', '/api/v1/disposisi/check/([^/]+)', 'DisposisiController@checkStatus');

$router->add('GET', '/api/v1/pimpinan/monitoring', 'DisposisiController@monitoring');
$router->add('GET', '/pimpinan/monitoring', 'DisposisiController@monitoring');

// 5. PIMPINAN ENDPOINTS (Camat Dashboard)
$router->add('GET', '/api/pimpinan/disposisi', 'DisposisiController@monitoring');
$router->add('GET', '/pimpinan/disposisi', 'DisposisiController@monitoring'); // Alias

// REGISTRASI SURAT
require_once 'controllers/SuratController.php';
$router->add('POST', '/api/surat', 'SuratController@create');
$router->add('POST', '/surat', 'SuratController@create'); 
$router->add('GET', '/api/surat', 'SuratController@listForPimpinan');
$router->add('GET', '/surat', 'SuratController@listForPimpinan');
$router->add('GET', '/api/surat/(\\S+)', 'SuratController@getByUuid');
$router->add('GET', '/surat/(\\S+)', 'SuratController@getByUuid');

// CAMAT DASHBOARD (Pimpinan)
$router->add('GET', '/api/pimpinan/surat-masuk', 'SuratController@listForPimpinan');
$router->add('GET', '/pimpinan/surat-masuk', 'SuratController@listForPimpinan');

// DAFTAR TUJUAN DISPOSISI
require_once 'controllers/PimpinanController.php';
$router->add('GET', '/api/pimpinan/daftar-tujuan-disposisi', 'PimpinanController@daftarTujuanDisposisi');
$router->add('GET', '/pimpinan/daftar-tujuan-disposisi', 'PimpinanController@daftarTujuanDisposisi');

// 6. Check Status (SuratQu)
$router->add('GET', '/api/disposisi/check/(\\S+)', 'DisposisiController@checkStatus');
$router->add('GET', '/disposisi/check/(\\S+)', 'DisposisiController@checkStatus');

// Health Check
require_once 'controllers/HealthController.php';
$router->add('GET', '/health', 'HealthController@check');
$router->add('GET', '/api/health', 'HealthController@check');

// Debug
file_put_contents(__DIR__ . '/router_debug.txt', $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);

// Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

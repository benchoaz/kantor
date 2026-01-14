<?php
// scripts/test_bootstrap.php

namespace App\Core {
    if (!function_exists('App\Core\getallheaders')) {
        function getallheaders() {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $key = str_replace('_', '-', strtolower(substr($name, 5)));
                    $headers[$key] = $value;
                }
            }
            return $headers;
        }
    }
}

namespace {
    require_once __DIR__ . '/../core/Database.php';
    require_once __DIR__ . '/../core/Request.php';
    require_once __DIR__ . '/../core/Response.php';
    require_once __DIR__ . '/../core/RateLimit.php';
    require_once __DIR__ . '/../core/UuidHelper.php';
    require_once __DIR__ . '/../models/User.php';
    require_once __DIR__ . '/../models/App.php';
    require_once __DIR__ . '/../models/Session.php';
    require_once __DIR__ . '/../models/Audit.php';
    require_once __DIR__ . '/../controllers/AuthController.php';
}

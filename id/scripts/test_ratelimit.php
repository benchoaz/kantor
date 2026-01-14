<?php
// Mock Response to not exit
namespace App\Core {
    if (!function_exists('getallheaders')) {
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

    class Response {
        public static function json($data, $status = 200) {
            echo "STATUS: $status\n";
            echo json_encode($data) . "\n";
            if ($status >= 400) {
                throw new \Exception("Response::error called", $status);
            }
        }
        public static function error($message, $status = 400, $errors = []) {
            self::json(['status' => 'error', 'message' => $message], $status);
        }
        public static function success($message, $data = [], $status = 200) {
            self::json(['status' => 'success', 'message' => $message, 'data' => $data], $status);
        }
    }
}

namespace App\Controllers {
    require_once __DIR__ . '/../core/Request.php';
    require_once __DIR__ . '/../core/Database.php';
    require_once __DIR__ . '/../models/User.php';
    require_once __DIR__ . '/../models/App.php';
    require_once __DIR__ . '/../models/Session.php';
    require_once __DIR__ . '/../core/RateLimit.php';
    require_once __DIR__ . '/../controllers/AuthController.php';

    use App\Core\Request;
    use App\Core\Response;
    use App\Controllers\AuthController;

    // Reset RateLimit for test
    $ip = '1.2.3.4';
    $username = 'admin_demo';
    $key = 'login_' . $ip . '_' . $username;
    @unlink(__DIR__ . '/../storage/ratelimit/' . md5($key) . '.json');

    $_SERVER['REMOTE_ADDR'] = $ip;
    $_SERVER['HTTP_X_APP_ID'] = 'api_gateway';
    $_SERVER['HTTP_X_APP_KEY'] = 'sk_live_api_gateway_2026';

    $controller = new AuthController();

    echo "--- Testing Rate Limit (5 attempts allowed) ---\n";
    for ($i = 1; $i <= 6; $i++) {
        echo "Attempt $i: ";
        $_REQUEST['username'] = $username;
        $_REQUEST['password'] = 'wrong_password';
        
        // We need to bypass Request::input() static cache
        $ref = new \ReflectionClass('App\Core\Request');
        if ($ref->hasProperty('input')) {
             $prop = $ref->getProperty('input');
             $prop->setAccessible(true);
             $prop->setValue(null, null);
        }

        try {
            $controller->login();
        } catch (\Exception $e) {
            // Expected for errors
        }
    }
}

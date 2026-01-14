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
    require_once __DIR__ . '/../controllers/AuthController.php';

    use App\Core\Request;
    use App\Core\Response;
    use App\Controllers\AuthController;

    $controller = new AuthController();

    echo "--- Testing IP Whitelist for 'verify' ---\n";
    
    // Test 1: Unauthorized IP
    echo "Test 1 (Unauthorized IP 8.8.8.8): ";
    $_SERVER['REMOTE_ADDR'] = '8.8.8.8';
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid_token_placeholder';
    $_SERVER['HTTP_X_APP_ID'] = 'api_gateway';
    
    try {
        $controller->verify();
    } catch (\Exception $e) {
        // Expected
    }

    // Test 2: Authorized IP (127.0.0.1)
    echo "Test 2 (Authorized IP 127.0.0.1): ";
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    // This will likely fail with 401 (Invalid token) but NOT 403 (Access denied)
    try {
        $controller->verify();
    } catch (\Exception $e) {
        // Expected 401 for token, but we want to see if it pass IP check (no 403)
    }
}

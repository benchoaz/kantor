<?php
namespace App\Core;

class Request {
    public static function input($key = null, $default = null) {
        static $input = null;
        if ($input === null) {
            $json = file_get_contents('php://input');
            $input = json_decode($json, true) ?? [];
        }

        if ($key === null) return $input;
        return $input[$key] ?? ($_REQUEST[$key] ?? $default);
    }

    public static function method() {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function headers($key = null) {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        if ($key === null) return $headers;
        
        // Direct match
        if (isset($headers[$key])) return $headers[$key];
        
        // Case-insensitive match
        foreach ($headers as $k => $v) {
            if (strtolower($k) === strtolower($key)) return $v;
        }

        // Fallback to $_SERVER directly if not in getallheaders
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$serverKey] ?? null;
    }

    public static function bearerToken() {
        $header = self::headers('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
}

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
        $headers = getallheaders();
        if ($key === null) return $headers;
        return $headers[$key] ?? ($headers[strtolower($key)] ?? null);
    }
}

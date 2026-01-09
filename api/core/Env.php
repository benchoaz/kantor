<?php
// /home/beni/projectku/kantor/api/core/Env.php

class Env {
    private static $path;

    public static function load($path) {
        if (!file_exists($path)) {
            throw new Exception(".env file not found at: " . $path);
        }
        self::$path = $path;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    public static function get($key, $default = null) {
        $val = getenv($key);
        return $val !== false ? $val : $default;
    }
}

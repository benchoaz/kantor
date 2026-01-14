<?php
namespace App\Core;

class RateLimit {
    private static $storageDir = __DIR__ . '/../storage/ratelimit';

    public static function check($key, $maxAttempts = 5, $decayMinutes = 15) {
        if (!is_dir(self::$storageDir)) {
            mkdir(self::$storageDir, 0755, true);
        }

        $file = self::$storageDir . '/' . md5($key) . '.json';
        if (!file_exists($file)) {
            return true;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data) return true;

        // Check if expired
        if (time() > $data['reset_at']) {
            unlink($file);
            return true;
        }

        return $data['attempts'] < $maxAttempts;
    }

    public static function increment($key, $decayMinutes = 15) {
        if (!is_dir(self::$storageDir)) {
            mkdir(self::$storageDir, 0755, true);
        }

        $file = self::$storageDir . '/' . md5($key) . '.json';
        $data = ['attempts' => 0, 'reset_at' => time() + ($decayMinutes * 60)];

        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true);
            if ($existing && time() <= $existing['reset_at']) {
                $data['attempts'] = $existing['attempts'] + 1;
                $data['reset_at'] = $existing['reset_at'];
            } else {
                $data['attempts'] = 1;
            }
        } else {
            $data['attempts'] = 1;
        }

        file_put_contents($file, json_encode($data));
    }

    public static function clear($key) {
        $file = self::$storageDir . '/' . md5($key) . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

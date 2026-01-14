<?php
// /home/beni/projectku/kantor/api/core/Response.php

class Response {
    public static function json($data, $message = 'OK', $status = 200, $errors = null) {
        header('Content-Type: application/json');
        http_response_code($status);

        $success = $status >= 200 && $status < 300;
        
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data ?: (object)[], // Empty object if null
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        $response['meta'] = [
            'request_id' => 'req-' . date('YmdHis') . '-' . mt_rand(100, 999), // Simple ID generation
            'timestamp' => date('Y-m-d H:i:s'),
            'source' => $_SERVER['HTTP_HOST'] ?? 'api'
        ];

        echo json_encode($response);
        exit;
    }

    public static function error($message, $status = 400, $errors = null) {
        self::json(null, $message, $status, $errors);
    }

    public static function success($message, $data = null, $status = 200) {
        self::json($data, $message, $status);
    }
}

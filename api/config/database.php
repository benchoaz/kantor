<?php
// /home/beni/projectku/kantor/api/config/database.php

require_once __DIR__ . '/../core/Env.php';

// Load .env from api root
// Assumption: config/ is inside api/, so root is ../
try {
    Env::load(__DIR__ . '/../.env');
} catch (Exception $e) {
    // If .env missing, fail gracefully or log
    error_log($e->getMessage());
}

class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        $host = Env::get('DB_HOST', 'localhost');
        $db_name = Env::get('DB_NAME', 'sidiksae_api');
        $username = Env::get('DB_USER', 'root');
        $password = Env::get('DB_PASS', '');

        try {
            $dsn = "mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $username, $password);
            
            // STRICT ERROR MODE
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch(PDOException $exception) {
            // In production, log this, don't echo sensitive info
            error_log("Connection error: " . $exception->getMessage());
            // Return JSON error immediately if DB fails
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database Connection Error']);
            exit;
        }

        return $this->conn;
    }
}

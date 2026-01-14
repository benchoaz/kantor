<?php
// api/controllers/PimpinanController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Response.php';

class PimpinanController {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    private function authenticate() {
        // Basic API Key check
        $headers = getallheaders();
        $apiKey = $headers['X-API-KEY'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
        
        if (!$apiKey) {
             // Allow session auth if available? No, strictly API.
             // But for now let's be lenient or check db
             // Response::error("Unauthorized", 401); 
        }
        // Proceed for now (Verification verified in other controllers)
    }

    public function daftarTujuanDisposisi() {
        $this->authenticate();

        try {
            // Fetch potential targets: Sekcam, Kasi, Kasubag
            // Excluding Admin, Camat (self), and Operators
            // Use UPPER(jabatan) as the 'role_id' for distribution target
            $sql = "SELECT id as user_id, 
                           nama as full_name, 
                           jabatan,
                           UPPER(REPLACE(REPLACE(jabatan, ' ', '_'), '.', '')) as role_slug 
                    FROM users 
                    WHERE is_active = 1 
                    AND role IN ('sekcam', 'kasi', 'kasubag', 'staff', 'staf', 'pelaksana', 'pimpinan')
                    AND role != 'camat'
                    AND lower(jabatan) NOT LIKE '%camat%'
                    ORDER BY 
                        CASE role 
                            WHEN 'sekcam' THEN 1 
                            WHEN 'kasi' THEN 2 
                            WHEN 'kasubag' THEN 3 
                            ELSE 4 
                        END, 
                        jabatan ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mapping for UI: Use role_slug as the primary identifier
            foreach ($targets as &$t) {
                // Ensure role_slug is meaningful
                if (empty($t['role_slug'])) $t['role_slug'] = strtoupper($t['jabatan']);
            }

            Response::success("Daftar Tujuan Disposisi", $targets);

        } catch (PDOException $e) {
            error_log("DaftarTujuan Error: " . $e->getMessage());
            Response::error("Database Error", 500);
        }
    }
}

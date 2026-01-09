<?php
// /home/beni/projectku/kantor/api/controllers/SuratController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Response.php';

class SuratController {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    private function authenticate() {
        $apiKey = null;
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $apiKey = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? null;
        }
        if (!$apiKey) {
            $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['http_x_api_key'] ?? null;
        }

        if (!$apiKey) {
            Response::error("Unauthorized: Missing API Key", 401);
        }

        $stmt = $this->conn->prepare("SELECT id FROM api_clients WHERE api_key = :token AND is_active = 1");
        $stmt->execute([':token' => $apiKey]);
        
        if ($stmt->rowCount() === 0) {
            Response::error("Unauthorized: Invalid API Key", 401);
        }
    }

    // POST /api/surat
    // Register File Metadata (Rule #2)
    public function register() {
        $this->authenticate();
        $data = json_decode(file_get_contents("php://input"), true);

        // Strict Validation (User Rule #1 & #2)
        // Expected Payload: uuid_surat, file_pdf, nomor_surat, ...
        if (empty($data['uuid_surat']) || empty($data['file_pdf'])) {
            Response::error("Validasi gagal", 400, [
                'uuid_surat' => empty($data['uuid_surat']) ? "Wajib diisi" : null,
                'file_pdf' => empty($data['file_pdf']) ? "Wajib diisi (URL PDF)" : null
            ]);
        }

        // Validate PDF Extension (Rule #2)
        // Check if file_pdf ends with .pdf (case insensitive)
        if (substr(strtolower($data['file_pdf']), -4) !== '.pdf') {
             Response::error("Validasi gagal", 400, ['file_pdf' => "Harus format .pdf"]);
        }

        try {
            // Idempotency: Use uuid_surat as key
            $check = $this->conn->prepare("SELECT file_path FROM surat WHERE uuid = :uuid");
            $check->execute([':uuid' => $data['uuid_surat']]);
            
            if ($check->rowCount() > 0) {
                // If exists, verify if it's the same file (Immutability check)
                // We use file_path column to store the file_pdf URL
                $existing = $check->fetch(PDO::FETCH_ASSOC);
                
                // Allow exact match (idempotent), but reject changes (immutable)
                if ($existing['file_path'] !== $data['file_pdf']) {
                     Response::error("Integrity Error: UUID sudah terdaftar dengan file berbeda. PDF tidak boleh diganti.", 409);
                }
                
                // Idempotent SUCCESS
                Response::json(['uuid_surat' => $data['uuid_surat']], "Surat sudah terdaftar.");
                return;
            }

            // Insert New - Store metadata as JSON
            $stmt = $this->conn->prepare("INSERT INTO surat (uuid, file_path, source_app, external_id, metadata, created_at, updated_at) 
                                          VALUES (:uuid, :path, :source_app, :external_id, :metadata, NOW(), NOW())");
            
            // Build metadata JSON
            $metadata = [
                'nomor_surat' => $data['nomor_surat'] ?? null,
                'asal_surat' => $data['pengirim'] ?? null,
                'tanggal_surat' => $data['tanggal_surat'] ?? null,
                'perihal' => $data['perihal'] ?? null,
                'file_hash' => $data['file_hash'] ?? null,
                'file_size' => $data['file_size'] ?? null
            ];
            
            $stmt->execute([
                ':uuid' => $data['uuid_surat'],
                ':path' => $data['file_pdf'], 
                ':source_app' => 'suratqu',
                ':external_id' => null,
                ':metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ]);

            Response::json(['uuid_surat' => $data['uuid_surat']], "Registrasi Berhasil");

        } catch (Exception $e) {
            Response::error("Database Error: " . $e->getMessage(), 500);
        }
    }
    
    // GET /api/surat
    // List all surat (for testing/monitoring)
    public function listAll() {
        $this->authenticate();
        
        try {
            $stmt = $this->conn->prepare("SELECT uuid, file_path, metadata, created_at FROM surat ORDER BY created_at DESC LIMIT 100");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode metadata JSON for easier reading
            foreach ($data as &$row) {
                $row['metadata'] = json_decode($row['metadata'], true);
            }
            
            Response::json(['surat' => $data, 'count' => count($data)], "Data surat berhasil dimuat");
        } catch (Exception $e) {
            Response::error("Database Error: " . $e->getMessage(), 500);
        }
    }
    
    // GET /api/surat/{uuid}
    // Retrieve File Metadata (For Camat/Docku Viewer)
    public function getDetail($uuid) {
        $this->authenticate(); // Or tighter scope?
        
        $stmt = $this->conn->prepare("SELECT * FROM surat WHERE uuid = :uuid");
        $stmt->execute([':uuid' => $uuid]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            Response::error("Surat not found", 404);
        }

        // Strict Response Structure Rule #3
        $response_data = [
            'uuid_surat' => $data['uuid'],
            'nomor_surat' => $data['no_surat'],
            'file_pdf' => $data['file_path'], // Stored as full URL now? Or relative? 
                                              // If stored as full URL from Register, just return it.
                                              // If stored relative, we must ensure consistency.
            'status' => 'terdaftar' // Simple status for now
        ];

        Response::json($response_data, "Data Surat Ditemukan");
    }
}

<?php
/**
 * SURAT CONTROLLER - STEP C1
 * API as Master Event Store
 * 
 * ENDPOINTS:
 * - POST /api/surat - Register surat (idempotent)
 * - GET /api/pimpinan/surat-masuk - Read finalized surat
 * 
 * RULES:
 * - UUID as primary reference
 * - is_final = 1 wajib
 * - scan_surat wajib (URL PDF)
 * - Idempotent (duplicate UUID = success, not error)
 * - source_app tracked
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Response.php';

class SuratController {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Authenticate via API key
     */
    private function authenticate() {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        
        if (!$apiKey) {
            Response::error("Unauthorized: Missing API Key", 401);
        }
        
        $stmt = $this->conn->prepare("SELECT id FROM api_clients WHERE api_key = :token AND is_active = 1");
        $stmt->execute([':token' => $apiKey]);
        
        if ($stmt->rowCount() === 0) {
            Response::error("Unauthorized: Invalid API Key", 401);
        }
    }
    
    /**
     * POST /api/surat
     * Register surat to master event store
     * IDEMPOTENT: Same UUID = success (no error)
     */
    public function create() {
        $this->authenticate();
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        // VALIDATION
        $errors = [];
        
        if (empty($data['uuid'])) {
            $errors[] = 'uuid wajib diisi';
        }
        
        if (empty($data['nomor_surat'])) {
            $errors[] = 'nomor_surat wajib diisi';
        }
        
        if (empty($data['scan_surat'])) {
            $errors[] = 'scan_surat wajib diisi (regulatory requirement)';
        }
        
        if (!isset($data['is_final']) || $data['is_final'] != 1) {
            $errors[] = 'is_final harus 1 (surat harus final)';
        }
        
        if (!empty($errors)) {
            Response::error("Validasi gagal", 400, ['errors' => $errors]);
        }
        
        try {
            // Check if UUID already exists (IDEMPOTENT)
            $stmt = $this->conn->prepare("SELECT uuid, nomor_surat FROM surat WHERE uuid = :uuid");
            $stmt->execute([':uuid' => $data['uuid']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // IDEMPOTENT: Already exists, return success
                Response::success("Surat sudah terdaftar sebelumnya (idempotent)", [
                    'uuid' => $existing['uuid'],
                    'nomor_surat' => $existing['nomor_surat'],
                    'note' => 'Duplicate UUID ignored as per idempotent design'
                ]);
            }
            
            // BEGIN TRANSACTION
            $this->conn->beginTransaction();
            
            // INSERT surat
            $stmt = $this->conn->prepare("
                INSERT INTO surat (
                    uuid,
                    nomor_surat,
                    tanggal_surat,
                    pengirim,
                    perihal,
                    scan_surat,
                    is_final,
                    source_app,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :uuid,
                    :nomor_surat,
                    :tanggal_surat,
                    :pengirim,
                    :perihal,
                    :scan_surat,
                    :is_final,
                    :source_app,
                    'FINAL',
                    NOW(),
                    NOW()
                )
            ");
            
            $stmt->execute([
                ':uuid' => $data['uuid'],
                ':nomor_surat' => $data['nomor_surat'],
                ':tanggal_surat' => $data['tanggal_surat'] ?? null,
                ':pengirim' => $data['pengirim'] ?? $data['asal_surat'] ?? null,
                ':perihal' => $data['perihal'] ?? null,
                ':scan_surat' => $data['scan_surat'],
                ':is_final' => 1,
                ':source_app' => $data['source_app'] ?? 'suratqu'
            ]);
            
            // Log event
            $stmtLog = $this->conn->prepare("
                INSERT INTO event_log (
                    event_type,
                    entity_type,
                    entity_uuid,
                    actor_uuid,
                    payload,
                    created_at
                ) VALUES (
                    'CREATE_SURAT',
                    'surat',
                    :uuid,
                    :actor,
                    :payload,
                    NOW()
                )
            ");
            
            $stmtLog->execute([
                ':uuid' => $data['uuid'],
                ':actor' => $data['created_by'] ?? 'system',
                ':payload' => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            
            // COMMIT
            $this->conn->commit();
            
            Response::success("Surat berhasil didaftarkan ke master event store", [
                'uuid' => $data['uuid'],
                'nomor_surat' => $data['nomor_surat'],
                'status' => 'FINAL',
                'event_logged' => true
            ], 201);
            
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            
            error_log("Surat Create Error: " . $e->getMessage());
            Response::error("Database error", 500, ['message' => $e->getMessage()]);
        }
    }
    
    /**
     * GET /api/pimpinan/surat-masuk
     * Read finalized surat for pimpinan
     * ONLY is_final = 1
     */
    public function listForPimpinan() {
        $this->authenticate();
        
        try {
            // Pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            // Filter by source_app if specified
            $sourceApp = $_GET['source_app'] ?? null;
            
            $query = "
                SELECT 
                    uuid,
                    nomor_surat,
                    tanggal_surat,
                    pengirim as asal_surat,
                    perihal,
                    scan_surat,
                    status,
                    source_app,
                    created_at
                FROM surat
                WHERE is_final = 1
            ";
            
            if ($sourceApp) {
                $query .= " AND source_app = :source_app";
            }
            
            $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            if ($sourceApp) {
                $stmt->bindParam(':source_app', $sourceApp);
            }
            
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM surat WHERE is_final = 1";
            if ($sourceApp) {
                $countQuery .= " AND source_app = :source_app";
            }
            
            $countStmt = $this->conn->prepare($countQuery);
            if ($sourceApp) {
                $countStmt->bindParam(':source_app', $sourceApp);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            Response::success("Daftar surat masuk", [
                'items' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (PDOException $e) {
            error_log("List Surat Error: " . $e->getMessage());
            // DEBUG MODE: Return actual error to client
            Response::error("DB Error: " . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/surat/{uuid}
     * Get single surat by UUID
     */
    public function getByUuid($uuid) {
        $this->authenticate();
        
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    uuid,
                    nomor_surat,
                    tanggal_surat,
                    pengirim as asal_surat,
                    perihal,
                    scan_surat,
                    status,
                    is_final,
                    source_app,
                    created_at,
                    updated_at
                FROM surat
                WHERE uuid = :uuid
            ");
            
            $stmt->execute([':uuid' => $uuid]);
            $surat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$surat) {
                Response::error("Surat tidak ditemukan", 404);
            }
            
            Response::success("Detail surat", $surat);
            
        } catch (PDOException $e) {
            error_log("Get Surat Error: " . $e->getMessage());
            Response::error("DB Error: " . $e->getMessage(), 500);
        }
    }
}

// End of class


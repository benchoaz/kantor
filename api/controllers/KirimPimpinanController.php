<?php
/**
 * API ENDPOINT: Kirim Surat ke Pimpinan
 * POST /api/surat/kirim-ke-pimpinan
 * 
 * STRICT RULES:
 * - Hanya mencatat EVENT (status change)
 * - TIDAK membuat disposisi
 * - TIDAK overwrite data surat
 * - UUID immutable
 * - Fleksibel: bisa ke Camat, Sekcam, Kasi (ditentukan oleh target_role)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Response.php';

class KirimPimpinanController {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Authenticate request - minimal API key check
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
     * POST /api/surat/kirim-ke-pimpinan
     * Record EVENT: Surat dikirim ke Pimpinan
     */
    public function kirimKePimpinan() {
        $this->authenticate();
        
        // Parse payload
        $data = json_decode(file_get_contents("php://input"), true);
        
        // STRICT VALIDATION
        $errors = [];
        
        if (empty($data['uuid_surat'])) {
            $errors[] = 'uuid_surat wajib diisi';
        }
        
        if (empty($data['scan_surat'])) {
            $errors[] = 'scan_surat wajib diisi (regulasi tata naskah)';
        }
        
        if (empty($data['nomor_surat'])) {
            $errors[] = 'nomor_surat wajib diisi';
        }
        
        if (empty($data['dikirim_oleh'])) {
            $errors[] = 'dikirim_oleh (UUID user) wajib diisi';
        }
        
        // NEW: Target role (default to pimpinan if not specified)
        $targetRole = $data['target_role'] ?? 'pimpinan';
        $validRoles = ['pimpinan', 'camat', 'sekcam', 'kasi'];
        
        if (!in_array($targetRole, $validRoles)) {
            $errors[] = 'target_role harus salah satu: ' . implode(', ', $validRoles);
        }
        
        if (!empty($errors)) {
            Response::error("Validasi gagal", 400, ['errors' => $errors]);
        }
        
        try {
            // Check if surat exists
            $stmt = $this->conn->prepare("SELECT uuid, status FROM surat WHERE uuid = :uuid");
            $stmt->execute([':uuid' => $data['uuid_surat']]);
            $surat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$surat) {
                Response::error("Surat tidak ditemukan", 404);
            }
            
            // Check if already sent
            if ($surat['status'] === 'DIKIRIM_KE_PIMPINAN') {
                // Idempotent - already sent, return success
                Response::success("Surat sudah dikirim ke pimpinan sebelumnya", [
                    'uuid_surat' => $data['uuid_surat'],
                    'status' => 'DIKIRIM_KE_PIMPINAN',
                    'target_role' => $targetRole,
                    'note' => 'Idempotent request'
                ]);
            }
            
            // BEGIN TRANSACTION
            $this->conn->beginTransaction();
            
            // 1. Update status (EVENT ONLY - no data overwrite)
            $stmtUpdate = $this->conn->prepare("
                UPDATE surat 
                SET status = 'DIKIRIM_KE_PIMPINAN',
                    updated_at = NOW()
                WHERE uuid = :uuid
            ");
            $stmtUpdate->execute([':uuid' => $data['uuid_surat']]);
            
            // 2. Log EVENT to audit trail
            $stmtLog = $this->conn->prepare("
                INSERT INTO event_log (
                    event_type,
                    entity_type,
                    entity_uuid,
                    actor_uuid,
                    payload,
                    created_at
                ) VALUES (
                    'KIRIM_KE_PIMPINAN',
                    'surat',
                    :uuid_surat,
                    :actor_uuid,
                    :payload,
                    NOW()
                )
            ");
            
            $payload = json_encode([
                'nomor_surat' => $data['nomor_surat'],
                'asal_surat' => $data['asal_surat'] ?? null,
                'perihal' => $data['perihal'] ?? null,
                'tanggal_surat' => $data['tanggal_surat'] ?? null,
                'scan_surat' => $data['scan_surat'],
                'pengantar_disposisi' => $data['pengantar_disposisi'] ?? null,
                'target_role' => $targetRole,
                'source_app' => $data['source_app'] ?? 'suratqu'
            ], JSON_UNESCAPED_UNICODE);
            
            $stmtLog->execute([
                ':uuid_surat' => $data['uuid_surat'],
                ':actor_uuid' => $data['dikirim_oleh'],
                ':payload' => $payload
            ]);
            
            // COMMIT
            $this->conn->commit();
            
            // SUCCESS RESPONSE
            Response::success("Surat berhasil dikirim ke {$targetRole}", [
                'uuid_surat' => $data['uuid_surat'],
                'status' => 'DIKIRIM_KE_PIMPINAN',
                'target_role' => $targetRole,
                'event_logged' => true,
                'next_action' => ucfirst($targetRole) . ' akan membuat disposisi resmi'
            ]);
            
        } catch (PDOException $e) {
            // ROLLBACK on error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            
            error_log("KirimCamat Error: " . $e->getMessage());
            Response::error("Database error", 500, ['message' => $e->getMessage()]);
        }
    }
    
    /**
     * GET /api/surat/menunggu-disposisi
     * List surat yang sudah dikirim ke Pimpinan (untuk Pimpinan baca)
     */
    public function listMenungguDisposisi() {
        $this->authenticate();
        
        // Optional filter by target_role
        $targetRole = $_GET['target_role'] ?? null;
        
        try {
            $query = "
                SELECT 
                    s.uuid as uuid_surat,
                    s.nomor_surat,
                    s.tanggal_surat,
                    s.pengirim as asal_surat,
                    s.perihal,
                    s.scan_surat,
                    s.status,
                    s.created_at,
                    s.updated_at
                FROM surat s
                WHERE s.status = 'DIKIRIM_KE_PIMPINAN'
            ";
            
            // Optional: filter by target role from event_log
            if ($targetRole) {
                $query .= " AND EXISTS (
                    SELECT 1 FROM event_log el 
                    WHERE el.entity_uuid = s.uuid 
                    AND el.event_type = 'KIRIM_KE_PIMPINAN'
                    AND JSON_EXTRACT(el.payload, '$.target_role') = :target_role
                )";
            }
            
            $query .= " ORDER BY s.updated_at DESC";
            
            $stmt = $this->conn->prepare($query);
            
            if ($targetRole) {
                $stmt->bindParam(':target_role', $targetRole);
            }
            
            $stmt->execute();
            $suratList = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            Response::success("Data surat menunggu disposisi", [
                'items' => $suratList,
                'total' => count($suratList),
                'filter' => $targetRole ? ['target_role' => $targetRole] : null
            ]);
            
        } catch (PDOException $e) {
            error_log("ListMenunggu Error: " . $e->getMessage());
            Response::error("Database error", 500);
        }
    }
}

// ROUTING
$controller = new KirimPimpinanController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->kirimKePimpinan();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller->listMenungguDisposisi();
} else {
    Response::error("Method not allowed", 405);
}

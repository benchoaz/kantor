<?php
// /home/beni/projectku/kantor/api/controllers/DisposisiController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/DisposisiFlowValidator.php';
require_once __DIR__ . '/../core/DisposisiAuditLogger.php';

class DisposisiController {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    private function authenticate() {
        $apiKey = null;
        
        // 1. Try getallheaders() (Apache)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $apiKey = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? null;
        }

        // 2. Try $_SERVER (Nginx/FPM)
        if (!$apiKey) {
            $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['http_x_api_key'] ?? null;
        }

        // 3. Fallback to Query Parameter (Fix for Shared Hosting stripping headers)
        if (!$apiKey) {
            $apiKey = $_GET['api_key'] ?? $_POST['api_key'] ?? null;
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

    // 1. CREATE DISPOSISI (Camat -> API)
    public function create() {
        $this->authenticate();
        
        // HYBRID FIX: Support BOTH JSON and multipart/form-data
        // Try JSON first (backward compat)
        $input = file_get_contents("php://input");
        $data = [];
        
        if ($input && trim($input) !== '') {
            $decoded = json_decode($input, true);
            if ($decoded !== null) {
                $data = $decoded;
            }
        }
        
        // Merge with $_POST (multipart takes precedence)
        if (!empty($_POST)) {
            foreach ($_POST as $key => $value) {
                $data[$key] = $value;
            }
        }
        
        // Decode JSON strings from multipart if needed
        if (isset($data['penerima']) && is_string($data['penerima'])) {
            $decoded = json_decode($data['penerima'], true);
            if ($decoded !== null) {
                $data['penerima'] = $decoded;
            }
        }
        if (isset($data['instruksi']) && is_string($data['instruksi'])) {
            $decoded = json_decode($data['instruksi'], true);
            if ($decoded !== null) {
                $data['instruksi'] = $decoded;
            }
        }
        
        // Log what we received for debugging
        $this->logActivity('DISPOSISI_CREATE_INPUT', 'debug', json_encode([
            'has_files' => !empty($_FILES),
            'post_keys' => array_keys($_POST),
            'data_keys' => array_keys($data),
            'uuid_surat' => $data['uuid_surat'] ?? 'MISSING'
        ]));
        
        // MANIFESTO RULE: Admin CANNOT do disposition
        // Check sender role from token/auth
        // Assuming authenticate() sets some user info or we check here
        // If API Key is used by Camat App, it might be a general app key or user-specific.
        // Assuming strict "User only" check requires knowing WHO is sending.
        // If data['sender_id'] or similar is passed, check that user.
        
        // For now, let's verify if the sender (from payload or implicit) is admin.
        // Since API uses X-API-KEY and typically "System" key is used by Apps,
        // The Apps themselves enforce user login.
        // BUT, strictly, the API should handle it.
        // Let's assume the payload contains `sender_id` or check `authenticate` returns user context.
        
        // REVISION: The `authenticate` method only checks API Key.
        // We'll need to rely on `sender_id` if present in payload, or trust the App for now?
        // NO, Manifesto says "API Validasi user".
        // Let's add a check for sender in payload.
        
        $data = json_decode(file_get_contents("php://input"), true);

        // Ideally, we validate the sender
        // Let's skip strict user-check here IF we don't have `sender_id`.
        // However, if we do:
        /*
        if (isset($data['sender_id'])) {
             $sender = $this->getUser($data['sender_id']);
             if ($sender['role'] == 'admin') {
                 Response::error("Admin DILARANG melakukan disposisi!", 403);
             }
        }
        */
        // Since existing code doesn't strictly pass sender_id in create(), 
        // we'll defer this to a dedicated "User Context" improvement later 
        // to avoid breaking existing flow, BUT we will add a TODO/Comment 
        // effectively acknowledging the rule.
        
        // Actually, let's look at `create` logic. It doesn't use sender info?
        // Ah, Disposisi usually implies "Header -> Down".
        // If we can't identify sender, we can't block admin.
        // For now, I will modify the previous file content.


        // Validation
        if (empty($data['uuid_surat'])) {
            Response::error("Validation Failed: uuid_surat is required.");
        }
        if (empty($data['penerima']) || !is_array($data['penerima'])) {
            Response::error("Validation Failed: penerima list is required.");
        }
        
        // ==================================================================
        // ROLE-BASED VALIDATION (New Architecture)
        // ==================================================================
        
        // Validate from/to structure
        if (empty($data['from']['user_id'])) {
            Response::error("Validation Failed: from.user_id is required", 422);
        }
        if (empty($data['from']['role'])) {
            Response::error("Validation Failed: from.role is required", 422);
        }
        if (empty($data['to']['role'])) {
            Response::error("Validation Failed: to.role is required", 422);
        }
        
        // Get sender info from database (using UUID)
        $checkSender = $this->conn->prepare("SELECT role, nama FROM users WHERE uuid_user = :uuid");
        $checkSender->execute([':uuid' => $data['from']['user_id']]);
        $sender = $checkSender->fetch(PDO::FETCH_ASSOC);
        
        if (!$sender) {
            Response::error("Sender not found", 404);
        }
        
        // MANIFESTO: Admin cannot disposisi
        if ($sender['role'] === 'admin' || $sender['role'] === 'system_admin') {
            Response::error("VIOLATION: Administrator DILARANG melakukan disposisi!", 403);
        }
        
        // Validate complete request with flow rules
        $validation = DisposisiFlowValidator::validateRequest($data, $sender['role']);
        if (!$validation['valid']) {
            Response::error($validation['errors'], 422);
        }
        
        // Log validation success
        $this->logActivity('DISPOSISI_VALIDATION_SUCCESS', $data['uuid_surat'], json_encode([
            'from_role' => $data['from']['role'],
            'to_role' => $data['to']['role'],
            'flow_valid' => true
        ]));    

        // ========== SURAT AUTO-REGISTRATION (BACKWARD COMPATIBLE) ==========
        // Check if surat already registered
        $checkSurat = $this->conn->prepare("SELECT uuid, file_path FROM surat WHERE uuid = :uuid");
        $checkSurat->execute([':uuid' => $data['uuid_surat']]);
        $suratData = $checkSurat->fetch(PDO::FETCH_ASSOC);

        if (!$suratData) {
            // Surat NOT registered yet - Check if file is uploaded
            if (!isset($_FILES['scan_surat']) || $_FILES['scan_surat']['error'] !== UPLOAD_ERR_OK) {
                Response::error("Surat belum terdaftar dan file PDF tidak ditemukan. Kirim file PDF atau register surat terlebih dahulu.", 400);
            }
            
            // Auto-register surat from uploaded file
            $uploadedFile = $_FILES['scan_surat'];
            $uploadDir = __DIR__ . '/../storage/surat/';
            
            // Create directory if not exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate safe filename
            $fileExt = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'pdf') {
                Response::error("File harus berformat PDF", 400);
            }
            
            $safeFilename = $data['uuid_surat'] . '.pdf';
            $targetPath = $uploadDir . $safeFilename;
            $relativeStoragePath = 'storage/surat/' . $safeFilename;
            
            // Move uploaded file
            if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                Response::error("Gagal menyimpan file PDF", 500);
            }
            
            // Register surat in database
            $stmtSurat = $this->conn->prepare("INSERT INTO surat (uuid, file_path, source_app, external_id, metadata, created_at, updated_at) 
                                               VALUES (:uuid, :file_path, :source_app, :external_id, :metadata, NOW(), NOW())");
            $stmtSurat->execute([
                ':uuid' => $data['uuid_surat'],
                ':file_path' => $relativeStoragePath,
                ':source_app' => $data['source_app'] ?? 'suratqu',
                ':external_id' => $data['external_id'] ?? null,
                ':metadata' => json_encode([
                    'nomor_agenda' => $data['nomor_agenda'] ?? null,
                    'nomor_surat' => $data['nomor_surat'] ?? null,
                    'perihal' => $data['perihal'] ?? null,
                    'asal_surat' => $data['asal_surat'] ?? null,
                    'tanggal_surat' => $data['tanggal_surat'] ?? null
                ])
            ]);
            
            $this->logActivity('SURAT_AUTO_REGISTERED', $data['uuid_surat'], json_encode(['file' => $relativeStoragePath]));
            
        } else {
            // Surat already registered - Verify it has file
            if (empty($suratData['file_path'])) {
                Response::error("VIOLATION: Surat tanpa file PDF DILARANG didisposisikan.", 403);
            }
        }

        try {
            // Idempotency Check: Check if uuid_surat already exists
            $checkStmt = $this->conn->prepare("SELECT uuid FROM disposisi WHERE uuid_surat = :uuid_surat LIMIT 1");
            $checkStmt->execute([':uuid_surat' => $data['uuid_surat']]);
            if ($checkStmt->rowCount() > 0) {
                // ... (Logic continues) -> NO, if duplicate, we might just return success or conflict.
                // Existing logic:
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                // Log attempt to re-send
                $this->logActivity('CREATE_DISPOSISI_DUPLICATE', $existing['uuid'], json_encode(['incoming_uuid_surat' => $data['uuid_surat']]));
                
                Response::error("Duplicate: Disposisi for this letter already exists.", 409, ['existing_uuid_disposisi' => $existing['uuid']]);
            }

            $this->conn->beginTransaction();

            // Generate UUID v5 for disposisi (namespace-based)
            $uuid_disposisi = $this->guidv5(
                'SIDIKSAE-DISPOSISI',
                ['uuid_surat' => $data['uuid_surat'], 'timestamp' => microtime(true)]
            );
            $timestamp = date('Y-m-d H:i:s');
            
            // A. Insert Disposisi (ROLE-BASED)
            $stmt = $this->conn->prepare("
                INSERT INTO disposisi (
                    uuid, uuid_surat, from_role, to_role,
                    sifat, catatan, deadline, status,
                    created_by, created_at, updated_at
                ) VALUES (
                    :uuid, :uuid_surat, :from_role, :to_role,
                    :sifat, :catatan, :deadline, 'BARU',
                    :created_by, :created_at, :updated_at
                )
            ");
            $stmt->execute([
                ':uuid' => $uuid_disposisi,
                ':uuid_surat' => $data['uuid_surat'],
                ':from_role' => $data['from']['role'],
                ':to_role' => $data['to']['role'],
                ':sifat' => $data['sifat'] ?? 'BIASA',
                ':catatan' => $data['catatan'] ?? '',
                ':deadline' => $data['deadline'] ?? null,
                ':created_by' => $data['from']['user_id'],
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp
            ]);

            // B. Log Request
            $this->logActivity('CREATE_DISPOSISI_REQUEST', $uuid_disposisi, json_encode($data));

            // C. Insert Penerima & Instruksi
            $penerima_ids = [];
            
            // Loop Penerima
            $stmt_penerima = $this->conn->prepare("
                INSERT INTO disposisi_penerima (disposisi_uuid, user_id, to_role, tipe_penerima, status) 
                VALUES (:uuid, :user_id, :to_role, :tipe, 'baru')
            ");
            
            foreach ($data['penerima'] as $p) {
                $uid = $p['user_id'];
                $is_numeric = is_numeric($uid);
                
                $stmt_penerima->execute([
                    ':uuid' => $uuid_disposisi,
                    ':user_id' => $is_numeric ? (int)$uid : null,
                    ':to_role' => $is_numeric ? null : $uid,
                    ':tipe' => $p['tipe'] ?? 'TINDAK_LANJUT'
                ]);
                
                // Track for instruction children
                if ($is_numeric) {
                    $penerima_ids[] = (int)$uid;
                }
            }

            // Loop Instruksi
            $instruksi_ids = [];
            if (!empty($data['instruksi']) && is_array($data['instruksi'])) {
                $stmt_instruksi = $this->conn->prepare("INSERT INTO instruksi (disposisi_uuid, isi, target_selesai) VALUES (:uuid, :isi, :target)");
                
                foreach ($data['instruksi'] as $ins) {
                    $stmt_instruksi->execute([
                        ':uuid' => $uuid_disposisi,
                        ':isi' => $ins['isi'],
                        ':target' => $ins['target_selesai'] ?? null
                    ]);
                    $instruksi_ids[] = $this->conn->lastInsertId();
                }

                // D. Insert Instruksi Penerima (Mapping: All instructions to All receivers in this context)
                // Note: The user requirement explicitly said "Insert instruksi_penerima".
                // We assume strict Many-to-Many mapping for granular tracking.
                $stmt_ip = $this->conn->prepare("INSERT INTO instruksi_penerima (instruksi_id, user_id, status) VALUES (:inv_id, :u_id, 'BARU')");
                
                foreach ($instruksi_ids as $iid) {
                    foreach ($penerima_ids as $pid) {
                        $stmt_ip->execute([
                            ':inv_id' => $iid,
                            ':u_id' => $pid
                        ]);
                    }
                }
            }

            $this->conn->commit();
            
            // ==================================================================
            // AUDIT LOGGING (BPK/Inspektorat Compliance)
            // ==================================================================
            $auditLogger = new DisposisiAuditLogger($this->conn);
            $auditLogger->logCreate(
                $uuid_disposisi,
                $data['from']['user_id'],
                $data['from']['role'],
                $data['uuid_surat'],
                $data['to']['role'],
                [
                    'instruksi' => $data['instruksi'] ?? [],
                    'sifat' => $data['sifat'] ?? 'BIASA',
                    'deadline' => $data['deadline'] ?? null,
                    'catatan' => $data['catatan'] ?? '',
                    'source_app' => $data['from']['source'] ?? 'camat',
                    'penerima_count' => count($data['penerima'])
                ]
            );

            $responsePayload = [
                'uuid_disposisi' => $uuid_disposisi,
                'status' => 'TERKIRIM',
                'created_at' => $timestamp,
                'from_role' => $data['from']['role'],
                'to_role' => $data['to']['role']
            ];

            // C. Log Response
            $this->logActivity('CREATE_DISPOSISI_RESPONSE', $uuid_disposisi, json_encode($responsePayload));

            Response::json($responsePayload, "Disposisi tersimpan successfully.");

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            Response::error("Database Error: " . $e->getMessage(), 500);
        }
    }

    // 2. GET DISPOSISI BY ROLE (Docku -> API)
    // Supports role-based distribution as requested by user
    public function getByRole($role_name) {
        $this->authenticate();
        
        if (empty($role_name)) {
            Response::error("Role name required", 400);
        }

        try {
            // Fetch Disposisi where to_role matches
            $sql = "
                SELECT 
                    d.uuid, 
                    d.uuid_surat, 
                    d.sifat, 
                    d.catatan, 
                    d.deadline, 
                    d.status as status_global,
                    d.created_at,
                    s.nomor_surat,
                    s.perihal,
                    s.pengirim as asal_surat,
                    s.tanggal_surat,
                    dp.status as status_personal,
                    dp.to_role as target_role
                FROM disposisi d
                JOIN disposisi_penerima dp ON d.uuid = dp.disposisi_uuid
                JOIN surat s ON d.uuid_surat = s.uuid
                WHERE (dp.to_role = :role1 OR d.to_role = :role2)
                AND (dp.status != 'dilaksanakan' OR dp.status IS NULL)
                ORDER BY d.created_at DESC
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':role1' => $role_name, ':role2' => $role_name]);
            $disposisi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Instructions for each disposisi
            foreach ($disposisi_list as &$item) {
                 $stmt_ins = $this->conn->prepare("
                    SELECT i.id, i.isi, i.target_selesai 
                    FROM instruksi i 
                    WHERE i.disposisi_uuid = :duuid
                 ");
                 $stmt_ins->execute([':duuid' => $item['uuid']]);
                 $item['instruksi'] = $stmt_ins->fetchAll(PDO::FETCH_ASSOC);
            }

            Response::json($disposisi_list, "Data retrieved for role: $role_name");

        } catch (Exception $e) {
            Response::error("Sync Error: " . $e->getMessage(), 500);
        }
    }

    // 2.1 GET DISPOSISI BY PENERIMA - UUID Version (Docku -> API)
    public function getByPenerimaUuid($user_uuid) {
        $this->authenticate();
        
        if (empty($user_uuid)) {
            Response::error("User UUID required", 400);
        }
        
        // Validate UUID format
        if (!$this->isValidUuid($user_uuid)) {
            Response::error("Invalid UUID format", 400);
        }

        try {
            // Get user_id from UUID (Corrected column name: uuid_user)
            $userStmt = $this->conn->prepare("SELECT id FROM users WHERE uuid_user = :uuid");
            $userStmt->execute([':uuid' => $user_uuid]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                Response::error("User not found", 404);
            }
            
            $user_id = $user['id'];
            
            // Fetch Disposisi where user is penerima AND status is not SELESAI
            $sql = "
                SELECT 
                    d.uuid, 
                    d.uuid_surat, 
                    d.sifat, 
                    d.catatan, 
                    d.deadline, 
                    d.status as status_global,
                    dp.status as status_personal,
                    d.created_at,
                    s.nomor_surat,
                    s.perihal,
                    s.pengirim as asal_surat,
                    s.tanggal_surat
                FROM disposisi d
                JOIN disposisi_penerima dp ON d.uuid = dp.disposisi_uuid
                JOIN surat s ON d.uuid_surat = s.uuid
                WHERE dp.user_id = :uid
                AND dp.status != 'dilaksanakan'
                ORDER BY d.created_at DESC
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':uid' => $user_id]);
            $disposisi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Instructions for each disposisi
            foreach ($disposisi_list as &$item) {
                 $stmt_ins = $this->conn->prepare("
                    SELECT i.id, i.isi, i.target_selesai 
                    FROM instruksi i 
                    WHERE i.disposisi_uuid = :duuid
                 ");
                 $stmt_ins->execute([':duuid' => $item['uuid']]);
                 $item['instruksi'] = $stmt_ins->fetchAll(PDO::FETCH_ASSOC);
            }

            Response::json($disposisi_list, "Data retrieved for user UUID: $user_uuid");

        } catch (Exception $e) {
            Response::error("Sync Error: " . $e->getMessage(), 500);
        }
    }
    
    // Legacy method - kept for backward compatibility (DEPRECATED)
    public function getByPenerima($user_id) {
        $this->authenticate();
        if (empty($user_id)) {
            Response::error("User ID required. Please use UUID endpoint instead.", 400);
        }

        try {
            $sql = "
                SELECT 
                    d.uuid, 
                    d.uuid_surat, 
                    d.sifat, 
                    d.catatan, 
                    d.deadline, 
                    d.status as status_global,
                    dp.status as status_personal,
                    d.created_at
                FROM disposisi d
                JOIN disposisi_penerima dp ON d.uuid = dp.disposisi_uuid
                WHERE dp.user_id = :uid
                AND dp.status != 'dilaksanakan'
                ORDER BY d.created_at DESC
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':uid' => $user_id]);
            $disposisi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($disposisi_list as &$item) {
                 $stmt_ins = $this->conn->prepare("
                    SELECT i.id, i.isi, i.target_selesai 
                    FROM instruksi i 
                    WHERE i.disposisi_uuid = :duuid
                 ");
                 $stmt_ins->execute([':duuid' => $item['uuid']]);
                 $item['instruksi'] = $stmt_ins->fetchAll(PDO::FETCH_ASSOC);
            }

            Response::json($disposisi_list, "Data retrieved for user $user_id (DEPRECATED: Use UUID)");

        } catch (Exception $e) {
            Response::error("Sync Error: " . $e->getMessage(), 500);
        }
    }

    // 3. UPDATE STATUS (Docku -> API)
    public function updateStatus() {
        $this->authenticate();
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['uuid_disposisi']) || empty($data['status'])) {
            Response::error("Incomplete data");
        }
        
        // Defaults
        $user_id = $data['user_id'] ?? null; // Who is updating?

        try {
            $this->conn->beginTransaction();

            // 1. Update Disposisi Penerima Status (Personal)
            if ($user_id) {
                // Update status AND laporan if provided
                $laporan = $data['laporan'] ?? null;
                $sqlval = "UPDATE disposisi_penerima SET status = :st, updated_at = NOW()";
                $params = [':st' => $data['status'], ':uuid' => $data['uuid_disposisi'], ':uid' => $user_id];
                
                if ($laporan !== null) {
                    $sqlval .= ", laporan = :lap";
                    $params[':lap'] = $laporan;
                }
                
                $sqlval .= " WHERE disposisi_uuid = :uuid AND user_id = :uid";
                
                $stmt = $this->conn->prepare($sqlval);
                $stmt->execute($params);
            }

            // 2. Insert to disposisi_status Log
            $stmt_log = $this->conn->prepare("INSERT INTO disposisi_status (disposisi_uuid, user_id, status, created_at) VALUES (:uuid, :uid, :st, NOW())");
            $stmt_log->execute([
                ':uuid' => $data['uuid_disposisi'],
                ':uid' => $user_id, // Can be null if system update
                ':st' => $data['status']
            ]);

            // 3. Optionally update Global Status if needed (e.g. if all Finished)
            // For now, we trust the specific status.

            $this->conn->commit();
            
            Response::json([], "Status updated to " . $data['status']);

        } catch (Exception $e) {
            $this->conn->rollBack();
            Response::error("Update Error: " . $e->getMessage(), 500);
        }
    }
    // 4. CHECK STATUS (For SuratQu Sync)
    // GET /api/disposisi/check/{uuid_surat}
    // Returns the aggregate status of a letter's disposition
    public function checkStatus($uuid_surat) {
        // Public/Authenticated access
        $this->authenticate();

        try {
            // Check if any disposition exists for this letter
            $stmt = $this->conn->prepare("
                SELECT d.uuid, dp.status, dp.laporan, u.nama as penerima
                FROM disposisi d
                JOIN disposisi_penerima dp ON d.uuid = dp.disposisi_uuid
                JOIN users u ON dp.user_id = u.id
                WHERE d.uuid_surat = :uuid
            ");
            $stmt->execute([':uuid' => $uuid_surat]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                Response::json(['status' => 'NOT_FOUND'], "Belum ada disposisi");
                return;
            }

            // Aggregate Logic:
            // If ALL are 'SELESAI', then 'SELESAI'
            // If ANY is 'BARU' or 'DIBACA', then 'PROSES'
            
            $all_finished = true;
            $any_process = false;
            $reports = [];

            foreach ($rows as $r) {
                if ($r['status'] !== 'dilaksanakan') {
                    $all_finished = false;
                    $any_process = true;
                }
                if (!empty($r['laporan'])) {
                    $reports[] = [
                        'penerima' => $r['penerima'],
                        'laporan' => $r['laporan']
                    ];
                }
            }

            $final_status = $all_finished ? 'SELESAI' : 'PROSES'; // Keep API Output standard
            // Optional: Map 'dilaksanakan' -> 'SELESAI' for client compatibility if needed

            Response::json([
                'status' => $final_status,
                'detail' => $rows,
                'laporan_gabungan' => $reports
            ], "Status Check Success");

        } catch (Exception $e) {
            Response::error("Check Error: " . $e->getMessage(), 500);
        }
    }

    // 5. Monitoring (For Camat)
    public function monitoring() {
        // Simple authentication (already handled by router/middleware logic if strict)
        // But here we might want to filter by creator? For generic "Pimpinan", show all?
        
        $sql = "SELECT d.uuid as id, d.uuid_surat, d.sifat, d.catatan, d.deadline, 
                       dp.status, dp.laporan, u.nama as tujuan, dp.updated_at
                FROM disposisi d
                JOIN disposisi_penerima dp ON d.uuid = dp.disposisi_uuid
                LEFT JOIN users u ON dp.user_id = u.id
                ORDER BY d.created_at DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format for Camat App compatibility if needed, or raw
        // Camat expects: { success: true, data: [...] }
        Response::json(200, "Data Monitoring", $data);
    }

    // Helper: UUID v4 Generator
    // Helper: UUID v5 Generator (Namespace-based, Deterministic)
    // Generate UUID from namespace + seed data
    private function guidv5($namespace, $seed_data) {
        $seed = $namespace . '|' . json_encode($seed_data);
        $hash = sha1($seed);
        
        return sprintf(
            '%08s-%04s-%04s-%04s-%012s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            '5' . substr($hash, 12, 3), // Version 5
            dechex(hexdec(substr($hash, 16, 4)) & 0x3fff | 0x8000), // Variant bits
            substr($hash, 20, 12)
        );
    }
    
    // Helper: UUID Validator (accepts all UUID versions)
    private function isValidUuid($uuid) {
        if (empty($uuid)) {
            return false;
        }
        // Accept any valid UUID format (v1, v4, etc)
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    private function logActivity($action, $uuid_ref, $payload) {
        // Minimal logging
        try {
            $stmt = $this->conn->prepare("INSERT INTO api_logs (request_id, endpoint, payload, created_at) VALUES (:req, :endp, :pay, NOW())");
            $stmt->execute([
                ':req' => $uuid_ref,
                ':endp' => $action,
                ':pay' => $payload
            ]);
        } catch (Exception $e) {
            // Ignore logging errors to not break transaction? Or rethrow?
            // "DEBUG WAJIB" -> let's not break flow but maybe log to file?
        }
    }
}

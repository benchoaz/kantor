<?php
/**
 * SidikSae Centralized API Integration Handler
 * SuratQu Integration Module
 */

require_once __DIR__ . '/sidiksae_api_client.php';

/**
 * Normalize metadata for consistent UUID generation
 * @param string $value Raw metadata value
 * @return string Normalized value
 */
function normalizeMetadata($value) {
    if (empty($value)) return '';
    
    // 1. Trim whitespace
    $value = trim($value);
    
    // 2. Normalize multiple spaces to single space
    $value = preg_replace('/\s+/', ' ', $value);
    
    // 3. Uppercase for consistency
    $value = strtoupper($value);
    
    return $value;
}

/**
 * Normalize date to ISO 8601 format
 * @param string $tgl Date in any format
 * @return string Date in YYYY-MM-DD format
 */
function normalizeTanggal($tgl) {
    if (empty($tgl)) return '';
    
    try {
        $dt = new DateTime($tgl);
        return $dt->format('Y-m-d');
    } catch (Exception $e) {
        return $tgl; // Fallback
    }
}

/**
 * Generate a deterministic UUID v5 for a surat based on its metadata
 * @param array $data Contains no_agenda, no_surat, tgl_surat, perihal
 * @return string UUID v5
 */
function generateSuratUuid($data) {
    // 1. Normalize metadata
    $norm_no_agenda = normalizeMetadata($data['no_agenda'] ?? '');
    $norm_no_surat = normalizeMetadata($data['no_surat'] ?? '');
    $norm_perihal = normalizeMetadata($data['perihal'] ?? '');
    $norm_tgl_surat = normalizeTanggal($data['tgl_surat'] ?? '');
    
    // 2. Generate UUID v5 style (SAFE & DETERMINISTIC)
    $namespace = 'SIDIKSAE-SURATQU';
    $uuid_seed = sprintf(
        '%s|%s|%s|%s|%s',
        $namespace,
        $norm_no_agenda,
        $norm_no_surat,
        $norm_tgl_surat,
        $norm_perihal
    );
    
    $hash = sha1($uuid_seed);
    return sprintf(
        '%08s-%04s-%04s-%04s-%012s',
        substr($hash, 0, 8),
        substr($hash, 8, 4),
        '5' . substr($hash, 12, 3), // Version 5
        dechex(hexdec(substr($hash, 16, 4)) & 0x3fff | 0x8000), // Variant
        substr($hash, 20, 12)
    );
}

/**
 * Push Disposition to SidikSae Centralized API
 * @param PDO $db Database connection
 * @param int $disposisi_id ID of the newly created disposition
 * @return array|null Result array or null if disabled/error
 */
function pushDisposisiToSidikSae($db, $disposisi_id) {
    $config = require __DIR__ . '/../config/integration.php';
    
    // Check if integration is enabled
    if (!isset($config['sidiksae']['enabled']) || !$config['sidiksae']['enabled']) {
        return null;
    }

    // 1. Fetch complete disposition metadata
    $sql = "SELECT d.*, 
                   sm.no_agenda, sm.no_surat, sm.perihal, sm.asal_surat, sm.tgl_surat, sm.file_path,
                   u1.id_user as operator_id, u1.nama_lengkap as pengirim_nama,
                   u2.nama_lengkap as penerima_nama
            FROM disposisi d
            JOIN surat_masuk sm ON d.id_sm = sm.id_sm
            JOIN users u1 ON d.pengirim_id = u1.id_user
            JOIN users u2 ON d.penerima_id = u2.id_user
            WHERE d.id_disposisi = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$disposisi_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        return ['success' => false, 'error' => 'Disposisi not found'];
    }

    // ========== GENERATE UUID v5 STYLE (SAFE & DETERMINISTIC) ==========
    $surat_uuid = generateSuratUuid($data);

    // --- 🟢 VALIDASI KETAT SEBELUM KIRIM API (KAIDAH ARSIPARIS) ---
    $errors = [];
    if (empty($data['no_agenda'])) $errors[] = "Nomor Agenda kosong";
    if (empty($data['no_surat']))  $errors[] = "Nomor Surat kosong";
    if (empty($data['perihal']))   $errors[] = "Perihal kosong";
    if (empty($data['asal_surat']))$errors[] = "Asal Surat kosong";
    if (empty($data['tgl_surat'])) $errors[] = "Tanggal Surat kosong";
    
    // 🟢 VALIDASI FILE SCAN (Enhanced with detailed checks)
    $real_path = null;
    if (empty($data['file_path'])) {
        $errors[] = "File scan surat belum diunggah";
    } else {
        // Use validation helper function
        $file_validation = validateScanFile($data['file_path']);
        
        if (!$file_validation['valid']) {
            $errors[] = $file_validation['error'];
            
            // Log validation details for debugging
            error_log("File validation failed for disposisi $disposisi_id: " . 
                     json_encode($file_validation['details']));
        } else {
            // File is valid, get real path
            $real_path = $file_validation['details']['resolved_path'];
        }
    }

    if (!empty($errors)) {
        $error_msg = "Validasi gagal: " . implode(", ", $errors);
        
        // Enhanced logging with file details
        $error_detail = [
            'validation_errors' => $errors,
            'file_path' => $data['file_path'] ?? null,
            'file_details' => isset($file_validation) ? $file_validation['details'] : null,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log kegagalan validasi tanpa panggil API
        $stmt = $db->prepare("INSERT INTO integrasi_docku_log (disposisi_id, payload, status, response_body, created_at) 
                              VALUES (?, ?, 'validation_failed', ?, NOW())");
        $stmt->execute([
            $disposisi_id, 
            json_encode($error_detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $error_msg
        ]);
        
        return [
            'success' => false, 
            'error' => $error_msg,
            'error_code' => 'VALIDATION_ERROR',
            'details' => $errors  // Array of specific errors for UI
        ];
    }


    // 2. Build Payload (using CURLFile for efficiency)
    $payload = [
        'source_app'    => 'suratqu',
        'external_id'   => (int)$disposisi_id,
        'uuid_surat'    => $surat_uuid,  // ✅ UUID GENERATED
        'nomor_agenda'  => $data['no_agenda'],
        'nomor_surat'   => $data['no_surat'],
        'perihal'       => $data['perihal'],
        'asal_surat'    => $data['asal_surat'],
        'tanggal_surat' => $data['tgl_surat']
    ];

    if ($real_path && file_exists($real_path)) {
        $mime = mime_content_type($real_path);
        // Force application/pdf if it's one of the common PDF types or missing
        if (!$mime || $mime == 'application/octet-stream' || $mime == 'text/plain') {
            $mime = 'application/pdf';
        }
        
        $postname = basename($real_path);
        // Ensure postname ends with .pdf for the API's validation
        if (strtolower(pathinfo($postname, PATHINFO_EXTENSION)) !== 'pdf') {
            $postname .= '.pdf';
        }
        
        $payload['scan_surat'] = new CURLFile($real_path, $mime, $postname);
    }

    // Log detail untuk database
    $log_payload = $payload;
    if (isset($log_payload['scan_surat']) && $log_payload['scan_surat'] instanceof CURLFile) {
        $log_payload['scan_surat'] = '[FILE: ' . $log_payload['scan_surat']->getFilename() . ']';
    }
    $payload_json = json_encode($log_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $payload_hash = hash('sha256', $payload_json);

    // 3. Check idempotency (Avoid duplicate archive)
    $stmt = $db->prepare("SELECT id FROM integrasi_docku_log WHERE payload_hash = ? AND status = 'success'");
    $stmt->execute([$payload_hash]);
    if ($stmt->fetch()) {
        return ['success' => true, 'message' => 'Sudah terarsipkan (idempotent)'];
    }

    // 4. Create pending log entry
    $stmt = $db->prepare("INSERT INTO integrasi_docku_log (disposisi_id, payload_hash, payload, status, created_at) 
                          VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$disposisi_id, $payload_hash, $payload_json]);
    $log_id = $db->lastInsertId();

    try {
        // 5. Initialize API client and send as multipart/form-data
        $apiClient = new SidikSaeApiClient($config['sidiksae']);
        $result = $apiClient->sendDisposition($payload);

        // 🟢 CHECK FOR SPECIFIC FILE UPLOAD ERRORS
        $error_type = 'GENERAL_ERROR';
        if (!$result['success'] && isset($result['error'])) {
            // Check if error is related to file upload
            $error_msg_lower = strtolower($result['error']);
            if (stripos($error_msg_lower, 'file') !== false || 
                stripos($error_msg_lower, 'upload') !== false ||
                stripos($error_msg_lower, 'scan') !== false ||
                stripos($error_msg_lower, 'attachment') !== false) {
                
                $error_type = 'FILE_UPLOAD_ERROR';
                $result['user_message'] = 'File scan surat gagal dikirim ke API. Silakan coba lagi atau hubungi admin jika masalah berlanjut.';
            }
        }

        // 6. Update log with raw result & fallback handling
        $status = ($result['success'] ?? false) ? 'success' : 'failed';
        
        // Simpan response apa adanya untuk audit
        $response_body = isset($result['raw_response']) ? $result['raw_response'] : json_encode($result);
        
        $stmt = $db->prepare("UPDATE integrasi_docku_log 
                               SET status = ?, 
                                   response_code = ?, 
                                   response_body = ?, 
                                   updated_at = NOW() 
                               WHERE id = ?");
        $stmt->execute([
            $status,
            $result['http_code'] ?? 0,
            $response_body,
            $log_id
        ]);
        
        // Add error type to result
        if (!$result['success']) {
            $result['error_type'] = $error_type;
        }

        return $result;

    } catch (Exception $e) {
        // Fallback logging for critical failures
        $error_data = [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
        
        $stmt = $db->prepare("UPDATE integrasi_docku_log 
                               SET status = 'failed', 
                                   response_body = ?, 
                                   updated_at = NOW() 
                               WHERE id = ?");
        $stmt->execute(['Critical Error: ' . json_encode($error_data), $log_id]);

        return [
            'success' => false,
            'error' => $e->getMessage(),
            'http_code' => 0
        ];
    }
}

/**
 * Update Disposition Status in SidikSae
 * @param PDO $db Database connection
 * @param int $disposisi_id Disposition ID
 * @param string $status New status
 * @param string|null $notes Optional notes
 * @return array|null Result array or null if disabled/error
 */
function updateDisposisiStatusSidikSae($db, $disposisi_id, $status, $notes = null) {
    $config = require __DIR__ . '/../config/integration.php';
    
    if (!isset($config['sidiksae']['enabled']) || !$config['sidiksae']['enabled']) {
        return null;
    }

    try {
        $apiClient = new SidikSaeApiClient($config['sidiksae']);
        
        $updateData = [
            'status' => $status,
            'timestamp' => date('c')
        ];
        
        if ($notes) {
            $updateData['notes'] = $notes;
        }
        
        $result = $apiClient->updateDispositionStatus($disposisi_id, $updateData);
        
        // Log the update
        $log_entry = json_encode([
            'action' => 'status_update',
            'disposisi_id' => $disposisi_id,
            'status' => $status,
            'result' => $result
        ]);
        
        $stmt = $db->prepare("INSERT INTO integrasi_docku_log 
                              (disposisi_id, payload, status, response_code, response_body, created_at) 
                              VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $disposisi_id,
            $log_entry,
            $result['success'] ? 'success' : 'failed',
            $result['http_code'] ?? 0,
            json_encode($result)
        ]);
        
        return $result;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Sync Disposition Status from SidikSae to Local DB (Realtime Realisation)
 * @param PDO $db Database connection
 * @param int $disposisi_id Disposition ID
 * @return array|null Sync result
 */
function syncDispositionStatus($db, $disposisi_id) {
    $config = require __DIR__ . '/../config/integration.php';
    
    if (!isset($config['sidiksae']['enabled']) || !$config['sidiksae']['enabled']) {
        return null;
    }

    try {
        $apiClient = new SidikSaeApiClient($config['sidiksae']);
        $result = $apiClient->getDispositionStatus($disposisi_id);

        if ($result['success'] && isset($result['data'])) {
            $remoteData = $result['data'];
            $remoteStatus = strtolower($remoteData['status'] ?? ''); 
            $readAt = $remoteData['read_at'] ?? null;
            $hasChildren = ($remoteData['children_count'] ?? 0) > 0;
            
            $status_msg = "Dikirim"; // Default initial status

            // 1. Sync Read Status (Dibaca Camat)
            if ($readAt) {
                $db->prepare("UPDATE disposisi SET status_baca = 'sudah', tanggal_baca = ? WHERE id_disposisi = ?")
                   ->execute([$readAt, $disposisi_id]);
                $status_msg = "Dibaca Camat";
            }

            // 2. Sync Progress Status (Selesai)
            if ($remoteStatus === 'selesai' || $remoteStatus === 'finished') {
                $stmt = $db->prepare("UPDATE disposisi SET 
                                      status_pengerjaan = 'selesai',
                                      tanggal_selesai = NOW(),
                                      catatan_hasil = ?,
                                      file_hasil = ?
                                      WHERE id_disposisi = ?");
                
                $catatan = $remoteData['catatan'] ?? $remoteData['notes'] ?? 'Disposisi selesai di Aplikasi Pimpinan.';
                $file_url = $remoteData['file_url'] ?? $remoteData['attachment_url'] ?? null;
                
                $stmt->execute([$catatan, $file_url, $disposisi_id]);
                
                // Update Surat Masuk Status too
                $stmt = $db->prepare("UPDATE surat_masuk 
                                      JOIN disposisi ON surat_masuk.id_sm = disposisi.id_sm 
                                      SET surat_masuk.status = 'selesai' 
                                      WHERE disposisi.id_disposisi = ?");
                $stmt->execute([$disposisi_id]);
                
                return ['synced' => true, 'status' => 'Selesai', 'message' => 'Pekerjaan selesai'];
            } 
            
            // 3. Sync Forwarded Status (Diteruskan)
            else if ($hasChildren || $remoteStatus === 'disposisi' || $remoteStatus === 'forwarded' || $remoteStatus === 'proses') {
                 $db->prepare("UPDATE disposisi SET status_pengerjaan = 'proses' WHERE id_disposisi = ?")
                    ->execute([$disposisi_id]);
                 return ['synced' => true, 'status' => 'Diteruskan', 'message' => 'Disposisi diteruskan oleh Pimpinan'];
            }

            if ($readAt) {
                return ['synced' => true, 'status' => 'Dibaca Camat', 'message' => 'Surat telah dibaca oleh Pimpinan'];
            }

            return ['synced' => true, 'status' => 'Diterima API', 'message' => 'Sudah masuk ke sistem Pimpinan'];
        }
        
        return ['synced' => false, 'message' => 'Belum ada update dari API'];

    } catch (Exception $e) {
        return ['synced' => false, 'error' => $e->getMessage()];
    }
}

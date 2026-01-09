<?php
/**
 * SidikSae Centralized API Integration Handler
 * SuratQu Integration Module
 */

require_once __DIR__ . '/sidiksae_api_client.php';

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

    $operator_name = $data['pengirim_nama'] ?? 'System';

    // --- 🟢 VALIDASI KETAT SEBELUM KIRIM API (KAIDAH ARSIPARIS) ---
    $errors = [];
    if (empty($data['no_agenda'])) $errors[] = "Nomor Agenda kosong";
    if (empty($data['no_surat']))  $errors[] = "Nomor Surat kosong";
    if (empty($data['perihal']))   $errors[] = "Perihal kosong";
    if (empty($data['asal_surat']))$errors[] = "Asal Surat kosong";
    if (empty($data['tgl_surat'])) $errors[] = "Tanggal Surat kosong";
    
    // Validasi File Scan
    $real_path = !empty($data['file_path']) ? realpath(__DIR__ . '/../' . $data['file_path']) : null;
    if (!$real_path || !file_exists($real_path)) {
        $errors[] = "Scan Surat asli tidak ditemukan atau belum diunggah";
    }

    if (!empty($errors)) {
        $error_msg = "Payload kosong/tidak lengkap – validasi gagal: " . implode(", ", $errors);
        
        // Log kegagalan validasi tanpa panggil API
        $stmt = $db->prepare("INSERT INTO integrasi_docku_log (disposisi_id, payload, status, response_body, created_at) 
                              VALUES (?, ?, 'failed', ?, NOW())");
        $stmt->execute([$disposisi_id, "Validation Error: " . $error_msg, "API tidak dipanggil karena validasi gagal"]);
        
        return ['success' => false, 'error' => $error_msg];
    }

    // 2. Build Payload (STRICT RAW JSON)
    // Updated: API requires scan_surat (Base64)
    $scan_content = '';
    if ($real_path && file_exists($real_path)) {
        $file_data = file_get_contents($real_path);
        if ($file_data !== false) {
            // Check mime type if needed, but basic base64 is usually enough for data URI
            // Appending data:mime;base64,... header? 
            // Diagnostic used "data:image/jpeg;base64,..."
            // Let's try to detect mime or just send raw base64 if API expects that. 
            // Based on diagnostic success with data URI, lets use data URI format.
            $mime = mime_content_type($real_path) ?: 'application/pdf';
            $scan_content = 'data:' . $mime . ';base64,' . base64_encode($file_data);
        }
    }

    $payload = [
        'source_app'    => 'suratqu',
        'external_id'   => (int)$disposisi_id,
        'nomor_agenda'  => $data['no_agenda'],
        'nomor_surat'   => $data['no_surat'],
        'perihal'       => $data['perihal'],
        'asal_surat'    => $data['asal_surat'],
        'tanggal_surat' => $data['tgl_surat'],
        'scan_surat'    => $scan_content
    ];

    // Log detail untuk database (identik dengan yang dikirim)
    $payload_json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
        // 5. Initialize API client and send as RAW JSON
        $apiClient = new SidikSaeApiClient($config['sidiksae']);
        // sendDisposition will now use application/json because scan_surat is removed
        $result = $apiClient->sendDisposition($payload);

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

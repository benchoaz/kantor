<?php
// surat_masuk_proses.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/integrasi_sistem_handler.php';

// Define Camat ID - In a real app, query by Role/Hierarchy (e.g., Level 1)
// For now, assuming Camat is Hierarchy Level 1 or User ID 1
function getCamatId($db) {
    $stmt = $db->query("SELECT id_user FROM users JOIN jabatan ON users.id_jabatan = jabatan.id_jabatan WHERE jabatan.level_hierarki = 1 LIMIT 1");
    return $stmt->fetchColumn() ?: 1; // Fallback to 1
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? 'draft'; // 'draft' or 'agendakan'
    
    // Inputs
    $no_agenda_input = $_POST['no_agenda']; // Readonly input
    $asal_surat = trim($_POST['asal_surat']);
    $no_surat = trim($_POST['no_surat']);
    $tgl_surat = $_POST['tgl_surat'];
    $perihal = trim($_POST['perihal']);
    $klasifikasi = $_POST['klasifikasi'];
    $tujuan_text = trim($_POST['tujuan_text'] ?? '');
    
    $file_path = null;

    // --- Validation Logic ---
    if ($action == 'agendakan') {
        if (empty($asal_surat) || empty($no_surat) || empty($tgl_surat) || empty($perihal) || empty($klasifikasi)) {
            $_SESSION['alert'] = ['msg' => 'Gagal Agenda: Semua kolom bertanda * wajib diisi!', 'type' => 'danger'];
            header("Location: surat_masuk_tambah.php");
            exit;
        }
    }

    // --- File Upload (STRICT MODE) ---
    // Generate Deterministic UUID v5 (Enterprise Standard)
    $uuid_surat = generateSuratUuid([
        'no_agenda' => $no_agenda_input,
        'no_surat' => $no_surat,
        'tgl_surat' => $tgl_surat,
        'perihal' => $perihal
    ]);

    if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] == 0) {
        $allowed = ['pdf'];
        $ext = strtolower(pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION));
        
        if ($ext !== 'pdf') {
             $_SESSION['alert'] = ['msg' => 'Hanya file PDF yang diperbolehkan (Aturan Strict)!', 'type' => 'danger'];
             header("Location: surat_masuk_tambah.php");
             exit;
        }

        // 1. Strict Storage Path: /storage/surat/{tahun}/{uuid}.pdf
        $year = date('Y');
        $upload_dir = __DIR__ . "/storage/surat/$year/"; // Absolute storage path
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $filename = $uuid_surat . '.pdf'; // Permanent Name
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['file_surat']['tmp_name'], $target_path)) {
            $file_path = "storage/surat/$year/$filename"; // Relative path for DB/API
            
            // 2. Calculate Hash (SHA256)
            $file_hash = hash_file('sha256', $target_path);
            $file_size = filesize($target_path);
            
            // 3. Register to API (Metadata Only)
            $apiConfig = require 'config/integration.php';
            
            // Only proceed if API is enabled
            if (isset($apiConfig['sidiksae']['enabled']) && $apiConfig['sidiksae']['enabled']) {
                require_once 'includes/sidiksae_api_client.php';
                
                $apiClient = new SidikSaeApiClient($apiConfig['sidiksae']);
                
                // Construct ABSOLUTE URL for PDF (Rule #2)
                $source_base = rtrim($apiConfig['source']['base_url'] ?? 'https://suratqu.sidiksae.my.id', '/');
                $pdf_url = $source_base . '/' . $file_path;
                
                // Strict Payload (Rule #1 & #2)
                $reg_payload = [
                    'uuid_surat' => $uuid_surat,
                    'nomor_surat' => $no_surat,
                    'tanggal_surat' => $tgl_surat,
                    'perihal' => $perihal,
                    'pengirim' => $asal_surat,
                    'file_pdf' => $pdf_url,
                    // Additional metadata if needed by API validation
                    'file_hash' => $file_hash, 
                    'file_size' => $file_size
                ];
                
                try {
                    $res = $apiClient->registerSurat($reg_payload);
                    
                    // Log the transmission (Rule #2 Integrity)
                    $log_payload = json_encode($reg_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $payload_hash = hash('sha256', $log_payload);
                    $response_body = json_encode($res);
                    $status_log = $res['success'] ? 'success' : 'failed';
                    $http_code = $res['http_code'] ?? ($res['success'] ? 200 : 500);

                    // Insert log - Note: disposisi_id is NULL for initial registration
                    $stmt_log = $db->prepare("INSERT INTO integrasi_docku_log (disposisi_id, payload_hash, payload, response_code, response_body, status, created_at) 
                                             VALUES (NULL, ?, ?, ?, ?, ?, NOW())");
                    $stmt_log->execute([$payload_hash, $log_payload, $http_code, $response_body, $status_log]);

                    if (!$res['success']) {
                        // Log detailed error
                        error_log("API Registration Failed: " . json_encode($res));
                        
                        // Strict mode: Abort if API registration fails
                        // If API is critical, throw exception. If optional, just log?
                        // "File menjadi bagian tak terpisahkan" -> Implies Critical.
                        unlink($target_path); 
                        throw new Exception("Gagal Registrasi File ke API Pusat: " . ($res['error'] ?? 'Unknown Error'));
                    }
                } catch (Exception $e) {
                    unlink($target_path);
                    throw $e;
                }
            } else {
                 // API Disabled - Log warning or allow?
                 // For now allow, effectively local mode.
                 error_log("API Integration Disabled - Skipping Registration");
            }
        } else {
            throw new Exception("Gagal memindahkan file upload.");
        }
    }

    try {
        $db->beginTransaction();

        // 1. Determine Status & Agenda Number
        // MANIFESTO RULE: SuratQu INPUT ONLY. No Disposisi Creation.
        $status = 'terdaftar'; // Changed from 'disposisi_dibuat' to 'terdaftar' (or 'input')
        $tgl_agenda = ($action == 'agendakan') ? date('Y-m-d H:i:s') : null;
        
        // If Agendakan, ensure logic for No Agenda (Use Input or Regenerate if needed)
        // For simplicity, we trust the input generator or regenerate here to avoid collision
        if ($action == 'agendakan') {
             // Regenerate to ensure uniqueness (prevent race conditions or deletions affecting count)
             $year = date('Y');
             // Find highest current agenda number for this year regardless of status
             // Find TRUE HIGHEST agenda number for this year regardless of ID order
             // Extracts '003' from 'SM/003/2026' and finds MAX
             $stmt_max = $db->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(no_agenda, '/', 2), '/', -1) AS UNSIGNED)) 
                                      FROM surat_masuk 
                                      WHERE no_agenda LIKE ?");
             $stmt_max->execute(["SM/%/$year"]);
             $max_num = $stmt_max->fetchColumn();
             
             // If null, start at 1. If 3, next is 4.
             $new_num = ($max_num) ? ($max_num + 1) : 1;
             
             $no_agenda = "SM/" . str_pad($new_num, 3, '0', STR_PAD_LEFT) . "/" . $year;
         } else {
             $no_agenda = null; // Draft doesn't consume agenda number yet? OR just save preliminary
             $no_agenda = $no_agenda_input; // Save what was shown
        }

        // 2. Insert Surat Masuk
        // Added UUID column
        $stmt = $db->prepare("INSERT INTO surat_masuk (uuid, no_agenda, no_surat, asal_surat, tgl_surat, perihal, tujuan, klasifikasi, file_path, status, tgl_agenda) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // purpose column 'tujuan' converts the Letter's Addressee
        $tujuan_surat = trim($_POST['tujuan'] ?? '');
        
        // Auto-Disposisi Target: Default Camat, or Override
        $target_disposisi = empty($tujuan_text) ? 'Camat (Pimpinan)' : $tujuan_text;
        
        // Insert into surat_masuk (Note: 'tujuan' column stores the Letter Addresssee)
        $stmt->execute([$uuid_surat, $no_agenda, $no_surat, $asal_surat, $tgl_surat, $perihal, $tujuan_surat, $klasifikasi, $file_path, $status, $tgl_agenda]);
        $id_sm = $db->lastInsertId();

        // 3. Auto-Disposisi REMOVED per Manifesto
        // "SuratQu: Input surat masuk... Tidak boleh disposisi"
        
        if ($action == 'agendakan') {
             // Just log activity
             logActivity("Mengagendakan Surat Masuk: $no_agenda", "surat_masuk", $id_sm);
             $msg = "Surat berhasil diagendakan. Silakan informasikan Pimpinan untuk Disposisi via Aplikasi Pimpinan (Camat).";
        } else {
            logActivity("Menyimpan Draft Surat: $no_surat", "surat_masuk", $id_sm);
            $msg = "Draft surat berhasil disimpan.";
        }

        $db->commit();
        $_SESSION['alert'] = ['msg' => $msg, 'type' => 'success'];
        header("Location: surat_masuk.php");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['alert'] = ['msg' => 'Gagal memproses: ' . $e->getMessage(), 'type' => 'danger'];
        header("Location: surat_masuk_tambah.php");
        exit;
    }
}
?>

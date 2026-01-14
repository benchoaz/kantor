<?php
/**
 * Disposisi
 * Form untuk membuat disposisi (FITUR UTAMA)
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAuth();

$pageTitle = 'Buat Disposisi';
$suratId = $_GET['surat_id'] ?? null;
$surat = null;

$api = new ApiClient();

// Jika ada surat_id, ambil detail surat
if ($suratId) {
    $response = $api->get(ENDPOINT_SURAT_DETAIL . '/' . $suratId);
    if ($response['success'] && isset($response['data'])) {
        $surat = $response['data'];
    } else {
        setFlashMessage('error', 'Detail surat tidak dapat dimuat: ' . ($response['message'] ?? 'Unknown error'));
    }
}

// Ambil daftar pegawai/unit untuk tujuan disposisi
$tujuanResponse = $api->get('/pimpinan/daftar-tujuan-disposisi');
$daftarTujuan = [];
$errorMsg = null;

if ($tujuanResponse['success'] && isset($tujuanResponse['data']) && !empty($tujuanResponse['data'])) {
    // Filter out restricted keywords but allow specific Kasubag Perencanaan & Keuangan
    $daftarTujuan = array_filter($tujuanResponse['data'], function($item) {
        $namaLower = strtolower($item['nama'] ?? '');
        $jabatanLower = strtolower($item['jabatan'] ?? '');
        
        // Block operators
        if (str_contains($namaLower, 'operator') || str_contains($jabatanLower, 'operator')) return false;
        
        // Specific block for Kasi PMD
        if (str_contains($namaLower, 'pmd') || str_contains($jabatanLower, 'pmd')) return false;

        // Specific block for Kasi Keuangan
        if (str_contains($namaLower, 'kasi keuangan') || str_contains($jabatanLower, 'kasi keuangan')) return false;

        // Otherwise allow
        return true;
    });
} else {
    // FALLBACK: Gunakan daftar default jika API bermasalah
    // Note: User can explicitly ask to enable "Docku" targets here if needed.
    $errorMsg = 'Info: Menggunakan daftar tujuan darurat (Gagal mengambil data dari Server API).';
    $daftarTujuan = [
        ['id' => 'sekcam', 'nama' => 'Sekretaris Kecamatan', 'jabatan' => 'Sekcam'],
        ['id' => 'kasi_pem', 'nama' => 'Kasi Pemerintahan', 'jabatan' => 'Kasi'],
        ['id' => 'kasi_trantib', 'nama' => 'Kasi Trantib', 'jabatan' => 'Kasi'],
        ['id' => 'kasubag_umum', 'nama' => 'Kasubag Umum & Kepegawaian', 'jabatan' => 'Kasubag'],
        ['id' => 'kasubag_perencanaan', 'nama' => 'Kasubag Perencanaan & Keuangan', 'jabatan' => 'Kasubag']
    ];
}

// Proses submit disposisi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    // Verify CSRF
    if (ENABLE_CSRF_PROTECTION && !verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid']);
            exit;
        }
        setFlashMessage('error', 'Token keamanan tidak valid');
        redirect('disposisi.php' . ($suratId ? '?surat_id=' . $suratId : ''));
    }
    
    
    $tujuan = $_POST['tujuan'] ?? [];
    $sifat_disposisi = $_POST['sifat_disposisi'] ?? '';
    $catatan = sanitize($_POST['catatan'] ?? '');
    $deadline = $_POST['deadline'] ?? '';
    
    // Map to new format
    $sifat = strtoupper($sifat_disposisi); // SEGERA, PENTING, BIASA, RAHASIA
    $penerima = is_array($tujuan) ? array_map(function($uid){ 
        return ['user_id' => $uid, 'tipe' => 'TINDAK_LANJUT']; 
    }, (array)$tujuan) : [['user_id' => $tujuan, 'tipe' => 'TINDAK_LANJUT']];
    $instruksi = [['isi' => $catatan]]; // Wrap catatan as instruksi
    
    // CRITICAL FIX: Dynamic toRole based on first recipient
    $toRole = 'sekcam'; // Default fallback
    if (!empty($penerima[0]['user_id'])) {
        // Get role from first recipient
        $firstRecipient = $penerima[0]['user_id'];
        $recipientData = $api->get('/users/' . $firstRecipient);
        if ($recipientData['success'] && isset($recipientData['data']['role'])) {
            $toRole = $recipientData['data']['role'];
        }
    }
    
    // CRITICAL FIX: File validation (governance requirement)
    if (empty($surat['scan_surat'])) {
        $errors[] = 'Scan surat wajib tersedia sebelum disposisi (cacat administrasi)';
    }
    
    // Validasi
    $errors = [];
    if (empty($tujuan)) {
        $errors[] = 'Pilih minimal satu tujuan disposisi';
    }
    if (empty($sifat_disposisi)) {
        $errors[] = 'Sifat disposisi wajib dipilih';
    }
    if (empty($catatan)) {
        $errors[] = 'Isi disposisi wajib diisi';
    }
    if (empty($deadline)) {
        $errors[] = 'Deadline wajib diisi';
    }
    
    if (empty($errors)) {
        // CRITICAL FIX: Correct session structure (Phase B flat session)
        // Kirim ke API (ROLE-BASED PAYLOAD)
        $data = [
            'uuid_surat' => $surat['uuid'] ?? $suratId,
            'from' => [
                'uuid_user' => $_SESSION['uuid_user'] ?? null,  // FIXED: Phase B session
                'user_id'   => $_SESSION['user_id'],            // Legacy for audit
                'role'      => $_SESSION['role'] ?? 'pimpinan', // FIXED: Flat session
                'source'    => 'camat'
            ],
            'to' => [
                'role' => $toRole  // Now dynamic from recipient
            ],
            'penerima' => $penerima,
            'instruksi' => $instruksi,
            'sifat' => $sifat,
            'catatan' => $catatan,
            'deadline' => $deadline
        ];

        $response = $api->post('/pimpinan/disposisi', $data);

        if ($response['success']) {
            if ($isAjax) {
                header('Content-Type: application/json');
                // CRITICAL FIX: Add redirect to prevent double submission
            echo json_encode([
                'success' => true,
                'message' => 'Disposisi berhasil dikirim',
                'data' => $response['data'] ?? [],
                'redirect' => $suratId 
                    ? 'modules/surat/detail.php?surat_id=' . urlencode($suratId)
                    : 'monitoring.php'
            ]);
                exit;
            }
            setFlashMessage('success', 'Disposisi berhasil dikirim');
            redirect($suratId ? 'modules/surat/detail.php?surat_id=' . $suratId : 'monitoring.php');
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(400); // Bad Request
                echo json_encode(['success' => false, 'message' => $response['message'] ?? 'Gagal mengirim disposisi']);
                exit;
            }
            setFlashMessage('error', $response['message'] ?? 'Gagal mengirim disposisi');
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
            exit;
        }
        setFlashMessage('error', implode('<br>', $errors));
    }
}

include 'includes/header.php';
?>

<style>
/* Reset and simplification for form controls */
.form-control-pill {
    display: block;
    width: 100%;
    padding: 12px 20px;
    background-color: #ffffff;
    border: 1px solid #cbd5e0;
    border-radius: 50px;
    font-size: 15px;
    color: #2D3748;
    line-height: 1.5;
    outline: none;
    -webkit-appearance: none;
    appearance: none;
    transition: all 0.2s;
}

.form-control-pill:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(122, 155, 142, 0.15);
}

textarea.form-control-pill {
    border-radius: 20px;
    min-height: 120px;
}

/* Ensure date input behaves like a standard date picker */
input[type="date"].form-control-pill {
    -webkit-appearance: auto !important;
    appearance: auto !important;
    min-height: 48px;
    padding-right: 12px;
    background-color: #ffffff !important;
}

/* Label styling */
.form-label-premium {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    margin-left: 12px;
}
</style>

<div class="page-header">
    <h1 class="page-title">Buat Disposisi</h1>
    <p class="page-subtitle">Berikan arahan untuk surat ini</p>
</div>

<?php if ($errorMsg): ?>
    <div class="alert-box alert-critical">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?php echo e($errorMsg); ?></span>
    </div>
<?php endif; ?>

<!-- Info Surat (Card Overlay) -->
<?php if ($surat): 
    // Extract fields from API response (matching user request to show all data)
    $idSurat = $surat['id_surat'] ?? '';
    $id = $surat['id'] ?? '';
    $nomorSurat = $surat['nomor_surat'] ?? '-';
    $asalSurat = $surat['asal_surat'] ?? $surat['pengirim'] ?? '-';
    $perihal = $surat['perihal'] ?? '-';
    $nomorAgenda = $surat['nomor_agenda'] ?? '-';
    $tanggalSurat = $surat['tanggal_surat'] ?? '-';
    $status = $surat['status'] ?? '-';
    $sourceApp = $surat['source_app'] ?? '-';
?>
<div style="background: white; border-radius: 24px; box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.05); margin-bottom: 24px; padding: 24px; border-left: 4px solid var(--primary);">
    <!-- Header: Asal Surat & Status -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
        <div>
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--primary); margin-bottom: 4px;">Asal Surat</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--text-main); line-height: 1.3;"><?php echo e($asalSurat); ?></div>
        </div>
        <div>
            <span style="display: inline-block; background: #EDF2F7; color: #4A5568; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                <?php echo e($status); ?>
            </span>
        </div>
    </div>

    <!-- Perihal -->
    <div style="margin-bottom: 20px;">
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 4px;">Perihal</div>
        <div style="font-size: 15px; color: var(--text-main); line-height: 1.6; font-weight: 500;">
            "<?php echo e($perihal); ?>"
        </div>
    </div>

    <!-- Data Grid -->
    <div style="background: #F8FAFC; border-radius: 16px; padding: 16px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px;">
        <!-- Nomor Surat -->
        <div>
            <div style="font-size: 10px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; font-weight: 600;">Nomor Surat</div>
            <div style="font-size: 13px; font-family: monospace; color: var(--text-main); font-weight: 600;"><?php echo e($nomorSurat); ?></div>
        </div>
        
        <!-- Nomor Agenda -->
        <div>
            <div style="font-size: 10px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; font-weight: 600;">Nomor Agenda</div>
            <div style="font-size: 13px; font-family: monospace; color: var(--text-main); font-weight: 600;"><?php echo e($nomorAgenda); ?></div>
        </div>
        
        <!-- Tanggal Surat -->
        <div>
            <div style="font-size: 10px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; font-weight: 600;">Tanggal Surat</div>
            <div style="font-size: 13px; color: var(--text-main); font-weight: 600;"><?php echo e($tanggalSurat); ?></div>
        </div>
        
        <!-- Source / ID -->
        <div>
            <div style="font-size: 10px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; font-weight: 600;">Sumber / ID</div>
            <div style="font-size: 13px; color: var(--text-main); font-weight: 600;">
                <?php echo e($sourceApp); ?> <span style="color: var(--text-muted); font-weight: 400;">(ID: <?php echo e($id); ?>/<?php echo e($idSurat); ?>)</span>
            </div>
        </div>
    </div>
    
    <!-- Link Scan -->
    <?php if (!empty($surat['scan_surat'])): ?>
    <div style="padding-top: 8px; border-top: 1px dashed rgba(0,0,0,0.1); text-align: center;">
        <a href="<?php echo e($surat['scan_surat']); ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: var(--primary); text-decoration: none; padding: 8px 16px; background: rgba(0, 0, 0, 0.03); border-radius: 50px; transition: all 0.2s;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span>Lihat Scan Surat</span>
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Form Disposisi -->
<!-- onsubmit handler removed to use JS listener -->
<form method="POST" id="disposisiForm">
    <?php echo csrfField(); ?>
    
    <div style="background: white; border-radius: 24px; padding: 24px; box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.05); display: flex; flex-direction: column; align-items: stretch; text-align: left;">
        
        <div style="margin-bottom: 20px;">
            <label class="form-label-premium" for="tujuan">Diteruskan Kepada *</label>
            <div style="position: relative;">
                <select name="tujuan" id="tujuan" class="form-control-pill" required style="appearance: none; cursor: pointer;">
                    <option value="">-- Pilih Tujuan --</option>
                    <?php foreach ($daftarTujuan as $tujuanItem): ?>
                    <option value="<?php echo e($tujuanItem['id']); ?>" 
                            data-nama="<?php echo e($tujuanItem['nama']); ?>"
                            data-jabatan="<?php echo e($tujuanItem['jabatan'] ?? ''); ?>">
                        <?php echo e($tujuanItem['nama']); ?> - <?php echo e($tujuanItem['jabatan'] ?? 'Staff'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
            </div>
        </div>


        <div style="margin-bottom: 20px;">
            <label class="form-label-premium" for="sifat_disposisi">Sifat Disposisi *</label>
            <div style="position: relative;">
                <select name="sifat_disposisi" id="sifat_disposisi" class="form-control-pill" required style="appearance: none; cursor: pointer;">
                    <option value="">-- Pilih Sifat --</option>
                    <option value="Segera" <?php echo (isset($_POST['sifat_disposisi']) && $_POST['sifat_disposisi'] == 'Segera') ? 'selected' : ''; ?>>Segera</option>
                    <option value="Penting" <?php echo (isset($_POST['sifat_disposisi']) && $_POST['sifat_disposisi'] == 'Penting') ? 'selected' : ''; ?>>Penting</option>
                    <option value="Biasa" <?php echo (isset($_POST['sifat_disposisi']) && $_POST['sifat_disposisi'] == 'Biasa') ? 'selected' : ''; ?>>Biasa</option>
                    <option value="Rahasia" <?php echo (isset($_POST['sifat_disposisi']) && $_POST['sifat_disposisi'] == 'Rahasia') ? 'selected' : ''; ?>>Rahasia</option>
                </select>
                <div style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label class="form-label-premium" for="catatan">Instruksi / Catatan *</label>
            <textarea 
                name="catatan" 
                id="catatan" 
                class="form-control-pill interactive-input" 
                placeholder="Tuliskan arahan..."
                required
                style="min-height: 120px; resize: vertical;"
            ><?php echo e($_POST['catatan'] ?? ''); ?></textarea>
        </div>
        
        <div style="margin-bottom: 32px;">
            <label class="form-label-premium" for="deadline">Target Penyelesaian *</label>
            <input 
                type="date" 
                name="deadline" 
                id="deadline" 
                class="form-control-pill interactive-input" 
                value="<?php echo e($_POST['deadline'] ?? ''); ?>"
                required
            >
        </div>
        
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <?php if ($suratId): ?>
            <a href="modules/surat/detail.php?surat_id=<?php echo e($suratId); ?>" style="display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: #EDF2F7; color: var(--text-muted); text-decoration: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
            </a>
            <?php else: ?>
            <a href="surat-masuk.php" style="display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: #EDF2F7; color: var(--text-muted); text-decoration: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>
            </a>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary btn-pill" style="flex: 1; height: 48px; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 15px rgba(122, 155, 142, 0.4);">
                <span>Kirim Disposisi</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </div>
    </div>
</form>


<!-- Script Handler Disposisi Manual (AJAX) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('disposisiForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // MENCEGAH RELOAD HALAMAN
            
            // Validasi client-side
            if (!validateDisposisiForm('disposisiForm')) {
                return false;
            }
            
            const btnSubmit = form.querySelector('button[type="submit"]');
            const originalBtnContent = btnSubmit.innerHTML;
            
            // UI State: Loading
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = `
                <span style="display:inline-block; width:16px; height:16px; border:2px solid white; border-top-color:transparent; border-radius:50%; animation: spin 1s linear infinite; margin-right:8px;"></span>
                <span>Mengirim...</span>
            `;
            
            // Prepare Data
            const formData = new FormData(form);
            
            // Kirim via AJAX Fetch
            fetch('disposisi.php?surat_id=<?php echo $suratId; ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Header penting untuk deteksi AJAX di PHP
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Sukses
                    alert('BERHASIL: ' + data.message);
                    window.location.href = data.redirect;
                } else {
                    // Gagal dari API
                    alert('GAGAL: ' + data.message);
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalBtnContent;
                }
            })
            .catch(error => {
                // Error Jaringan / Server
                console.error('Error:', error);
                alert('TERJADI KESALAHAN SISTEM: Permintaan tidak dapat diproses.');
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalBtnContent;
            });
        });
    }

    // Add CSS for spinner if not exists
    if (!document.getElementById('spinner-style')) {
        const style = document.createElement('style');
        style.id = 'spinner-style';
        style.innerHTML = `
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        `;
        document.head.appendChild(style);
    }
});
</script>

<?php include 'includes/footer.php'; ?>

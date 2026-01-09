<?php
// integrasi_pengaturan.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/sidiksae_api_client.php';

require_auth();

// Only admin can access
if ($_SESSION['role'] !== 'admin') {
    redirect('index.php', 'Akses ditolak!', 'danger');
}

$configFile = __DIR__ . '/config/integration.php';
$config = require $configFile;

// Handle Form Submission
if (isset($_POST['save_settings'])) {
    $base_url = trim($_POST['base_url']);
    $api_key = trim($_POST['api_key']);
    $client_id = trim($_POST['client_id']);
    $client_secret = trim($_POST['client_secret']);
    $enabled = isset($_POST['enabled']) ? 'true' : 'false';
    $timeout = (int)$_POST['timeout'];
    $source_base_url = trim($_POST['source_base_url']);

    // Generate PHP config file content
    $content = "<?php\n";
    $content .= "// config/integration.php\n";
    $content .= "// SidikSae Centralized API Integration Configuration\n\n";
    $content .= "return [\n";
    $content .= "    'sidiksae' => [\n";
    $content .= "        'base_url' => '" . addslashes($base_url) . "',\n";
    $content .= "        'api_key' => '" . addslashes($api_key) . "',\n";
    $content .= "        'client_id' => '" . addslashes($client_id) . "',\n";
    $content .= "        'client_secret' => '" . addslashes($client_secret) . "',\n";
    $content .= "        'enabled' => $enabled,\n";
    $content .= "        'timeout' => $timeout,\n";
    $content .= "    ],\n";
    $content .= "    'source' => [\n";
    $content .= "        'base_url' => '" . addslashes($source_base_url) . "',\n";
    $content .= "    ]\n";
    $content .= "];\n";

    if (file_put_contents($configFile, $content)) {
        redirect('integrasi_pengaturan.php', 'Pengaturan berhasil disimpan!', 'success');
    } else {
        $error = "Gagal menulis file konfigurasi. Pastikan folder config memiliki izin tulis.";
    }
}

// Handle Test Connection
$test_result = null;
if (isset($_POST['test_connection'])) {
    $base_url = trim($_POST['base_url']);
    $api_key = trim($_POST['api_key']);
    $client_id = trim($_POST['client_id']);
    $client_secret = trim($_POST['client_secret']);
    
    try {
        $testConfig = [
            'base_url' => $base_url,
            'api_key' => $api_key,
            'client_id' => $client_id,
            'user_id' => 1,
            'client_secret' => $client_secret,
            'timeout' => 10
        ];
        
        $apiClient = new SidikSaeApiClient($testConfig);
        $result = $apiClient->testConnection();
        
        if ($result['success']) {
            $test_result = [
                'status' => 'success',
                'msg' => '✓ Koneksi Berhasil! Sistem terpusat merespons dengan baik dan autentikasi berhasil.'
            ];
        } else {
            $test_result = [
                'status' => 'danger',
                'msg' => '✗ ' . ($result['message'] ?? 'Koneksi gagal. Periksa konfigurasi Anda.')
            ];
        }
    } catch (Exception $e) {
        $test_result = [
            'status' => 'danger',
            'msg' => '✗ Error: ' . $e->getMessage()
        ];
    }
}

include 'includes/header.php';
?>

<div class="mb-4 pt-2 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold mb-1">Pengaturan Integrasi Sistem</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="integrasi_sistem.php">Monitoring Integrasi</a></li>
                <li class="breadcrumb-item active">Pengaturan</li>
            </ol>
        </nav>
    </div>
    <a href="integrasi_sistem.php" class="btn btn-outline-primary btn-sm px-4">
        <i class="fa-solid fa-arrow-left me-2"></i> Kembali
    </a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($test_result)): ?>
<div class="alert alert-<?= $test_result['status'] ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="fa-solid <?= $test_result['status'] == 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-2"></i> <?= $test_result['msg'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom border-0 shadow-sm p-4">
            <form action="" method="POST">
                <div class="mb-4">
                    <h5 class="fw-bold text-primary mb-3">
                        <i class="fa-solid fa-cloud me-2"></i> Koneksi ke Sistem Terpusat SidikSae
                    </h5>
                    <p class="text-muted small mb-3">
                        Hubungkan SuratQu dengan sistem terpusat SidikSae untuk sinkronisasi disposisi otomatis.
                    </p>
                    
                    <div class="form-check form-switch p-0 ps-5">
                        <input class="form-check-input" type="checkbox" name="enabled" id="enabledSwitch" 
                               <?= $config['sidiksae']['enabled'] ? 'checked' : '' ?> 
                               style="width: 3em; height: 1.5em; cursor: pointer;">
                        <label class="form-check-label ms-2 fw-bold" for="enabledSwitch">
                            Aktifkan Sinkronisasi Otomatis
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1 ps-5">
                        Jika dimatikan, disposisi tidak akan dikirim ke sistem terpusat.
                    </small>
                </div>

                <hr class="my-4 opacity-10">

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fa-solid fa-link me-2 text-primary"></i> URL API SidikSae
                    </label>
                    <input type="url" name="base_url" class="form-control form-control-lg bg-light border-0" 
                           value="<?= htmlspecialchars($config['sidiksae']['base_url']) ?>" 
                           placeholder="https://api.sidiksae.my.id" required>
                    <div class="form-text small">
                        <i class="fa-solid fa-circle-info me-1"></i> 
                        Alamat API sistem terpusat SidikSae
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fa-solid fa-key me-2 text-primary"></i> API Key
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0">
                            <i class="fa-solid fa-shield-halved text-muted"></i>
                        </span>
                        <input type="password" name="api_key" id="apiKey" 
                               class="form-control form-control-lg bg-light border-0" 
                               value="<?= htmlspecialchars($config['sidiksae']['api_key']) ?>" 
                               placeholder="sk_live_..." required>
                        <button class="btn btn-light border-0" type="button" onclick="togglePassword('apiKey', 'toggleIcon1')">
                            <i id="toggleIcon1" class="fa-solid fa-eye-slash"></i>
                        </button>
                    </div>
                    <div class="form-text small">Kunci autentikasi dari administrator SidikSae</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fa-solid fa-id-card me-2 text-primary"></i> Client ID
                    </label>
                    <input type="text" name="client_id" class="form-control form-control-lg bg-light border-0" 
                           value="<?= htmlspecialchars($config['sidiksae']['client_id'] ?? 'suratqu') ?>" 
                           placeholder="suratqu" required>
                    <div class="form-text small">ID Identitas aplikasi (biasanya 'suratqu')</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">
                        <i class="fa-solid fa-lock me-2 text-primary"></i> Client Secret
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0">
                            <i class="fa-solid fa-user-secret text-muted"></i>
                        </span>
                        <input type="password" name="client_secret" id="clientSecret" 
                               class="form-control form-control-lg bg-light border-0" 
                               value="<?= htmlspecialchars($config['sidiksae']['client_secret']) ?>" 
                               placeholder="suratqu_secret_..." required>
                        <button class="btn btn-light border-0" type="button" onclick="togglePassword('clientSecret', 'toggleIcon2')">
                            <i id="toggleIcon2" class="fa-solid fa-eye-slash"></i>
                        </button>
                    </div>
                    <div class="form-text small">Kunci rahasia untuk keamanan tambahan</div>
                </div>

                <hr class="my-4 opacity-10">

                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label fw-bold">URL SuratQu Anda</label>
                        <input type="url" name="source_base_url" class="form-control bg-light border-0" 
                               value="<?= htmlspecialchars($config['source']['base_url']) ?>" 
                               placeholder="https://suratqu.instansi.go.id" required>
                        <div class="form-text small">Digunakan untuk link detail surat</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Timeout (detik)</label>
                        <div class="input-group">
                            <input type="number" name="timeout" class="form-control bg-light border-0" 
                                   value="<?= (int)$config['sidiksae']['timeout'] ?>" 
                                   min="5" max="60" required>
                            <span class="input-group-text bg-light border-0 text-muted">detik</span>
                        </div>
                        <div class="form-text small">Batas waktu koneksi API</div>
                    </div>
                </div>

                <div class="row pt-2 g-2">
                    <div class="col-md-8">
                        <button type="submit" name="save_settings" class="btn btn-primary btn-lg shadow-sm w-100">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Pengaturan
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="test_connection" class="btn btn-outline-primary btn-lg shadow-sm w-100">
                            <i class="fa-solid fa-vial me-2"></i> Test Koneksi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        }
    }
    </script>

    <div class="col-lg-4">
        <div class="card card-custom border-0 shadow-sm p-4 bg-light border-start border-5 border-primary mb-4">
            <h6 class="fw-bold mb-3">
                <i class="fa-solid fa-circle-info me-2 text-primary"></i> Tentang Integrasi
            </h6>
            <p class="small text-muted mb-0">
                SuratQu terhubung dengan <strong>Sistem Terpusat SidikSae</strong> untuk mengirimkan disposisi secara otomatis. 
                <br><br>
                Sistem ini memungkinkan sinkronisasi data antara SuratQu dan aplikasi lain seperti Docku.
                <br><br>
                <strong>Keuntungan:</strong>
                <br>• Disposisi otomatis tersinkron
                <br>• Data terpusat dan aman
                <br>• Monitoring real-time
            </p>
        </div>

        <div class="card card-custom border-0 shadow-sm p-4 border-start border-5 border-warning">
            <h6 class="fw-bold mb-2 text-warning">
                <i class="fa-solid fa-shield-halved me-2"></i> Keamanan
            </h6>
            <p class="small text-muted mb-0">
                <strong>API Key</strong> dan <strong>Client Secret</strong> adalah kunci rahasia. 
                Jangan bagikan kepada siapapun!
                <br><br>
                Hubungi administrator SidikSae jika Anda lupa atau perlu mengganti credentials.
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

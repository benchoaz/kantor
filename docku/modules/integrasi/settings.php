<?php
// modules/integrasi/settings.php
$page_title = 'Pengaturan Integrasi';
$active_page = 'integrasi';

require_once '../../config/database.php';
require_once '../../includes/header.php';

// Access Control: Admin Only
if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('Akses Ditolak'); window.location='../../index.php';</script>";
    exit;
}

// Handle Form Submission
$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $redirectParams = '';
            
            if ($_POST['action'] === 'update_sidiksae') {
                $id = intval($_POST['id']);
                $url = filter_var($_POST['outbound_url'], FILTER_SANITIZE_URL);
                $key = trim($_POST['outbound_key']);
                $secret = trim($_POST['client_secret']);
                $app_url = filter_var($_POST['app_url'], FILTER_SANITIZE_URL);
                $timeout = intval($_POST['timeout']);
                
                $stmt = $pdo->prepare("UPDATE integrasi_config SET outbound_url = ?, outbound_key = ?, client_secret = ?, app_url = ?, timeout = ?, is_active = 1 WHERE id = ?");
                if ($stmt->execute([$url, $key, $secret, $app_url, $timeout, $id])) {
                    $redirectParams = 'msg=success&txt=Konfigurasi SidikSae berhasil disimpan';
                } else {
                    $redirectParams = 'msg=error&txt=Gagal menyimpan konfigurasi';
                }
            } elseif ($_POST['action'] === 'update_telegram') {
                $token = trim($_POST['bot_token']);
                
                $stmtCheck = $pdo->prepare("SELECT id FROM integrasi_config WHERE label = 'Telegram' LIMIT 1");
                $stmtCheck->execute();
                $existingId = $stmtCheck->fetchColumn();
                
                if ($existingId) {
                    $stmt = $pdo->prepare("UPDATE integrasi_config SET outbound_key = ?, is_active = 1 WHERE id = ?");
                    $stmt->execute([$token, $existingId]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO integrasi_config (label, outbound_key, is_active, inbound_key) VALUES ('Telegram', ?, 1, 'TG-BOT')");
                    $stmt->execute([$token]);
                }
                $redirectParams = 'msg=success&txt=Konfigurasi Bot Telegram berhasil disimpan';
            } elseif ($_POST['action'] === 'generate_inbound') {
                $label = trim(htmlspecialchars($_POST['label']));
                $newKey = bin2hex(random_bytes(32));
                
                $stmt = $pdo->prepare("INSERT INTO integrasi_config (label, inbound_key, is_active) VALUES (?, ?, 1)");
                if ($stmt->execute([$label, $newKey])) {
                    $redirectParams = 'msg=success&txt=API Key Inbound baru berhasil dibuat';
                }
            } elseif ($_POST['action'] === 'delete') {
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM integrasi_config WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $redirectParams = 'msg=success&txt=Integrasi berhasil dihapus';
                }
            }
            
            if ($redirectParams) {
                header("Location: settings.php?" . $redirectParams);
                exit;
            }
            
        } catch (PDOException $e) {
            $errorMsg = urlencode("Database Error: " . $e->getMessage());
            header("Location: settings.php?msg=error&txt=" . $errorMsg);
            exit;
        }
    }
}

// Handle Flash Messages
if (isset($_GET['msg'])) {
    $msgType = ($_GET['msg'] === 'success') ? 'success' : 'danger';
    $message = htmlspecialchars($_GET['txt'] ?? '');
}

// Fetch SidikSae Config
$stmtS = $pdo->prepare("SELECT * FROM integrasi_config WHERE label = 'SidikSae' LIMIT 1");
$stmtS->execute();
$sidiksae = $stmtS->fetch(PDO::FETCH_ASSOC);

// Fetch Telegram Config
$stmtT = $pdo->prepare("SELECT * FROM integrasi_config WHERE label = 'Telegram' LIMIT 1");
$stmtT->execute();
$telegram = $stmtT->fetch(PDO::FETCH_ASSOC);

// Fetch Other Integrations (Exclude SidikSae and Telegram)
$others = $pdo->query("SELECT * FROM integrasi_config WHERE label NOT IN ('SidikSae', 'Telegram') ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Base Endpoint URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = $_SERVER['SCRIPT_NAME'];
$basePath = preg_replace('#/modules/integrasi/.*$#', '', $scriptPath);
$endpointUrl = $protocol . '://' . $host . $basePath . '/api/v1/disposisi/receive.php';
?>

<div class="container-fluid py-4 fade-in">
    <div class="row mb-4">
        <div class="col">
            <h4 class="fw-bold mb-1">🔌 Integrasi Sistem</h4>
            <p class="text-muted small">Kelola konektivitas antar-sistem SidikSae, SuratQu, dan Telegram.</p>
        </div>
        <div class="col-auto">
            <a href="tutorial.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-book me-1"></i> Panduan Integrasi
            </a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
        <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Main Integration: SidikSae -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-primary">
                            <i class="bi bi-link-45deg me-1"></i> Konfigurasi Akun SidikSae
                        </h5>
                        <?php if ($sidiksae): ?>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="testConnection(<?= $sidiksae['id'] ?>)" id="testBtn_<?= $sidiksae['id'] ?>">
                            <i class="bi bi-plug-fill me-1"></i> Test Koneksi
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if ($sidiksae): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_sidiksae">
                        <input type="hidden" name="id" value="<?= $sidiksae['id'] ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted mb-1">
                                <i class="bi bi-globe me-1"></i> URL API SidikSae
                            </label>
                            <input type="url" name="outbound_url" class="form-control form-control-lg bg-light border-0" 
                                   placeholder="https://api.sidiksae.my.id/api/v1/" value="<?= htmlspecialchars($sidiksae['outbound_url'] ?? '') ?>" required>
                            <div class="form-text small"><i class="bi bi-info-circle me-1"></i> Alamat API sistem terpusat SidikSae</div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted mb-1">
                                    <i class="bi bi-key me-1"></i> API Key
                                </label>
                                <div class="input-group input-group-lg bg-light rounded-3 overflow-hidden">
                                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-shield-lock text-primary"></i></span>
                                    <input type="password" name="outbound_key" class="form-control bg-transparent border-0" 
                                           id="apiKey" placeholder="sk_live_..." value="<?= htmlspecialchars($sidiksae['outbound_key'] ?? '') ?>">
                                    <button class="btn btn-link text-muted border-0" type="button" onclick="togglePassword('apiKey')">
                                        <i class="bi bi-eye" id="apiKeyIcon"></i>
                                    </button>
                                </div>
                                <div class="form-text small">Kunci autentikasi dari administrator SidikSae</div>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted mb-1">
                                    <i class="bi bi-lock me-1"></i> Client Secret
                                </label>
                                <div class="input-group input-group-lg bg-light rounded-3 overflow-hidden">
                                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-bank text-primary"></i></span>
                                    <input type="password" name="client_secret" class="form-control bg-transparent border-0" 
                                           id="clientSecret" placeholder="suratqu_secret_..." value="<?= htmlspecialchars($sidiksae['client_secret'] ?? '') ?>">
                                    <button class="btn btn-link text-muted border-0" type="button" onclick="togglePassword('clientSecret')">
                                        <i class="bi bi-eye" id="clientSecretIcon"></i>
                                    </button>
                                </div>
                                <div class="form-text small">Kunci rahasia untuk keamanan tambahan</div>
                            </div>
                        </div>

                        <hr class="my-4 opacity-10">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-muted mb-1">URL SuratQu Anda</label>
                                <input type="url" name="app_url" class="form-control bg-light border-0" 
                                       placeholder="https://suratqu.sidiksae.my.id" value="<?= htmlspecialchars($sidiksae['app_url'] ?? '') ?>">
                                <div class="form-text small">Digunakan untuk link detail surat</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted mb-1">Timeout (detik)</label>
                                <div class="input-group">
                                    <input type="number" name="timeout" class="form-control bg-light border-0" 
                                           min="1" max="60" value="<?= intval($sidiksae['timeout'] ?? 10) ?>">
                                    <span class="input-group-text bg-light border-0">detik</span>
                                </div>
                                <div class="form-text small">Batas waktu koneksi API</div>
                            </div>
                        </div>

                        <div class="mt-4 pt-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-3 fw-bold">
                                <i class="bi bi-save2 me-2"></i> Simpan Konfigurasi
                            </button>
                        </div>
                    </form>
                    <div id="testResult_<?= $sidiksae['id'] ?>" class="mt-3" style="display:none;"></div>
                    <?php else: ?>
                    <div class="alert alert-warning">Konfigurasi 'SidikSae' tidak ditemukan di database. Pastikan migrasi sudah dijalankan.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Inbound Keys -->
            <div class="mt-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white p-4 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-box-arrow-in-right me-1 text-success"></i> Inbound API Keys</h6>
                        <button class="btn btn-success btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAddInbound">
                            <i class="bi bi-plus-lg me-1"></i> Buat Baru
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 custom-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Label</th>
                                        <th>Endpoint</th>
                                        <th>Inbound Key</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($others as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold"><?= htmlspecialchars($row['label']) ?></span>
                                            <div class="small text-muted"><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm" style="max-width: 250px;">
                                                <input type="text" class="form-control bg-light border-0 small" value="<?= htmlspecialchars($endpointUrl) ?>" readonly id="end_<?= $row['id'] ?>">
                                                <button class="btn btn-outline-secondary border-0 bg-light" type="button" onclick="copyText('end_<?= $row['id'] ?>')">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm" style="max-width: 200px;">
                                                <input type="password" class="form-control bg-light border-0 small" value="<?= htmlspecialchars($row['inbound_key']) ?>" readonly id="inkey_<?= $row['id'] ?>">
                                                <button class="btn btn-outline-secondary border-0 bg-light" type="button" onclick="togglePassword('inkey_<?= $row['id'] ?>')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary border-0 bg-light" type="button" onclick="copyText('inkey_<?= $row['id'] ?>')">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-link text-danger p-0" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['label']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($others)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Belum ada kunci API inbound tambahan.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Config -->
        <div class="col-lg-4">
            <!-- Telegram Section -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-telegram text-info fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Bot Telegram</h6>
                    </div>
                    <p class="text-muted small">Aktifkan notifikasi pimpinan via filter Telegram.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_telegram">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">BOT Token</label>
                            <input type="password" name="bot_token" id="botToken" class="form-control border-0 bg-light" 
                                   placeholder="123456:ABC-DEF..." value="<?= htmlspecialchars($telegram['outbound_key'] ?? '') ?>">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-info text-white fw-bold">Simpan Bot</button>
                            <button type="button" id="btnTestTelegram" class="btn btn-link py-0 text-info small text-decoration-none" <?= empty($telegram['outbound_key']) ? 'disabled' : '' ?>>
                                <i class="bi bi-send me-1"></i> Kirim Test Pesan
                            </button>
                        </div>
                    </form>
                    <div id="testTelegramResult" class="mt-3" style="display:none;"></div>
                </div>
            </div>

            <!-- Help & Info -->
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb-fill me-2"></i>Butuh Bantuan?</h6>
                    <p class="small opacity-75 mb-4">Pastikan URL API dan API Key sesuai dengan data yang diberikan oleh administrator pusat SidikSae.</p>
                    <a href="tutorial.php" class="btn btn-light btn-sm w-100 rounded-pill fw-bold text-primary px-3">
                        Buka Dokumentasi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Inbound -->
<div class="modal fade" id="modalAddInbound" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow rounded-4">
            <input type="hidden" name="action" value="generate_inbound">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Buat Kunci API Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Nama Sistem / Label</label>
                    <input type="text" name="label" class="form-control bg-light border-0" placeholder="Contoh: E-Office" required>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success rounded-pill px-4">Generate</button>
            </div>
        </form>
    </div>
</div>

<form id="formDelete" method="POST" style="display:none;"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="deleteId"></form>

<style>
.custom-table thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6c757d; border-bottom: none; }
.custom-table tbody td { border-bottom: 1px solid #f8f9fa; }
.form-control:focus { box-shadow: none; background-color: #f0f2f5 !important; }
.btn-link:hover { opacity: 0.8; }
</style>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = document.getElementById(id + 'Icon') || event.currentTarget.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}

function copyText(id) {
    const input = document.getElementById(id);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
    
    const originalValue = input.value;
    input.value = "Tersalin!";
    setTimeout(() => { input.value = originalValue; }, 1000);
}

function confirmDelete(id, label) {
    if (confirm('Hapus integrasi "'+label+'"?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('formDelete').submit();
    }
}

function testConnection(id) {
    const btn = document.getElementById('testBtn_' + id);
    const resultDiv = document.getElementById('testResult_' + id);
    const originalHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Connecting...';
    resultDiv.style.display = 'none';
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('test_connection.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        resultDiv.style.display = 'block';
        if (data.status === 'success') {
            resultDiv.innerHTML = '<div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i>' + data.message + '</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger border-0 shadow-sm"><i class="bi bi-exclamation-octagon-fill me-2"></i>' + data.message + '</div>';
        }
    })
    .catch(error => {
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="alert alert-danger border-0 shadow-sm">Error: ' + error.message + '</div>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

const btnTestTelegram = document.getElementById('btnTestTelegram');
if (btnTestTelegram) {
    btnTestTelegram.addEventListener('click', function() {
        const resultDiv = document.getElementById('testTelegramResult');
        btnTestTelegram.disabled = true;
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="alert alert-info py-2 small border-0"><i class="bi bi-hourglass-split me-1"></i> Menghubungi...</div>';
        
        fetch('test_telegram.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            resultDiv.innerHTML = '<div class="alert alert-' + (data.status === 'success' ? 'success' : 'danger') + ' py-2 small border-0">' + data.message + '</div>';
        })
        .finally(() => { btnTestTelegram.disabled = false; });
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>

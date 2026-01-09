<?php
// modules/integrasi/tutorial.php
$page_title = 'Panduan Integrasi';
$active_page = 'integrasi';

require_once '../../config/database.php';
require_once '../../includes/header.php';

// Access Control: Admin Only
if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('Akses Ditolak'); window.location='../../index.php';</script>";
    exit;
}

// Base URL handling for code snippets
$server_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$api_endpoint = $server_url . "/api/v1/disposisi/receive.php";
?>

<div class="row fade-in justify-content-center">
    <div class="col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">📚 Panduan Integrasi API</h4>
                <p class="text-muted small">Dokumentasi teknis untuk menghubungkan sistem luar dengan BESUK SAE.</p>
            </div>
            <a href="settings.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Pengaturan
            </a>
        </div>

        <div class="card card-modern border-0 shadow-sm mb-4">
            <div class="card-header bg-white p-4 border-bottom">
                <h5 class="fw-bold text-primary mb-0"><i class="bi bi-diagram-3 me-2"></i>Alur Kerja Integrasi</h5>
            </div>
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center mb-3 mb-md-0">
                        <div class="p-3 bg-light rounded-3 border border-dark border-opacity-10">
                            <h6 class="fw-bold">Sistem Luar</h6>
                            <small class="text-muted">(cth: SuratQu)</small>
                            <div class="mt-2 text-primary fw-bold">1. POST JSON</div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="bi bi-arrow-right fs-1 text-muted d-none d-md-inline"></i>
                        <i class="bi bi-arrow-down fs-1 text-muted d-md-none"></i>
                        <div class="my-2">
                            <div class="badge bg-primary p-2">API BESUK SAE</div>
                        </div>
                        <i class="bi bi-arrow-right fs-1 text-muted d-none d-md-inline"></i>
                        <i class="bi bi-arrow-down fs-1 text-muted d-md-none"></i>
                    </div>
                    <div class="col-md-4 text-center mt-3 mt-md-0">
                        <div class="p-3 bg-light rounded-3 border border-dark border-opacity-10">
                            <h6 class="fw-bold">BESUK SAE</h6>
                            <small class="text-muted">(Disposisi & Laporan)</small>
                            <div class="mt-2 text-success fw-bold">2. Webhook Callback</div>
                        </div>
                    </div>
                </div>
                <p class="text-muted mt-4 mb-0 text-center small">
                    Sistem luar mengirim data disposisi ke BESUK SAE. Setelah petugas menyelesaikan tugas di lapagan dan mendokumentasikannya, BESUK SAE akan mengirim notifikasi balik (Webhook) ke sistem asal.
                </p>
            </div>
        </div>

        <div class="card card-modern border-0 shadow-sm mb-4">
            <div class="card-header bg-white p-4 border-bottom">
                <span class="badge bg-primary float-end">Langkah 1</span>
                <h5 class="fw-bold mb-0">📡 Inbound API (Menerima Data)</h5>
            </div>
            <div class="card-body p-4">
                <p>Gunakan endpoint ini untuk mengirim disposisi baru ke BESUK SAE.</p>
                
                <div class="mb-3">
                    <label class="fw-bold small text-muted">ENDPOINT URL</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light font-monospace">POST</span>
                        <input type="text" class="form-control font-monospace" value="<?= $api_endpoint ?>" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold small text-muted">HEADERS</label>
                    <div class="bg-dark text-white p-3 rounded-3 font-monospace small">
Content-Type: application/json
X-API-KEY: [Kunci API Inbound Anda]
                    </div>
                </div>

                <label class="fw-bold small text-muted">CONTOH BODY (JSON)</label>
                <div class="bg-dark text-white p-3 rounded-3 font-monospace small position-relative">
<pre class="m-0 text-white">
{
  "external_id": "SRT-2024-001",
  "perihal": "Pengecekan Drainase Sumbat",
  "instruksi": "Mohon cek lokasi di Jl. Merdeka No. 45 dan dokumentasikan.",
  "tgl_disposisi": "2024-01-01 08:00:00",
  "target_username": "petugas_lapangan" 
}
</pre>
                </div>
                
                <div class="alert alert-warning mt-3 small">
                    <i class="bi bi-exclamation-triangle me-1"></i> <strong>Penting:</strong> Sesuaikan URL endpoint dengan domain deployment Anda. 
                    Contoh untuk production: <code>https://sidiksae.my.id/api/v1/disposisi/receive.php</code>
                </div>
                
                <div class="alert alert-info mt-3 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i> <strong>Catatan:</strong> Jika <code>target_username</code> tidak ditemukan, disposisi akan masuk ke Admin.
                </div>
            </div>
        </div>

        <!-- Testing Guide --
        <div class="card card-modern border-0 shadow-sm mb-4">
            <div class="card-header bg-white p-4 border-bottom">
                <span class="badge bg-warning float-end">Testing</span>
                <h5 class="fw-bold mb-0">🧪 Testing dengan cURL</h5>
            </div>
            <div class="card-body p-4">
                <p>Untuk testing koneksi API, gunakan <code>curl</code> dari terminal atau Postman:</p>
                
                <label class="fw-bold small text-muted mb-2">Test 1: Check Endpoint Availability (GET)</label>
                <div class="bg-dark text-white p-3 rounded-3 font-monospace small mb-3">
<pre class="m-0 text-white">curl -X GET <?= $api_endpoint ?> -i</pre>
                </div>
                <small class="text-muted">✅ Expected: HTTP 200 dengan informasi endpoint</small>
                
                <hr class="my-3">
                
                <label class="fw-bold small text-muted mb-2">Test 2: Send Test Disposisi (POST)</label>
                <div class="bg-dark text-white p-3 rounded-3 font-monospace small mb-3" style="overflow-x: auto;">
<pre class="m-0 text-white">curl -X POST <?= $api_endpoint ?> \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: YOUR_API_KEY_HERE" \
  -d '{
    "external_id": "TEST_<?= time() ?>",
    "perihal": "Test Koneksi API",
    "instruksi": "Testing integrasi"
  }' \
  -i</pre>
                </div>
                <small class="text-muted">✅ Expected: HTTP 201 Created dengan response JSON sukses</small>
                
                <div class="alert alert-info mt-3 mb-0 small">
                    <i class="bi bi-lightbulb me-1"></i> <strong>Tip:</strong> Ganti <code>YOUR_API_KEY_HERE</code> dengan API Key yang didapat dari halaman Pengaturan Integrasi
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <!-- Outbound Guide -->
        <div class="card card-modern border-0 shadow-sm mb-4">
            <div class="card-header bg-white p-4 border-bottom">
                <span class="badge bg-success float-end">Langkah 2</span>
                <h5 class="fw-bold mb-0">↩️ Outbound Webhook (Respon Balik)</h5>
            </div>
            <div class="card-body p-4">
                <p>Ketika petugas mengklik tombol "Simpan Laporan" di BESUK SAE, sistem akan menembak URL Webhook yang Anda konfigurasi di halaman Pengaturan.</p>

                <label class="fw-bold small text-muted">PAYLOAD YANG DIKIRIM BESUK SAE</label>
                <div class="bg-dark text-white p-3 rounded-3 font-monospace small">
<pre class="m-0 text-white">
{
  "external_id": "SRT-2024-001",
  "status": "dilaksanakan",
  "updated_at": "2024-01-01 10:30:00",
  "notes": "Tindak lanjut telah didokumentasikan di BESUK SAE."
}
</pre>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Pastikan server Anda siap menerima method <strong>POST</strong> pada URL callback yang didaftarkan.</small>
                </div>
            </div>
        </div>

        <!-- Troubleshooting Guide -->
        <div class="card card-modern border-0 shadow-sm mb-4">
            <div class="card-header bg-white p-4 border-bottom">
                <span class="badge bg-danger float-end">Help</span>
                <h5 class="fw-bold mb-0">🔧 Troubleshooting</h5>
            </div>
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Common HTTP Error Codes:</h6>
                
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Code</th>
                                <th width="35%">Penyebab</th>
                                <th>Solusi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td><code class="text-danger">401</code></td>
                                <td>Missing API Key</td>
                                <td>Pastikan header <code>X-API-KEY</code> disertakan dalam request</td>
                            </tr>
                            <tr>
                                <td><code class="text-danger">403</code></td>
                                <td>Invalid API Key</td>
                                <td>Periksa API Key yang digunakan, pastikan sesuai dengan yang ada di Pengaturan</td>
                            </tr>
                            <tr>
                                <td><code class="text-danger">404</code></td>
                                <td>Endpoint URL salah</td>
                                <td>Verifikasi URL endpoint, pastikan path <code>/api/v1/disposisi/receive.php</code> benar</td>
                            </tr>
                            <tr>
                                <td><code class="text-warning">302</code></td>
                                <td>Redirect (biasanya ke login)</td>
                                <td>Pastikan mengakses endpoint API langsung, bukan melalui halaman web biasa</td>
                            </tr>
                            <tr>
                                <td><code class="text-danger">400</code></td>
                                <td>Invalid JSON / Missing Field</td>
                                <td>Periksa format JSON dan pastikan field <code>external_id</code> dan <code>perihal</code> ada</td>
                            </tr>
                            <tr>
                                <td><code class="text-danger">500</code></td>
                                <td>Server Error</td>
                                <td>Cek log server atau hubungi administrator</td>
                            </tr>
                            <tr>
                                <td><code class="text-success">201</code></td>
                                <td>✅ Success</td>
                                <td>Disposisi berhasil diterima dan disimpan</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-warning mt-3 mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i> <strong>Catatan Deployment:</strong> 
                    Testing integrasi antar-sistem (misal SuratQu ↔ BESUK SAE) memerlukan kedua aplikasi sudah di-deploy ke server yang bisa saling mengakses. 
                    Tidak bisa dilakukan di localhost karena kedua sistem berjalan terpisah.
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

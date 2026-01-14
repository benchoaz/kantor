<?php
/**
 * Alert Settings - Identity Admin Portal
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Settings | SidikSae Identity</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text-main); line-height: 1.6; }
        .app-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: #0f172a;
            color: white;
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
        }
        .sidebar-brand { font-size: 1.5rem; font-weight: 700; margin-bottom: 3rem; display: flex; align-items: center; gap: 0.75rem; }
        .sidebar-brand i { color: var(--primary); font-size: 1.8rem; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 0.75rem;
            transition: all 0.2s;
            margin-bottom: 0.5rem;
        }
        .nav-link:hover { background: rgba(99, 102, 241, 0.1); color: white; }
        .nav-link.active { background: var(--primary); color: white; }
        .main-content { margin-left: 280px; flex: 1; padding: 2rem 3rem; }
        .header { margin-bottom: 2rem; }
        .page-title h1 { font-size: 1.875rem; font-weight: 700; }
        .page-title p { color: var(--text-muted); font-size: 0.95rem; }
        .card { background: var(--card-bg); border-radius: 1rem; border: 1px solid var(--border); padding: 2rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.95rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 0.95rem;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .toggle {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 26px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 4px; bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(24px); }
    </style>
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-shield-check"></i>
                <span>SidikSae ID</span>
            </div>
            <nav>
                <a href="index.php" class="nav-link">
                    <i class="bi bi-people"></i>
                    <span>Manajemen User</span>
                </a>
                <a href="monitoring.php" class="nav-link">
                    <i class="bi bi-shield-exclamation"></i>
                    <span>Security Monitoring</span>
                </a>
                <a href="settings.php" class="nav-link active">
                    <i class="bi bi-gear"></i>
                    <span>Alert Settings</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="page-title">
                    <h1>Alert Settings</h1>
                    <p>Configure security notifications and alert thresholds.</p>
                </div>
            </header>

            <form id="settings-form">
                <div class="card">
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem;">Telegram Notifications</h3>
                    <div class="form-group" style="display: flex; align-items: center; justify-content: space-between;">
                        <label style="margin: 0;">Enable Telegram Alerts</label>
                        <label class="toggle">
                            <input type="checkbox" id="telegram_enabled" name="telegram_enabled">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Bot Token</label>
                        <input type="text" id="telegram_bot_token" name="telegram_bot_token" placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz">
                    </div>
                    <div class="form-group">
                        <label>Chat ID</label>
                        <input type="text" id="telegram_chat_id" name="telegram_chat_id" placeholder="-1001234567890">
                    </div>
                </div>

                <div class="card">
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem;">Security Thresholds</h3>
                    <div class="form-group">
                        <label>Login Failure Threshold (alerts triggered after this many failures)</label>
                        <input type="number" id="login_failure" name="login_failure" value="3" min="1" max="10">
                    </div>
                    <div class="form-group">
                        <label>Detection Window (seconds)</label>
                        <input type="number" id="window_seconds" name="window_seconds" value="60" min="30" max="300">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Pengaturan
                </button>
            </form>
        </main>
    </div>

    <script>
        // Identity Service Configuration
        const IDENTITY_API_BASE = 'https://id.sidiksae.my.id/v1';
        
        // Get Bearer token from PHP session (if available)
        const accessToken = '<?php echo $_SESSION['access_token'] ?? ''; ?>';

        async function loadSettings() {
            try {
                const headers = { 'Accept': 'application/json' };
                if (accessToken) {
                    headers['Authorization'] = `Bearer ${accessToken}`;
                }

                const res = await fetch(`${IDENTITY_API_BASE}/users/get-settings`, {
                    method: 'GET',
                    headers: headers
                });
                
                const result = await res.json();
                if (result.status === 'success') {
                    const s = result.data.settings;
                    document.getElementById('telegram_enabled').checked = s.telegram?.enabled || false;
                    document.getElementById('telegram_bot_token').value = s.telegram?.bot_token || '';
                    document.getElementById('telegram_chat_id').value = s.telegram?.chat_id || '';
                    document.getElementById('login_failure').value = s.thresholds?.login_failure || 3;
                    document.getElementById('window_seconds').value = s.thresholds?.window_seconds || 60;
                } else {
                    console.error('API Error:', result.message);
                }
            } catch (err) {
                console.error('Failed to load settings:', err);
                alert('Gagal memuat pengaturan. Pastikan Anda sudah login ke sistem.');
            }
        }

        async function saveSettings(e) {
            e.preventDefault();
            const settings = {
                telegram: {
                    enabled: document.getElementById('telegram_enabled').checked,
                    bot_token: document.getElementById('telegram_bot_token').value,
                    chat_id: document.getElementById('telegram_chat_id').value
                },
                thresholds: {
                    login_failure: parseInt(document.getElementById('login_failure').value),
                    window_seconds: parseInt(document.getElementById('window_seconds').value)
                },
                logging: {
                    enabled: true,
                    file: '../storage/logs/security_alerts.log'
                }
            };

            try {
                const headers = { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                };
                if (accessToken) {
                    headers['Authorization'] = `Bearer ${accessToken}`;
                }

                const res = await fetch(`${IDENTITY_API_BASE}/users/save-settings`, {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({ settings })
                });
                
                const result = await res.json();
                alert(result.message);
                if (result.status === 'success') loadSettings();
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }

        document.getElementById('settings-form').onsubmit = saveSettings;
        window.onload = loadSettings;
    </script>
</body>
</html>

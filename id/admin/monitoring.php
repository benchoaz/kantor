<?php
/**
 * Security Monitoring Dashboard - Identity Admin Portal
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Monitoring | SidikSae Identity</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-label { color: var(--text-muted); font-size: 0.875rem; font-weight: 500; display: block; }
        .stat-value { font-size: 2rem; font-weight: 700; display: block; margin-top: 0.5rem; }
        .card { background: var(--card-bg); border-radius: 1rem; border: 1px solid var(--border); overflow: hidden; margin-bottom: 1.5rem; }
        .card-header { padding: 1.5rem; border-bottom: 1px solid var(--border); }
        .card-header h3 { font-size: 1.125rem; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; }
        td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-success { background: #dcfce7; color: #15803d; }
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
                <a href="monitoring.php" class="nav-link active">
                    <i class="bi bi-shield-exclamation"></i>
                    <span>Security Monitoring</span>
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="bi bi-gear"></i>
                    <span>Alert Settings</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="page-title">
                    <h1>Security Monitoring</h1>
                    <p>Real-time security events and authentication trends across the ecosystem.</p>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Failed Logins (24h)</span>
                    <span class="stat-value" style="color: var(--danger);" id="failed-count">...</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Brute Force Detected</span>
                    <span class="stat-value" style="color: var(--warning);" id="brute-count">...</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Alerts Sent (24h)</span>
                    <span class="stat-value" style="color: var(--primary);" id="alert-count">...</span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Recent Security Events</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table id="events-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Event</th>
                                <th>User/IP</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                    Loading security events...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function loadMonitoring() {
            // Mock data for demonstration
            document.getElementById('failed-count').innerText = '12';
            document.getElementById('brute-count').innerText = '2';
            document.getElementById('alert-count').innerText = '5';

            const mockEvents = [
                { time: '2026-01-09 15:45:00', event: 'Brute Force Detection', detail: 'IP 103.xxx.xxx.1', priority: 'critical' },
                { time: '2026-01-09 15:30:12', event: 'Failed Login', detail: 'admin from 103.xxx.xxx.2', priority: 'warning' },
                { time: '2026-01-09 15:12:05', event: 'User Created', detail: 'uuid: abc-123', priority: 'info' },
            ];

            const tbody = document.querySelector('#events-table tbody');
            tbody.innerHTML = '';
            mockEvents.forEach(evt => {
                const badgeClass = evt.priority === 'critical' ? 'badge-danger' : 
                                   evt.priority === 'warning' ? 'badge-warning' : 'badge-success';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><code style="font-size: 0.85rem;">${evt.time}</code></td>
                    <td style="font-weight: 600;">${evt.event}</td>
                    <td>${evt.detail}</td>
                    <td><span class="badge ${badgeClass}">${evt.priority}</span></td>
                `;
                tbody.appendChild(tr);
            });
        }

        window.onload = loadMonitoring;
    </script>
</body>
</html>

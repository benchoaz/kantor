<?php
/**
 * Identity Admin Portal - SidikSae Identity
 * Single Authority for User Management
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Panel | SidikSae Identity</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --glass: rgba(255, 255, 255, 0.7);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text-main); line-height: 1.6; }

        .app-container { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #0f172a;
            color: white;
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }
        .sidebar-brand { font-size: 1.5rem; font-weight: 700; margin-bottom: 3rem; display: flex; align-items: center; gap: 0.75rem; }
        .sidebar-brand i { color: var(--primary); font-size: 1.8rem; }
        .sidebar-nav { flex: 1; list-style: none; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 0.75rem;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active { background: rgba(99, 102, 241, 0.1); color: white; }
        .nav-link.active { background: var(--primary); }

        /* Main Content */
        .main-content { margin-left: 280px; flex: 1; padding: 2rem 3rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem; }
        .page-title h1 { font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; }
        .page-title p { color: var(--text-muted); font-size: 0.95rem; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 1.25rem;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .stat-label { color: var(--text-muted); font-size: 0.875rem; font-weight: 500; }
        .stat-value { font-size: 2rem; font-weight: 700; display: block; margin-top: 0.5rem; }

        /* Table Section */
        .card { background: var(--card-bg); border-radius: 1.25rem; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .card-header { padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 1rem 2rem; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; }
        td { padding: 1.25rem 2rem; border-bottom: 1px solid var(--border); font-size: 0.95rem; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-muted); }
        .user-details .name { display: block; font-weight: 600; }
        .user-details .meta { display: block; font-size: 0.8rem; color: var(--text-muted); }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-inactive { background: #f1f5f9; color: #475569; }

        .actions { display: flex; gap: 0.5rem; }
        .btn-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; background: #f1f5f9; color: #475569; }
        .btn-icon:hover { background: #e2e8f0; color: var(--primary); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-up { animation: fadeIn 0.4s ease-out forwards; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-shield-check"></i>
                <span>SidikSae ID</span>
            </div>
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="bi bi-people"></i>
                        <span>Manajemen User</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="monitoring.php" class="nav-link">
                        <i class="bi bi-shield-exclamation"></i>
                        <span>Security Monitoring</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="bi bi-gear"></i>
                        <span>Alert Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Aplikasi Terdaftar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="bi bi-journal-text"></i>
                        <span>Audit Log Security</span>
                    </a>
                </li>
            </ul>
            <div style="margin-top: auto; padding-top: 2rem;">
                <a href="logout.php" class="nav-link" style="color: #ef4444;">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Keluar Sistem</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header animate-up">
                <div class="page-title">
                    <h1>Manajemen Pengguna</h1>
                    <p>Otoritas tunggal untuk manajemen identitas di seluruh ekosistem SidikSae.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="alert('Module Tambah User akan segera aktif')">
                        <i class="bi bi-plus-lg"></i>
                        Tambah Pengguna Baru
                    </button>
                </div>
            </header>

            <div class="stats-grid animate-up" style="animation-delay: 0.1s;">
                <div class="stat-card">
                    <span class="stat-label">Total Pengguna Terdaftar</span>
                    <span class="stat-value" id="count-total">...</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Aktif 24 Jam Terakhir</span>
                    <span class="stat-value" id="count-active" style="color: var(--success);">...</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Insiden Keamanan</span>
                    <span class="stat-value" id="count-incidents" style="color: var(--danger);">0</span>
                </div>
            </div>

            <div class="card animate-up" style="animation-delay: 0.2s;">
                <div class="card-header">
                    <h3 style="font-size: 1.125rem; font-weight: 600;">Daftar Pengguna Global</h3>
                    <div style="position: relative;">
                        <input type="text" placeholder="Cari username atau nama..." style="padding: 0.5rem 1rem 0.5rem 2.5rem; border: 1px solid var(--border); border-radius: 0.5rem; font-size: 0.875rem; width: 300px;">
                        <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table id="user-table">
                        <thead>
                            <tr>
                                <th>Identitas Pengguna</th>
                                <th>UUID Serial</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Login Terakhir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals (Simple Glassmorphism) -->
    <div id="modal-container" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:100; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition: all 0.3s;">
        <div class="card" style="width: 500px; padding: 2rem; position:relative;">
            <button onclick="closeModal()" style="position:absolute; right:1.5rem; top:1.5rem; background:none; border:none; color:var(--text-muted); cursor:pointer;"><i class="bi bi-x-lg"></i></button>
            <h2 id="modal-title" style="margin-bottom:1.5rem; font-size:1.5rem;">Tambah User</h2>
            <form id="user-form">
                <input type="hidden" name="uuid_user" id="form-uuid">
                <div style="display:grid; gap:1.25rem;">
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.5rem;">Full Name</label>
                        <input type="text" name="full_name" id="form-fullname" required style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:0.5rem;">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.5rem;">Username</label>
                        <input type="text" name="username" id="form-username" required style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:0.5rem;">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.5rem;">Password</label>
                        <input type="password" name="password" id="form-password" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:0.5rem;" placeholder="Leave empty if no change">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.5rem;">Role</label>
                        <select name="role" id="form-role" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:0.5rem;">
                            <option value="">-- Select Role --</option>
                            <!-- Populated via JS -->
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.5rem;">Status</label>
                        <select name="status" id="form-status" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:0.5rem;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:0.875rem;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let isEdit = false;

        function openModal(user = null) {
            isEdit = !!user;
            document.getElementById('modal-title').innerText = isEdit ? 'Ubah User' : 'Tambah User';
            document.getElementById('form-uuid').value = user?.uuid_user || '';
            document.getElementById('form-fullname').value = user?.full_name || '';
            document.getElementById('form-username').value = user?.username || '';
            document.getElementById('form-username').value = user?.username || '';
            document.getElementById('form-username').disabled = isEdit;
            document.getElementById('form-status').value = user?.status || 'active';
            document.getElementById('form-password').required = !isEdit;
            document.getElementById('form-role').value = (user && user.role_slugs && user.role_slugs.length > 0) ? user.role_slugs[0] : '';

            const mc = document.getElementById('modal-container');
            mc.style.display = 'flex';
            setTimeout(() => { mc.style.opacity = '1'; mc.style.pointerEvents = 'auto'; }, 10);
        }

        function closeModal() {
            const mc = document.getElementById('modal-container');
            mc.style.opacity = '0';
            mc.style.pointerEvents = 'none';
            setTimeout(() => { mc.style.display = 'none'; }, 300);
        }

        async function saveUser(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const endpoint = isEdit ? '../v1/users/update' : '../v1/users/create';
            
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const res = await response.json();
                if (res.status === 'success') {
                    closeModal();
                    fetchUsers();
                    fetchStats();
                    alert(res.message);
                } else {
                    alert(res.message);
                }
            } catch (err) {
                alert("Error: " + err.message);
            }
        }

        async function deleteUser(uuid) {
            if (!confirm('Hapus user ini secara permanen dari ekosistem?')) return;
            try {
                const response = await fetch('../v1/users/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ uuid_user: uuid })
                });
                const res = await response.json();
                if (res.status === 'success') {
                    fetchUsers();
                    fetchStats();
                    alert(res.message);
                }
            } catch (err) {
                alert("Error: " + err.message);
            }
        }

        async function fetchStats() {
            try {
                const response = await fetch('../v1/users/stats');
                const result = await response.json();
                if (result.status === 'success') {
                    const stats = result.data.stats;
                    document.getElementById('count-total').innerText = stats.total_users;
                    document.getElementById('count-active').innerText = stats.active_users_24h;
                    document.getElementById('count-incidents').innerText = stats.security_incidents_24h;
                }
            } catch (error) { console.error("Gagal memuat stats:", error); }
        }

        async function fetchRoles() {
            try {
                const response = await fetch('../v1/users/roles'); // Fixed endpoint
                const result = await response.json();
                if (result.status === 'success') {
                    const select = document.getElementById('form-role');
                    select.innerHTML = '<option value="">-- Select Role --</option>';
                    result.data.roles.forEach(role => {
                        const option = document.createElement('option');
                        option.value = role.slug; // Assign via slug
                        option.textContent = role.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) { console.error("Gagal memuat roles:", error); }
        }

        async function fetchUsers() {
            try {
                const response = await fetch('../v1/users/list');
                const result = await response.json();
                if (result.status === 'success') {
                    renderUsers(result.data.users);
                    // Stats are handled by fetchStats now
                }
            } catch (error) { console.error("Gagal memuat user:", error); }
        }

        function renderUsers(users) {
            const tbody = document.querySelector('#user-table tbody');
            tbody.innerHTML = '';
            users.forEach(user => {
                const roleBadge = user.roles && user.roles.length > 0 
                     ? `<span style="background:#e0e7ff; color:#4338ca; padding:2px 8px; border-radius:4px; font-size:0.75rem; font-weight:600;">${user.roles[0]}</span>` 
                     : '<span style="color:#94a3b8; font-size:0.75rem;">Guest</span>';
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">${user.username.charAt(0).toUpperCase()}</div>
                            <div class="user-details">
                                <span class="name">${user.full_name}</span>
                                <span class="meta">@${user.username}</span>
                            </div>
                        </div>
                    </td>
                    <td><code style="font-size: 0.75rem; color: var(--primary);">${user.uuid_user}</code></td>
                    <td>${roleBadge}</td>
                    <td><span class="status-badge status-${user.status}">${user.status}</span></td>
                    <td><span style="font-size: 0.85rem; color: var(--text-muted);">${user.last_login_at || 'Belum pernah'}</span></td>
                    <td>
                        <div class="actions">
                            <button class="btn-icon" title="Edit User" onclick='openModal(${JSON.stringify(user)})'><i class="bi bi-pencil-square"></i></button>
                            <button class="btn-icon" title="Delete" onclick="deleteUser('${user.uuid_user}')" style="color:var(--danger)"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        document.getElementById('user-form').onsubmit = saveUser;
        document.querySelector('.header-actions .btn').onclick = () => openModal();
        window.onload = () => {
            fetchUsers();
            fetchStats();
            fetchRoles();
        };
    </script>
</body>
</html>

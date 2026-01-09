<?php
// FILE: modules/auth/login.php
require_once __DIR__ . '/../../helpers/session_helper.php';

// If already logged in, go to dashboard
if (is_logged_in()) {
    header("Location: /index.php");
    exit;
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel Pimpinan</title>
    <!-- Use a simple CSS for login to keep it self-contained or link to assets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-sm bg-white p-8 rounded-xl shadow-lg border border-slate-100">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Panel Pimpinan</h1>
            <p class="text-sm text-slate-500 mt-2">Masuk untuk mengakses disposisi</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-4 p-3 rounded-lg text-sm <?php echo $flash['type'] === 'error' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <form action="/modules/auth/auth_process.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Username / NIP</label>
                <input type="text" name="username" required 
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required 
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>

            <button type="submit" 
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-lg transition shadow-sm">
                Masuk
            </button>
        </form>
        
        <div class="mt-6 text-center text-xs text-slate-400">
            &copy; <?php echo date('Y'); ?> SidikSae Camat App
        </div>
    </div>

</body>
</html>

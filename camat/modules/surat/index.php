<?php
// FILE: modules/surat/index.php
require_once __DIR__ . '/../../helpers/session_helper.php';
require_once __DIR__ . '/../../helpers/api_helper.php';

require_login();

// 1. Fetch Data from API
$token = get_token();
$response = call_api('GET', ENDPOINT_SURAT_MASUK, [], $token);

$surat_list = [];
if ($response['success']) {
    $surat_list = $response['data'] ?? [];
} else {
    // If token invalid, maybe auto logout logic?
    // For now, just show empty
    $error_msg = $response['message'] ?? 'Gagal mengambil data surat.';
}

$user = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Masuk - Panel Camat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen pb-20">

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-md mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-semibold text-slate-800">Surat Masuk</div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 hidden sm:inline"><?php echo htmlspecialchars($user['name'] ?? 'Pimpinan'); ?></span>
                <a href="/modules/auth/logout.php" class="text-red-500 text-sm hover:text-red-700">Keluar</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-md mx-auto px-4 py-6 space-y-4">
        
        <?php if (isset($error_msg)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-4">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($surat_list)): ?>
            <div class="text-center py-10 text-slate-400">
                <p>Tidak ada surat masuk baru.</p>
            </div>
        <?php else: ?>
            <?php foreach ($surat_list as $surat): ?>
                <a href="/index.php?page=detail&id=<?php echo urlencode($surat['id_surat']); ?>" class="block bg-white p-4 rounded-xl border border-slate-100 shadow-sm active:scale-[0.98] transition">
                    <div class="flex justify-between items-start mb-2">
                        <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">
                            Baru
                        </span>
                        <span class="text-xs text-slate-400">
                            <?php echo htmlspecialchars($surat['tanggal_surat'] ?? ''); ?>
                        </span>
                    </div>
                    <div class="font-medium text-slate-800 leading-tight mb-1">
                        <?php echo htmlspecialchars($surat['perihal'] ?? 'Tanpa Perihal'); ?>
                    </div>
                    <div class="text-sm text-slate-500">
                        Dari: <?php echo htmlspecialchars($surat['asal_surat'] ?? '-'); ?>
                    </div>
                    <div class="text-xs text-slate-400 mt-2">
                        No: <?php echo htmlspecialchars($surat['nomor_surat'] ?? '-'); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

    </main>

</body>
</html>

<?php
// FILE: modules/surat/detail.php
// ARCHITECTURAL FIX: Use Core Config & Auth to prevent session fragmentation & Session Name Mismatch

// 1. Define APP_INIT to allow config access
if (!defined('APP_INIT')) define('APP_INIT', true);

// 2. Load Main Config FIRST (Sets SESSION_NAME 'CAMAT_SESSION')
require_once __DIR__ . '/../../config/config.php';

// 3. Load Auth & API Helpers
require_once __DIR__ . '/../../helpers/session_helper.php';
require_once __DIR__ . '/../../includes/auth.php'; 
require_once __DIR__ . '/../../helpers/api_helper.php';

// 4. Start Session is handled by auth.php - REMOVED double session_start
// if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 5. Check Auth
requireAuth(); 

// BACKWARD COMPATIBILITY: Accept both 'surat_id' (NEW) and 'id' (LEGACY)
$id_surat = $_GET['surat_id'] ?? $_GET['id'] ?? null;

// Strict ID Validation
if (!$id_surat) {
    // Stop execution to prevent redirect loop default behavior
    die('<div style="padding: 20px; font-family: sans-serif; text-align: center;">
            <h2 style="color: red;">Error: Parameter surat_id Wajib Ada</h2>
            <p>Parameter URL <code>?surat_id=XXX</code> tidak ditemukan. <br>Kembali ke <a href="/surat-masuk.php">Surat Masuk</a>.</p>
         </div>');
}

// 1. Fetch Detail from API
$token = get_token();
$response = call_api('GET', ENDPOINT_SURAT_DETAIL . '/' . $id_surat, [], $token);

// 2. API Error Handling
if (!$response['success']) {
    die('<div style="padding: 20px; text-align: center; font-family: sans-serif;">
        <h3 style="color: #DC2626;">Gagal Memuat Surat</h3>
        <p style="color: #6B7280;">' . htmlspecialchars($response['message'] ?? 'Koneksi API error') . '</p>
        <a href="/surat-masuk.php" style="color: #059669; text-decoration: none;">← Kembali ke Inbox</a>
    </div>');
}

$surat = $response['data'] ?? [];

// 3. DATA GUARD - Stop if Empty
if (empty($surat) || !is_array($surat)) {
    // PRODUCTION DEBUG MODE: Add ?debug=1 to URL to see raw response
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        echo '<pre style="background: #1F2937; color: #10B981; padding: 20px; font-size: 12px;">';
        echo "=== DEBUG MODE ===\n";
        echo "URL Parameter surat_id: " . htmlspecialchars($id_surat) . "\n";
        echo "API Endpoint: " . ENDPOINT_SURAT_DETAIL . '/' . $id_surat . "\n";
        echo "Response Success: " . ($response['success'] ? 'YES' : 'NO') . "\n\n";
        echo "Full Response:\n";
        print_r($response);
        echo "\n\nRaw Data:\n";
        print_r($surat);
        echo '</pre>';
        die();
    }
    
    die('<div style="padding: 20px; text-align: center; font-family: sans-serif;">
        <h3 style="color: #DC2626;">Data Surat Kosong</h3>
        <p style="color: #6B7280;">API tidak mengembalikan data atau struktur tidak dikenali.</p>
        <p style="font-size: 12px; color: #9CA3AF;">Tip: Tambahkan <code>?debug=1</code> di URL untuk melihat response API</p>
        <a href="/surat-masuk.php" style="color: #059669; text-decoration: none;">← Kembali ke Inbox</a>
    </div>');
}

// 4. ALIGNED WITH NEW IDENTITY STANDARD
// Use 'id' or 'id_surat' as the Single Source of Truth (SuratQu ID)
$detail = [
    'id' => $surat['id_surat'] ?? $surat['id'] ?? $id_surat, 
    
    // Nomor Surat - Check semua varian key yang mungkin
    'nomor' => $surat['nomor_surat'] ?? 
               $surat['no_surat'] ?? 
               $surat['nomor_agenda'] ?? 
               $surat['ref_id'] ?? 
               '(Nomor Tidak Tersedia)',
    
    // Asal Surat - Check pengirim/asal/instansi
    'asal' => $surat['asal_surat'] ?? 
              $surat['pengirim'] ?? 
              $surat['instansi_pengirim'] ?? 
              $surat['from'] ?? 
              '(Asal Tidak Diketahui)',
    
    // Perihal - Check perihal/subject/tentang
    'perihal' => $surat['perihal'] ?? 
                 $surat['perihal_surat'] ?? 
                 $surat['subject'] ?? 
                 $surat['tentang'] ?? 
                 '(Perihal Kosong)',
    
    // Tanggal - Check berbagai format tanggal
    'tanggal' => $surat['tanggal_surat'] ?? 
                 $surat['tgl_surat'] ?? 
                 $surat['tanggal_terima'] ?? 
                 $surat['created_at'] ?? 
                 date('Y-m-d'),
    
    // Scan/File - Check berbagai key file
    'scan_url' => $surat['scan_surat'] ?? 
                  $surat['file_url'] ?? 
                  $surat['file'] ?? 
                  $surat['attachment'] ?? 
                  $surat['lampiran'] ?? 
                  '',
    
    // Sifat Surat
    'sifat' => $surat['sifat'] ?? 
               $surat['sifat_surat'] ?? 
               $surat['priority'] ?? 
               'Biasa'
];

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail & Disposisi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Flatpickr for Date -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen pb-24">

    <!-- Header -->
    <header class="bg-white px-4 py-3 flex items-center gap-3 border-b border-slate-200 sticky top-0 z-20">
        <a href="/index.php" class="p-2 -ml-2 text-slate-600 hover:bg-slate-50 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <h1 class="font-semibold text-slate-800">Detail Surat</h1>
    </header>

    <main class="max-w-md mx-auto p-4 space-y-6">

        <?php if ($flash): ?>
            <div class="<?php echo $flash['type'] === 'error' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'; ?> p-4 rounded-xl text-sm mb-4">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- SECTION 1: READ ONLY DATA SURAT -->
        <section class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 space-y-4">
            <div>
                <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">Perihal</label>
                <div class="text-slate-800 font-medium leading-relaxed">
                    <?php echo htmlspecialchars($detail['perihal']); ?>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                     <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">ID SuratQu</label>
                     <div class="text-sm font-bold text-blue-600"><?php echo htmlspecialchars($detail['id']); ?></div>
                </div>
                <div>
                     <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">Identitas</label>
                     <div class="text-[10px] text-slate-400 font-mono">Verified by API</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">Nomor Surat</label>
                    <div class="text-sm text-slate-700"><?php echo htmlspecialchars($detail['nomor']); ?></div>
                </div>
                <div>
                     <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">Tanggal</label>
                     <div class="text-sm text-slate-700"><?php echo htmlspecialchars($detail['tanggal']); ?></div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">Asal Surat</label>
                <div class="text-sm text-slate-700"><?php echo htmlspecialchars($detail['asal']); ?></div>
            </div>

            <?php if (!empty($detail['scan_url']) && $detail['scan_url'] !== '#'): ?>
            <div class="pt-2">
                <a href="<?php echo htmlspecialchars($detail['scan_url']); ?>" target="_blank" class="inline-flex items-center gap-2 text-emerald-600 text-sm font-medium hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Lihat Scan Surat
                </a>
            </div>
            <?php endif; ?>
        </section>

        <!-- SECTION 2: FORM DISPOSISI -->
        <section id="form-disposisi" class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden relative">
            <!-- Decorative Header Strip -->
            <div class="h-1.5 bg-gradient-to-r from-emerald-500 to-teal-500"></div>

            <div class="p-6">
                <!-- Header Section -->
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Lembar Disposisi
                    </h2>
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400 bg-slate-100 px-2 py-1 rounded">Formulir Pimpinan</div>
                </div>

                <form action="/modules/disposisi/process.php" method="POST" class="space-y-8" onsubmit="return validateForm()">
                    <input type="hidden" name="id_surat" value="<?php echo htmlspecialchars($detail['id']); ?>">
                    <input type="hidden" name="nomor_surat" value="<?php echo htmlspecialchars($detail['nomor']); ?>">
                    <input type="hidden" name="asal_surat" value="<?php echo htmlspecialchars($detail['asal']); ?>">
                    <input type="hidden" name="perihal" value="<?php echo htmlspecialchars($detail['perihal']); ?>">
                    <input type="hidden" name="tanggal_surat" value="<?php echo htmlspecialchars($detail['tanggal']); ?>">
                    <input type="hidden" name="scan_surat" value="<?php echo htmlspecialchars($detail['scan_url']); ?>">

                    <!-- DASAR SURAT SUMMARY -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-sm text-slate-600 space-y-2">
                        <div class="flex items-start gap-2">
                            <span class="font-bold uppercase text-[10px] text-slate-400 w-20 shrink-0 mt-0.5">Dasar Surat</span>
                            <div class="grid grid-cols-1 gap-1 w-full">
                                <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($detail['nomor']); ?></div>
                                <div class="text-xs">Dari: <?php echo htmlspecialchars($detail['asal']); ?></div>
                                <div class="text-xs text-slate-500">Tgl: <?php echo htmlspecialchars($detail['tanggal']); ?></div>
                                <div class="text-xs italic bg-white p-2 rounded border border-slate-100 mt-1">"<?php echo htmlspecialchars($detail['perihal']); ?>"</div>
                                <?php if (!empty($detail['scan_url']) && $detail['scan_url'] !== '#'): ?>
                                <div class="mt-2">
                                    <a href="<?php echo htmlspecialchars($detail['scan_url']); ?>" target="_blank" class="inline-flex items-center gap-1.5 text-emerald-600 text-[10px] uppercase font-bold hover:underline bg-emerald-50 px-2 py-1 rounded-full border border-emerald-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        Lihat File Asli
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- GROUP 1: Sifat & Target -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-slate-50/50 rounded-xl border border-slate-100">
                        <!-- Sifat Disposisi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Sifat Surat</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="cursor-pointer relative">
                                    <input type="radio" name="sifat" value="Biasa" class="peer sr-only" checked>
                                    <div class="p-2.5 rounded-lg border border-slate-200 bg-white text-slate-600 text-center text-xs font-semibold hover:bg-slate-50 peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-700 transition-all shadow-sm">
                                        Biasa
                                    </div>
                                </label>
                                <label class="cursor-pointer relative">
                                    <input type="radio" name="sifat" value="Segera" class="peer sr-only">
                                    <div class="p-2.5 rounded-lg border border-slate-200 bg-white text-slate-600 text-center text-xs font-semibold hover:bg-slate-50 peer-checked:bg-yellow-50 peer-checked:border-yellow-500 peer-checked:text-yellow-700 transition-all shadow-sm">
                                        ⚡ Segera
                                    </div>
                                </label>
                                <label class="cursor-pointer relative">
                                    <input type="radio" name="sifat" value="Penting" class="peer sr-only">
                                    <div class="p-2.5 rounded-lg border border-slate-200 bg-white text-slate-600 text-center text-xs font-semibold hover:bg-slate-50 peer-checked:bg-orange-50 peer-checked:border-orange-500 peer-checked:text-orange-700 transition-all shadow-sm">
                                        ! Penting
                                    </div>
                                </label>
                                <label class="cursor-pointer relative">
                                    <input type="radio" name="sifat" value="Rahasia" class="peer sr-only">
                                    <div class="p-2.5 rounded-lg border border-slate-200 bg-white text-slate-600 text-center text-xs font-semibold hover:bg-slate-50 peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:text-purple-700 transition-all shadow-sm">
                                        🔒 Rahasia
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Batas Waktu -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Batas Penyelesaian</label>
                            <div class="relative group">
                                <input type="text" name="batas_waktu" id="batas_waktu" class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm font-medium bg-white group-hover:border-slate-300 transition-colors" placeholder="Pilih tanggal...">
                                <div class="absolute left-3 top-2.5 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GROUP 2: Tujuan -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-4 flex justify-between items-center">
                            <span>Diteruskan Kepada <span class="text-red-500">*</span></span>
                            <span class="text-[10px] font-normal text-slate-400 bg-slate-50 px-2 py-1 rounded-full">Pilih satu atau lebih</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php 
                            // 1. Fetch Dynamic Targets from API
                            $resTargets = call_api('GET', ENDPOINT_DAFTAR_TUJUAN, [], $token);
                            $targets = [];
                            
                            if ($resTargets['success'] && !empty($resTargets['data'])) {
                                $targets = $resTargets['data'];
                            } else {
                                // Fallback to standard structural roles if API is not ready
                                $targets = [
                                    'Sekretaris Kecamatan', 'Kasi Pemerintahan', 'Kasi Pelayanan Umum', 
                                    'Kasi Trantib', 'Kasi PMD', 'Kasubag Umum & Kepegawaian',
                                    'Kasubag Perencanaan & Keuangan'
                                ];
                            }

                            foreach($targets as $item): 
                                // Logic: Use role_slug for distribution (Stabil/Positions)
                                $target_value = is_array($item) ? ($item['role_slug'] ?? $item['jabatan'] ?? '') : $item;
                                $target_id = is_array($item) ? ($item['user_id'] ?? '') : '';
                                
                                // Main Label = Jabatan/Role
                                $main_label = is_array($item) ? ($item['jabatan'] ?? $item['role'] ?? $item['name'] ?? $item) : $item;
                                // Sub Label = Full Name (Small)
                                $sub_label = is_array($item) ? ($item['full_name'] ?? '') : '';
                            ?>
                            <label class="flex items-center p-3 rounded-lg border border-slate-200 hover:bg-emerald-50/50 cursor-pointer transition-all group has-[:checked]:bg-emerald-50 has-[:checked]:border-emerald-500 has-[:checked]:shadow-sm">
                                <div class="relative flex items-center">
                                    <input type="checkbox" name="diteruskan_kepada[]" value="<?= htmlspecialchars($target_value) ?>" data-user-id="<?= htmlspecialchars($target_id) ?>" class="peer w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 transition-transform active:scale-95">
                                </div>
                                <div class="ml-3 flex flex-col">
                                    <span class="text-sm font-bold text-slate-800 group-has-[:checked]:text-emerald-900 transition-colors"><?= htmlspecialchars(strtoupper($main_label)) ?></span>
                                    <?php if($sub_label && strtolower($sub_label) !== strtolower($main_label)): ?>
                                        <span class="text-[10px] text-slate-500 font-medium tracking-tight"><?= htmlspecialchars($sub_label) ?></span>
                                    <?php endif; ?>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- GROUP 3: Instruksi Pimpinan -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                             <label class="block text-sm font-bold text-slate-700">Instruksi Pimpinan <span class="text-red-500">*</span></label>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span class="text-[10px] uppercase font-bold text-slate-400 py-1.5">Quick Add:</span>
                            <?php 
                            $instructions = ['Tindak lanjuti', 'Koordinasikan', 'Untuk diketahui', 'Pelajari & Laporkan', 'Wakili Saya', 'Arsipkan'];
                            foreach($instructions as $inst): 
                            ?>
                            <button type="button" onclick="addInstruction('<?= $inst ?>')" class="px-3 py-1.5 bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-700 text-xs font-medium rounded-full border border-slate-200 transition-colors">
                                + <?= $inst ?>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="relative">
                            <textarea name="isi_disposisi" id="isi_disposisi" rows="4" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm leading-relaxed placeholder-slate-400 shadow-sm transition-shadow" placeholder="Tulis instruksi atau perintah pimpinan di sini..." required></textarea>
                            <div class="absolute bottom-3 right-3 text-slate-300 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Action -->
                    <div class="pt-4 border-t border-slate-100">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-emerald-200/50 transition-all flex justify-center items-center gap-2 group">
                            <span>KIRIM DISPOSISI</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </button>
                        <a href="/index.php" class="block text-center text-slate-400 text-sm mt-4 hover:text-slate-600 transition-colors">Batal, kembali ke Inbox</a>
                    </div>

                </form>
            </div>
        </section>

    </main>

    <script>
        flatpickr("#batas_waktu", {
            dateFormat: "Y-m-d",
            minDate: "today",
            defaultDate: new Date(),
            locale: {
                firstDayOfWeek: 1
            }
        });
        
        // Auto scroll if hash is present
        document.addEventListener("DOMContentLoaded", function() {
            if (window.location.hash === '#form-disposisi') {
                setTimeout(function() {
                     const element = document.getElementById('form-disposisi');
                     if (element) {
                         element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                         // Focus visual
                         element.classList.add('ring-2', 'ring-emerald-500', 'ring-offset-2');
                         setTimeout(() => {
                             element.classList.remove('ring-2', 'ring-emerald-500', 'ring-offset-2');
                         }, 2000);
                     }
                }, 300); // 300ms delay to ensure heavy layout is done
            }
        });

        function addInstruction(text) {
            const textarea = document.getElementById('isi_disposisi');
            const currentVal = textarea.value;
            
            if (currentVal.trim() === "") {
                textarea.value = "- " + text;
            } else {
                textarea.value = currentVal.trim() + "\n- " + text;
            }
            textarea.focus();
            textarea.scrollTop = textarea.scrollHeight;
        }

        function validateForm() {
            const checkboxes = document.querySelectorAll('input[name="diteruskan_kepada[]"]:checked');
            if (checkboxes.length === 0) {
                alert("Harap pilih minimal satu tujuan disposisi (Diteruskan Kepada).");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>

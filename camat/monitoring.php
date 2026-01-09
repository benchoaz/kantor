<?php
/**
 * Monitoring
 * Pantau status pelaksanaan disposisi
 */

define('APP_INIT', true);

require_once 'config/config.php';
require_once 'includes/api_client.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAuth();

$pageTitle = 'Monitoring Disposisi';

// Filter
$filter = $_GET['filter'] ?? 'all'; // all, pending, proses, selesai, lewat

// Ambil data monitoring dari API
$api = new ApiClient();
$response = $api->get('/pimpinan/monitoring', ['filter' => $filter]);

$monitoringList = [];
if ($response['success'] && isset($response['data'])) {
    $monitoringList = $response['data'];
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Monitoring</h1>
    <p class="page-subtitle">Pantau status disposisi berjalan</p>
</div>

<!-- Filter Pills -->
<div style="display: flex; gap: 8px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 8px; margin-right: -20px; padding-right: 20px;">
    <a href="?filter=all" style="padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all 0.2s; <?php echo $filter === 'all' ? 'background: var(--primary); color: white;' : 'background: white; color: var(--text-muted); box-shadow: 0 2px 4px rgba(0,0,0,0.05);'; ?>">
        Semua
    </a>
    <a href="?filter=pending" style="padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all 0.2s; <?php echo $filter === 'pending' ? 'background: var(--primary); color: white;' : 'background: white; color: var(--text-muted); box-shadow: 0 2px 4px rgba(0,0,0,0.05);'; ?>">
        Pending
    </a>
    <a href="?filter=proses" style="padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all 0.2s; <?php echo $filter === 'proses' ? 'background: var(--primary); color: white;' : 'background: white; color: var(--text-muted); box-shadow: 0 2px 4px rgba(0,0,0,0.05);'; ?>">
        Proses
    </a>
    <a href="?filter=selesai" style="padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all 0.2s; <?php echo $filter === 'selesai' ? 'background: var(--primary); color: white;' : 'background: white; color: var(--text-muted); box-shadow: 0 2px 4px rgba(0,0,0,0.05);'; ?>">
        Selesai
    </a>
    <a href="?filter=lewat" style="padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all 0.2s; <?php echo $filter === 'lewat' ? 'background: #FED7D7; color: #C53030;' : 'background: white; color: var(--text-muted); box-shadow: 0 2px 4px rgba(0,0,0,0.05);'; ?>">
        Lewat Deadline
    </a>
</div>

<?php if (empty($monitoringList)): ?>
    <div class="empty-state" style="text-align: center; padding: 40px;">
        <div style="background: rgba(255,255,255,0.5); padding: 40px; border-radius: 24px; display: inline-block; width: 100%;">
             <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted); margin-bottom: 16px;"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
             <p style="margin: 0; color: var(--text-muted);">Tidak ada data monitoring</p>
        </div>
    </div>
<?php else: ?>
    <!-- Mobile Cards List -->
    <div style="display: grid; gap: 16px;">
        <?php foreach ($monitoringList as $item): 
            $linkId = $item['id_surat'] ?? $item['id'] ?? '';
        ?>
        <a href="modules/surat/detail.php?surat_id=<?php echo urlencode($linkId); ?>" class="action-card" style="align-items: flex-start; text-align: left; padding: 20px; text-decoration: none; display: block; transition: transform 0.2s;">
            <div style="display: flex; justify-content: space-between; align-items: start; width: 100%; margin-bottom: 12px;">
                <h3 style="font-size: 16px; margin: 0; color: var(--text-main); font-weight: 700;"><?php echo e($item['nomor_surat'] ?? '-'); ?></h3>
                <?php echo renderStatusBadge($item['status'] ?? 'pending', $item['deadline'] ?? null); ?>
            </div>
            
            <!-- Three Dots Menu (Absolute Positioned) -->
            <div style="position: absolute; top: 20px; right: 20px; z-index: 10;">
                <div style="position: relative;">
                    <button type="button" onclick="event.preventDefault(); toggleMenu('menu-<?php echo $linkId; ?>')" style="background: white; border: 1px solid #e2e8f0; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                    </button>
                    <div id="menu-<?php echo $linkId; ?>" class="action-menu" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 8px; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 160px; overflow: hidden; border: 1px solid #f0f0f0;">
                         <?php if (($item['status'] ?? 'pending') == 'pending'): ?>
                        <a href="#" 
                           onclick="event.preventDefault(); openEditModal(this)"
                           data-id="<?php echo $linkId; ?>"
                           data-tujuan="<?php echo htmlspecialchars($item['tujuan'] ?? '', ENT_QUOTES); ?>"
                           data-catatan="<?php echo htmlspecialchars($item['catatan'] ?? '', ENT_QUOTES); ?>"
                           data-deadline="<?php echo $item['deadline'] ?? ''; ?>"
                           data-sifat="<?php echo $item['sifat'] ?? 'Biasa'; ?>"
                           style="display: block; padding: 12px 16px; font-size: 13px; color: var(--text-main); text-decoration: none; border-bottom: 1px solid #f7fafc; transition: background 0.2s;" 
                           onmouseover="this.style.background='#f7fafc'" 
                           onmouseout="this.style.background='white'">
                            ✏️ Ralat Disposisi
                        </a>
                        <?php endif; ?>
                        <a href="#" onclick="event.preventDefault(); openCancelModal('<?php echo $linkId; ?>')" style="display: block; padding: 12px 16px; font-size: 13px; color: #E53E3E; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#FFF5F5'" onmouseout="this.style.background='white'">
                            🚫 Batalkan
                        </a>
                    </div>
                </div>
            </div>
            
            <p style="font-size: 14px; margin: 0 0 12px 0; color: var(--text-muted); line-height: 1.5;">
                <?php echo e($item['perihal'] ?? '-'); ?>
            </p>
            
            <div style="background: var(--bg-sage-light); padding: 12px; border-radius: 12px; width: 100%; margin-bottom: 12px;">
                <div style="margin-bottom: 8px; font-size: 12px; color: var(--text-muted);">TUJUAN DISPOSISI</div>
                <div style="font-weight: 600; color: var(--text-main); font-size: 14px;"><?php echo e($item['tujuan'] ?? '-'); ?></div>
            </div>
            
            <div style="display: flex; justify-content: space-between; width: 100%; font-size: 13px;">
                <div style="color: var(--text-muted);">
                    <span style="display: block; font-size: 10px; font-weight: 600; text-transform: uppercase;">Deadline</span>
                    <strong style="color: var(--text-main);"><?php echo formatTanggal($item['deadline'] ?? ''); ?></strong>
                </div>
                
                <?php if (!empty($item['catatan'])): ?>
                <div style="text-align: right; max-width: 60%;">
                    <span style="display: block; font-size: 10px; font-weight: 600; text-transform: uppercase; color: var(--text-muted);">Catatan</span>
                    <span style="color: var(--text-main); font-style: italic;">"<?php echo e(mb_strimwidth($item['catatan'], 0, 30, "...")); ?>"</span>
                </div>
                <?php endif; ?>
            </div>
            <div style="margin-top: 12px; font-size: 10px; color: var(--text-muted); text-align: right;">ID: <?php echo e($linkId); ?></div>
        </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<!-- Modal Cancel -->
<div id="cancelModal" class="modal-overlay hidden" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 50; display: none; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; border-radius: 20px; width: 90%; max-width: 400px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; font-size: 18px; font-weight: 700; color: #1a202c; margin-bottom: 8px;">Batalkan Disposisi</h3>
        <p style="font-size: 14px; color: #718096; margin-bottom: 20px;">Apakah Anda yakin ingin menarik kembali disposisi ini? Tindakan ini akan tercatat dalam riwayat.</p>
        
        <form action="modules/disposisi/manage.php" method="POST">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="id" id="cancel_id">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 8px;">Alasan Pembatalan <span style="color: #e53e3e;">*</span></label>
                <textarea name="alasan" required placeholder="Contoh: Salah tujuan, revisi arahan, dll." style="width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; font-size: 14px; min-height: 80px; outline: none; transition: border 0.2s;"></textarea>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal('cancelModal')" style="flex: 1; padding: 12px; border-radius: 50px; border: 1px solid #e2e8f0; background: white; font-weight: 600; color: #718096; cursor: pointer;">Batal</button>
                <button type="submit" style="flex: 1; padding: 12px; border-radius: 50px; background: #E53E3E; border: none; font-weight: 600; color: white; cursor: pointer; box-shadow: 0 4px 6px rgba(229, 62, 62, 0.2);">Ya, Batalkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit (Simple Rewrite) -->
<div id="editModal" class="modal-overlay hidden" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 50; display: none; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; border-radius: 20px; width: 90%; max-width: 500px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 90vh; overflow-y: auto;">
        <h3 style="margin-top: 0; font-size: 18px; font-weight: 700; color: #1a202c; margin-bottom: 20px;">Ralat Disposisi</h3>
        
        <form action="modules/disposisi/manage.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <!-- To keep it simple, we allow editing Note, Deadline, and Sifat. Target is complex if dynamic checkbox, but we can try simple input or select if feasible. For now let's assume text correction. -->
             <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 8px;">Sifat Disposisi</label>
                <select name="sifat" id="edit_sifat" style="width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; font-size: 14px; outline: none; bg: white;">
                    <option value="Biasa">Biasa</option>
                    <option value="Segera">Segera</option>
                    <option value="Penting">Penting</option>
                    <option value="Rahasia">Rahasia</option>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 8px;">Catatan / Instruksi</label>
                <textarea name="catatan" id="edit_catatan" required style="width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; font-size: 14px; min-height: 100px; outline: none;"></textarea>
            </div>

             <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 8px;">Deadline Baruyang</label>
                <input type="date" name="deadline" id="edit_deadline" required style="width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; font-size: 14px; outline: none;">
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal('editModal')" style="flex: 1; padding: 12px; border-radius: 50px; border: 1px solid #e2e8f0; background: white; font-weight: 600; color: #718096; cursor: pointer;">Batal</button>
                <button type="submit" style="flex: 1; padding: 12px; border-radius: 50px; background: var(--primary); border: none; font-weight: 600; color: white; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleMenu(menuId) {
    const menu = document.getElementById(menuId);
    
    // Close all other menus
    document.querySelectorAll('.action-menu').forEach(el => {
        if (el.id !== menuId) el.style.display = 'none';
    });
    
    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
    }
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-trigger')) {
        document.querySelectorAll('.action-menu').forEach(el => {
            el.style.display = 'none';
        });
    }
});

function openCancelModal(id) {
    document.getElementById('cancel_id').value = id;
    const modal = document.getElementById('cancelModal');
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
}

function openEditModal(el) {
    // Read data from data-attributes (safer for special characters)
    const id = el.getAttribute('data-id');
    const tujuan = el.getAttribute('data-tujuan');
    const catatan = el.getAttribute('data-catatan');
    const deadline = el.getAttribute('data-deadline');
    const sifat = el.getAttribute('data-sifat');

    document.getElementById('edit_id').value = id;
    document.getElementById('edit_catatan').value = catatan;
    document.getElementById('edit_deadline').value = deadline;
    document.getElementById('edit_sifat').value = sifat;
    // Note: Tujuan is not trivially editable in this simple modal due to complexity.
    
    const modal = document.getElementById('editModal');
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    modal.classList.add('hidden');
}
</script>

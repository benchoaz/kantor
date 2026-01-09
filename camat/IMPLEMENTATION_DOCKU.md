# Template Implementasi Docku Application

Lokasi: `/home/beni/projectku/Docku`

## 📁 File yang Harus Dibuat/Diupdate

```
/home/beni/projectku/Docku/
├── modules/disposisi/
│   ├── inbox.php (NEW)
│   ├── detail.php (NEW)
│   └── submit-report.php (NEW)
├── includes/
│   └── header.php (UPDATE - add notification badge)
└── config/
    └── api.php (UPDATE - add Docku endpoints)
```

---

## 1. Inbox Disposisi (Halaman Utama)

**File:** `/home/beni/projectku/Docku/modules/disposisi/inbox.php`

```php
<?php
/**
 * Inbox Disposisi - Docku
 * Menampilkan disposisi yang dikirim oleh Camat ke user yang sedang login
 */

define('APP_INIT', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/api_client.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();

$pageTitle = 'Inbox Disposisi';

// Get current user ID from session
$currentUser = getCurrentUser(); // Sesuaikan dengan function auth Docku
$userId = $currentUser['id'] ?? null;

if (!$userId) {
    setFlashMessage('error', 'User ID tidak ditemukan');
    redirect('dashboard.php');
}

// Fetch dispositions from API
$api = new ApiClient();
$response = $api->get('/docku/disposisi/' . $userId);

$dispositions = [];
if ($response['success'] && isset($response['data'])) {
    $dispositions = $response['data'];
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Inbox Disposisi</h1>
    <p class="page-subtitle">Instruksi dari Camat yang perlu ditindaklanjuti</p>
</div>

<?php if (empty($dispositions)): ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 48px 24px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 16px; color: var(--text-muted); opacity: 0.5;">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
            </svg>
            <h3 style="color: var(--text-muted); font-size: 16px; margin-bottom: 8px;">Inbox Kosong</h3>
            <p style="color: var(--text-muted); font-size: 14px;">Tidak ada disposisi yang perlu ditindaklanjuti saat ini</p>
        </div>
    </div>
<?php else: ?>
    <div class="section">
        <?php foreach ($dispositions as $dispo): 
            $id = $dispo['id'] ?? '-';
            $suratId = $dispo['surat_id'] ?? null;
            $nomorSurat = $dispo['nomor_surat'] ?? '-';
            $perihal = $dispo['perihal'] ?? '-';
            $catatan = $dispo['catatan'] ?? '';
            $deadline = $dispo['deadline'] ?? null;
            $sifat = $dispo['sifat_disposisi'] ?? 'Biasa';
            $status = $dispo['status_followup'] ?? 'pending';
            $createdAt = $dispo['created_at'] ?? null;
            
            // Status badge
            $statusBadge = '';
            if ($status === 'pending') {
                $statusBadge = '<span class="badge-warning">Menunggu</span>';
            } elseif ($status === 'in_progress') {
                $statusBadge = '<span class="badge-info">Sedang Dikerjakan</span>';
            }
            
            // Sifat badge
            $sifatClass = 'badge-default';
            if ($sifat === 'Segera') $sifatClass = 'badge-critical';
            elseif ($sifat === 'Penting') $sifatClass = 'badge-warning';
        ?>
        <div class="card" style="margin-bottom: 16px;">
            <!-- Header -->
            <div style="padding: 20px 24px; border-bottom: 1px solid #f0f0f0;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">Disposisi #<?php echo e($id); ?></span>
                            <?php echo $statusBadge; ?>
                            <span class="<?php echo $sifatClass; ?>"><?php echo e($sifat); ?></span>
                        </div>
                        <h3 style="font-size: 16px; font-weight: 600; color: var(--text-main); margin-bottom: 4px;">
                            <?php echo e($nomorSurat); ?>
                        </h3>
                        <p style="font-size: 14px; color: var(--text-muted); margin: 0;">
                            <?php echo e($perihal); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <?php if ($deadline): ?>
                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 2px;">Deadline</div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--color-critical);">
                            <?php echo formatTanggal($deadline); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Body: Instruksi -->
            <div style="padding: 16px 24px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 8px;">Instruksi dari Camat</div>
                <div style="background: var(--soft-gray); padding: 16px; border-radius: 12px; margin-bottom: 16px;">
                    <p style="font-size: 14px; color: var(--text-main); line-height: 1.6; margin: 0; white-space: pre-wrap;">
                        <?php echo nl2br(e($catatan)); ?>
                    </p>
                </div>
                
                <!-- Actions -->
                <div style="display: flex; gap: 12px;">
                    <a href="modules/disposisi/submit-report.php?id=<?php echo e($id); ?>" class="btn btn-primary" style="flex: 1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        Kirim Laporan
                    </a>
                    
                    <?php if ($status === 'pending'): ?>
                    <a href="modules/disposisi/mark-progress.php?id=<?php echo e($id); ?>" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Mulai Mengerjakan
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.badge-warning {
    display: inline-block;
    background: #F59E0B;
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-info {
    display: inline-block;
    background: #3B82F6;
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-critical {
    display: inline-block;
    background: #EF4444;
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-default {
    display: inline-block;
    background: #6B7280;
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
```

---

## 2. Form Submit Laporan

**File:** `/home/beni/projectku/Docku/modules/disposisi/submit-report.php`

```php
<?php
/**
 * Submit Laporan Disposisi - Docku
 * Form untuk mengirim laporan penyelesaian disposisi ke Camat
 */

define('APP_INIT', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/api_client.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();

$pageTitle = 'Kirim Laporan Disposisi';

$disposisiId = $_GET['id'] ?? null;

if (!$disposisiId) {
    setFlashMessage('error', 'ID disposisi tidak valid');
    redirect('modules/disposisi/inbox.php');
}

$api = new ApiClient();

// Get current user
$currentUser = getCurrentUser();
$userId = $currentUser['id'] ?? null;

// Get disposition details
$disposisiResponse = $api->get('/docku/disposisi/' . $userId);
$disposisi = null;

if ($disposisiResponse['success'] && isset($disposisiResponse['data'])) {
    foreach ($disposisiResponse['data'] as $item) {
        if ($item['id'] == $disposisiId) {
            $disposisi = $item;
            break;
        }
    }
}

if (!$disposisi) {
    setFlashMessage('error', 'Disposisi tidak ditemukan');
    redirect('modules/disposisi/inbox.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catatan = sanitize($_POST['catatan'] ?? '');
    $fileUrl = '';
    
    // Handle file upload
    if (isset($_FILES['laporan_file']) && $_FILES['laporan_file']['error'] === UPLOAD_ERR_OK) {
        // Upload file to server
        $uploadDir = __DIR__ . '/../../uploads/laporan/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['laporan_file']['name']);
        $uploadPath = $uploadDir . $fileName;
        
        // Validate PDF only
        $fileType = $_FILES['laporan_file']['type'];
        if ($fileType !== 'application/pdf') {
            setFlashMessage('error', 'Hanya file PDF yang diperbolehkan');
        } else {
            if (move_uploaded_file($_FILES['laporan_file']['tmp_name'], $uploadPath)) {
                // Generate URL (sesuaikan dengan base URL Docku)
                $fileUrl = BASE_URL . '/uploads/laporan/' . $fileName;
            } else {
                setFlashMessage('error', 'Gagal mengupload file');
            }
        }
    }
    
    // Validate
    if (empty($catatan) && empty($fileUrl)) {
        setFlashMessage('error', 'Catatan atau file laporan wajib diisi');
    } else {
        // Send to API
        $payload = [
            'laporan_catatan' => $catatan,
            'laporan_file_url' => $fileUrl
        ];
        
        $response = $api->post('/docku/disposisi/' . $disposisiId . '/laporan', $payload);
        
        if ($response['success']) {
            setFlashMessage('success', 'Laporan berhasil dikirim ke Camat!');
            redirect('modules/disposisi/inbox.php');
        } else {
            setFlashMessage('error', $response['message'] ?? 'Gagal mengirim laporan');
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Kirim Laporan</h1>
    <p class="page-subtitle">Laporan penyelesaian disposisi untuk Camat</p>
</div>

<!-- Info Disposisi -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body">
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 12px;">Disposisi #<?php echo e($disposisi['id']); ?></div>
        
        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">
            <?php echo e($disposisi['nomor_surat'] ?? '-'); ?>
        </h3>
        
        <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 16px;">
            <?php echo e($disposisi['perihal'] ?? '-'); ?>
        </p>
        
        <div style="background: var(--soft-gray); padding: 16px; border-radius: 12px;">
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 8px;">Instruksi dari Camat</div>
            <p style="font-size: 14px; line-height: 1.6; margin: 0; white-space: pre-wrap;">
                <?php echo nl2br(e($disposisi['catatan'] ?? '')); ?>
            </p>
        </div>
    </div>
</div>

<!-- Form Laporan -->
<form method="POST" enctype="multipart/form-data" class="card">
    <div class="card-body">
        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 24px; color: var(--primary);">Laporan Penyelesaian</h3>
        
        <div style="margin-bottom: 20px;">
            <label class="form-label-premium" for="catatan">Catatan Laporan *</label>
            <textarea 
                name="catatan" 
                id="catatan" 
                class="form-control-pill" 
                placeholder="Tuliskan hasil tindak lanjut disposisi..."
                required
                style="min-height: 150px; resize: vertical; border-radius: 20px;"
            ><?php echo e($_POST['catatan'] ?? ''); ?></textarea>
            <small style="font-size: 12px; color: var(--text-muted); margin-top: 6px; display: block;">
                Jelaskan apa yang sudah dikerjakan dan hasil yang dicapai
            </small>
        </div>
        
        <div style="margin-bottom: 24px;">
            <label class="form-label-premium" for="laporan_file">Upload File Laporan (PDF)</label>
            <input 
                type="file" 
                name="laporan_file" 
                id="laporan_file" 
                class="form-control-pill"
                accept=".pdf"
                style="padding: 12px 20px;"
            >
            <small style="font-size: 12px; color: var(--text-muted); margin-top: 6px; display: block;">
                Format: PDF, Ukuran maksimal: 5MB (Opsional jika catatan sudah lengkap)
            </small>
        </div>
        
        <div style="display: flex; gap: 12px;">
            <a href="modules/disposisi/inbox.php" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                    <path d="M19 12H5"></path>
                    <path d="M12 19l-7-7 7-7"></path>
                </svg>
                Batal
            </a>
            
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
                Kirim Laporan ke Camat
            </button>
        </div>
    </div>
</form>

<style>
.form-control-pill {
    display: block;
    width: 100%;
    padding: 12px 20px;
    background-color: #ffffff;
    border: 1px solid #cbd5e0;
    border-radius: 50px;
    font-size: 15px;
    color: #2D3748;
    line-height: 1.5;
    outline: none;
    transition: all 0.2s;
}

.form-control-pill:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(122, 155, 142, 0.15);
}

.form-label-premium {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    margin-left: 12px;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
```

---

## 3. Update Header untuk Notification Badge

**File:** `/home/beni/projectku/Docku/includes/header.php`

Tambahkan code berikut di bagian navigation/header:

```php
<?php
// Get unread disposition count for notification badge
$currentUser = getCurrentUser();
$userId = $currentUser['id'] ?? null;
$unreadCount = 0;

if ($userId) {
    $api = new ApiClient();
    $countResponse = $api->get('/docku/disposisi/' . $userId . '/unread-count');
    if ($countResponse['success'] && isset($countResponse['data']['count'])) {
        $unreadCount = (int) $countResponse['data']['count'];
    }
}
?>

<!-- Navigation (sesuaikan dengan struktur Docku) -->
<nav>
    <!-- ... menu lain ... -->
    
    <a href="modules/disposisi/inbox.php" class="nav-item">
        <span>Disposisi</span>
        <?php if ($unreadCount > 0): ?>
        <span class="notification-badge"><?php echo $unreadCount; ?></span>
        <?php endif; ?>
    </a>
</nav>

<style>
.notification-badge {
    display: inline-block;
    background: #EF4444;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    margin-left: 6px;
    min-width: 20px;
    text-align: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}
</style>
```

---

## 4. Update API Configuration

**File:** `/home/beni/projectku/Docku/config/api.php`

Tambahkan endpoint constants:

```php
<?php
// ... existing config ...

// Docku-specific endpoints
if (!defined('ENDPOINT_DISPOSISI_INBOX')) {
    define('ENDPOINT_DISPOSISI_INBOX', '/docku/disposisi');
}

if (!defined('ENDPOINT_DISPOSISI_UNREAD_COUNT')) {
    define('ENDPOINT_DISPOSISI_UNREAD_COUNT', '/docku/disposisi/{userId}/unread-count');
}

if (!defined('ENDPOINT_DISPOSISI_SUBMIT_REPORT')) {
    define('ENDPOINT_DISPOSISI_SUBMIT_REPORT', '/docku/disposisi/{id}/laporan');
}
```

---

## 5. Helper Function untuk Unread Count

**File:** `/home/beni/projectku/Docku/includes/functions.php`

Tambahkan function helper:

```php
<?php

/**
 * Get unread disposition count for current user
 * 
 * @return int
 */
function getUnreadDispositionCount() {
    $currentUser = getCurrentUser();
    $userId = $currentUser['id'] ?? null;
    
    if (!$userId) {
        return 0;
    }
    
    $api = new ApiClient();
    $response = $api->get('/docku/disposisi/' . $userId . '/unread-count');
    
    if ($response['success'] && isset($response['data']['count'])) {
        return (int) $response['data']['count'];
    }
    
    return 0;
}
```

---

## Testing Checklist

### Test di Docku

1. **Login sebagai user yang mendapat disposisi**
   ```
   - User: Kasi Pemerintahan (atau user lain dari sync)
   - Password: (sesuai database)
   ```

2. **Buka Inbox Disposisi**
   ```
   URL: http://localhost/Docku/modules/disposisi/inbox.php
   Expected: Muncul list disposisi (jika ada)
   ```

3. **Check Notification Badge**
   ```
   Expected: Badge merah dengan angka muncul di menu Disposisi
   ```

4. **Submit Laporan**
   ```
   - Klik "Kirim Laporan"
   - Isi catatan
   - Upload PDF (optional)
   - Klik "Kirim Laporan ke Camat"
   Expected: Success message, redirect ke inbox
   ```

5. **Verify di Camat**
   ```
   - Login ke Camat
   - Buka menu "LAPORAN"
   - Expected: Laporan muncul dengan file dan catatan
   ```

---

## Integration Flow Diagram

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│   CAMAT     │         │     API     │         │    DOCKU    │
└──────┬──────┘         └──────┬──────┘         └──────┬──────┘
       │                       │                       │
       │  1. Kirim Disposisi   │                       │
       │──────────────────────>│                       │
       │   (target_user_id)    │                       │
       │                       │                       │
       │                       │  2. Notifikasi        │
       │                       │──────────────────────>│
       │                       │   (badge update)      │
       │                       │                       │
       │                       │                       │
       │                       │  3. User buka inbox   │
       │                       │<──────────────────────│
       │                       │   GET /docku/dispo    │
       │                       │                       │
       │                       │  4. Tampilkan list    │
       │                       │──────────────────────>│
       │                       │                       │
       │                       │                       │
       │                       │  5. Submit laporan    │
       │                       │<──────────────────────│
       │                       │   POST /laporan       │
       │                       │                       │
       │  6. Notif laporan     │                       │
       │<──────────────────────│                       │
       │   (status: completed) │                       │
       │                       │                       │
```

---

## Next Steps

1. Copy semua file di atas ke `/home/beni/projectku/Docku`
2. Sesuaikan path dan function sesuai struktur Docku yang ada
3. Test end-to-end flow
4. Deploy ke production


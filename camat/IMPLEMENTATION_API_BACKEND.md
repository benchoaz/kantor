# Template Implementasi API Backend & Docku

Karena API backend ada di `/home/beni/projectku/api-docksurat` (di luar workspace), berikut adalah template file yang perlu dibuat.

## 📁 Struktur File yang Harus Dibuat

```
/home/beni/projectku/api-docksurat/
├── database/migrations/
│   └── xxxx_add_disposition_tracking.sql
├── app/Controllers/
│   └── DisposisiController.php (UPDATE)
└── routes/
    └── api.php (UPDATE)

/home/beni/projectku/docku/ (lokasi belum diketahui)
├── modules/disposisi/
│   ├── inbox.php (NEW)
│   └── submit-report.php (NEW)
└── includes/
    └── header.php (UPDATE - add notification badge)
```

---

## 1. Database Migration

**File:** `/home/beni/projectku/api-docksurat/database/migrations/xxxx_add_disposition_tracking.sql`

```sql
-- Add tracking fields to disposisi table
ALTER TABLE disposisi 
ADD COLUMN IF NOT EXISTS target_user_id INT NULL,
ADD COLUMN IF NOT EXISTS status_followup ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS laporan_file_url TEXT NULL,
ADD COLUMN IF NOT EXISTS laporan_catatan TEXT NULL,
ADD COLUMN IF NOT EXISTS notified_at TIMESTAMP NULL;

-- Create indexes for faster queries
CREATE INDEX IF NOT EXISTS idx_target_user ON disposisi(target_user_id);
CREATE INDEX IF NOT EXISTS idx_status_followup ON disposisi(status_followup);

-- Optional: Add foreign key if users table exists
-- ALTER TABLE disposisi 
-- ADD CONSTRAINT fk_target_user 
-- FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL;
```

**Cara Menjalankan:**
```bash
cd /home/beni/projectku/api-docksurat
mysql -u root -p your_database < database/migrations/xxxx_add_disposition_tracking.sql
```

---

## 2. Update DisposisiController

**File:** `/home/beni/projectku/api-docksurat/app/Controllers/DisposisiController.php`

Tambahkan method berikut:

```php
<?php

namespace App\Controllers;

use App\Models\Disposisi;
use App\Models\User;
use Exception;

class DisposisiController extends BaseController
{
    /**
     * CREATE DISPOSITION (Updated)
     * Endpoint: POST /api/v1/pimpinan/disposisi
     * 
     * Now includes target_user_id for notification system
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Validate required fields
            $required = ['surat_id', 'target_user_id', 'catatan', 'deadline'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->respondError("Field {$field} wajib diisi", 400);
                }
            }
            
            // Validate target_user_id exists
            $userModel = new User();
            $targetUser = $userModel->find($data['target_user_id']);
            if (!$targetUser) {
                return $this->respondError("User target tidak ditemukan", 404);
            }
            
            // Create disposition
            $disposisiModel = new Disposisi();
            $insertData = [
                'surat_id' => $data['surat_id'],
                'target_user_id' => $data['target_user_id'],
                'tujuan' => $data['tujuan'] ?? $targetUser['nama'], // Fallback to user name
                'catatan' => $data['catatan'],
                'deadline' => $data['deadline'],
                'sifat_disposisi' => $data['sifat_disposisi'] ?? 'Biasa',
                'status_followup' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $disposisiId = $disposisiModel->insert($insertData);
            
            if ($disposisiId) {
                // TODO: Send notification to Docku (optional - webhook/queue)
                // $this->sendNotificationToDocku($disposisiId, $data['target_user_id']);
                
                return $this->respondSuccess([
                    'id' => $disposisiId,
                    'message' => 'Disposisi berhasil dikirim'
                ], 201);
            }
            
            return $this->respondError('Gagal membuat disposisi', 500);
            
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }
    
    /**
     * GET DISPOSITIONS FOR SPECIFIC USER (Docku Side)
     * Endpoint: GET /api/v1/docku/disposisi/{userId}
     * 
     * Returns dispositions assigned to specific user
     */
    public function getDispositionsForUser($userId)
    {
        try {
            $disposisiModel = new Disposisi();
            
            // Only get pending and in_progress for the target user
            $dispositions = $disposisiModel
                ->where('target_user_id', $userId)
                ->whereIn('status_followup', ['pending', 'in_progress'])
                ->orderBy('created_at', 'DESC')
                ->findAll();
            
            // Mark as notified
            $disposisiModel
                ->where('target_user_id', $userId)
                ->where('notified_at IS NULL')
                ->update(['notified_at' => date('Y-m-d H:i:s')]);
            
            return $this->respondSuccess($dispositions);
            
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }
    
    /**
     * GET UNREAD COUNT FOR USER (Docku Notification Badge)
     * Endpoint: GET /api/v1/docku/disposisi/{userId}/unread-count
     */
    public function getUnreadCount($userId)
    {
        try {
            $disposisiModel = new Disposisi();
            
            $count = $disposisiModel
                ->where('target_user_id', $userId)
                ->where('status_followup', 'pending')
                ->countAllResults();
            
            return $this->respondSuccess(['count' => $count]);
            
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }
    
    /**
     * SUBMIT REPORT (Docku to Camat)
     * Endpoint: POST /api/v1/docku/disposisi/{id}/laporan
     * 
     * Staff submits completion report
     */
    public function submitReport($disposisiId)
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Get disposition
            $disposisiModel = new Disposisi();
            $disposisi = $disposisiModel->find($disposisiId);
            
            if (!$disposisi) {
                return $this->respondError('Disposisi tidak ditemukan', 404);
            }
            
            // Validate file URL if provided
            if (!empty($data['laporan_file_url'])) {
                // Optional: validate file exists
            }
            
            // Update disposition
            $updateData = [
                'status_followup' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'laporan_catatan' => $data['laporan_catatan'] ?? '',
                'laporan_file_url' => $data['laporan_file_url'] ?? ''
            ];
            
            $updated = $disposisiModel->update($disposisiId, $updateData);
            
            if ($updated) {
                // TODO: Send notification to Camat (optional)
                // $this->sendNotificationToCamat($disposisiId);
                
                return $this->respondSuccess([
                    'message' => 'Laporan berhasil dikirim',
                    'disposisi_id' => $disposisiId
                ]);
            }
            
            return $this->respondError('Gagal mengirim laporan', 500);
            
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }
    
    /**
     * GET COMPLETED REPORTS (Camat Side)
     * Endpoint: GET /api/v1/pimpinan/laporan-disposisi
     * 
     * Returns all completed dispositions with reports
     */
    public function getReports()
    {
        try {
            $disposisiModel = new Disposisi();
            
            // Get completed dispositions with JOIN to get user and surat info
            $builder = $disposisiModel
                ->select('disposisi.*, users.nama as tujuan_nama, users.jabatan, surat.nomor_surat, surat.perihal')
                ->join('users', 'users.id = disposisi.target_user_id', 'left')
                ->join('surat', 'surat.id = disposisi.surat_id', 'left')
                ->where('disposisi.status_followup', 'completed')
                ->orderBy('disposisi.completed_at', 'DESC');
            
            $reports = $builder->findAll();
            
            return $this->respondSuccess($reports);
            
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }
    
    /**
     * Helper: Send notification to Docku (Optional)
     */
    private function sendNotificationToDocku($disposisiId, $userId)
    {
        // TODO: Implement webhook or push notification
        // Example: POST to Docku webhook endpoint
        // Or: Add to queue for email notification
    }
    
    /**
     * Helper: Respond with success
     */
    protected function respondSuccess($data, $code = 200)
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $data
        ])->setStatusCode($code);
    }
    
    /**
     * Helper: Respond with error
     */
    protected function respondError($message, $code = 400)
    {
        return $this->response->setJSON([
            'success' => false,
            'message' => $message
        ])->setStatusCode($code);
    }
}
```

---

## 3. Update API Routes

**File:** `/home/beni/projectku/api-docksurat/routes/api.php`

Tambahkan routes berikut:

```php
<?php

// Existing routes...

// DOCKU ENDPOINTS (untuk staff di Docku)
$routes->group('docku', ['namespace' => 'App\Controllers'], function($routes) {
    // Get dispositions for specific user
    $routes->get('disposisi/(:num)', 'DisposisiController::getDispositionsForUser/$1');
    
    // Get unread count for badge
    $routes->get('disposisi/(:num)/unread-count', 'DisposisiController::getUnreadCount/$1');
    
    // Submit report
    $routes->post('disposisi/(:num)/laporan', 'DisposisiController::submitReport/$1');
});

// CAMAT/PIMPINAN ENDPOINTS
$routes->group('pimpinan', ['namespace' => 'App\Controllers'], function($routes) {
    // ... existing routes ...
    
    // Get completed reports
    $routes->get('laporan-disposisi', 'DisposisiController::getReports');
});
```

---

## 4. API Endpoint Documentation

### For Docku Application

#### Get Dispositions for User
```
GET /api/v1/docku/disposisi/{userId}
Headers:
  X-API-KEY: sk_live_docku_x9y8z7w6v5u4t3s2
  
Response:
{
  "success": true,
  "data": [
    {
      "id": 123,
      "surat_id": 45,
      "nomor_surat": "001/2026",
      "perihal": "Undangan Rapat",
      "catatan": "Harap dihadiri dan buat notulen",
      "deadline": "2026-01-10",
      "sifat_disposisi": "Segera",
      "status_followup": "pending",
      "created_at": "2026-01-06 10:00:00"
    }
  ]
}
```

#### Get Unread Count
```
GET /api/v1/docku/disposisi/{userId}/unread-count
Headers:
  X-API-KEY: sk_live_docku_x9y8z7w6v5u4t3s2
  
Response:
{
  "success": true,
  "data": {
    "count": 5
  }
}
```

#### Submit Report
```
POST /api/v1/docku/disposisi/{id}/laporan
Headers:
  X-API-KEY: sk_live_docku_x9y8z7w6v5u4t3s2
  Content-Type: application/json

Body:
{
  "laporan_catatan": "Rapat telah dilaksanakan, notulen terlampir",
  "laporan_file_url": "https://api.sidiksae.my.id/uploads/laporan_123.pdf"
}

Response:
{
  "success": true,
  "data": {
    "message": "Laporan berhasil dikirim",
    "disposisi_id": 123
  }
}
```

### For Camat Application

#### Get Reports
```
GET /api/v1/pimpinan/laporan-disposisi
Headers:
  X-API-KEY: sk_live_camat_c4m4t2026
  Authorization: Bearer {token}
  
Response:
{
  "success": true,
  "data": [
    {
      "id": 123,
      "surat_id": 45,
      "nomor_surat": "001/2026",
      "perihal": "Undangan Rapat",
      "tujuan_nama": "Budi Santoso",
      "jabatan": "Kasi Pemerintahan",
      "status_followup": "completed",
      "completed_at": "2026-01-08 14:30:00",
      "laporan_catatan": "Rapat telah dilaksanakan",
      "laporan_file_url": "https://..."
    }
  ]
}
```

---

## Testing Commands

### Test API Endpoints

```bash
# 1. Test create disposition (from Camat)
curl -X POST https://api.sidiksae.my.id/api/v1/pimpinan/disposisi \
  -H "X-API-KEY: sk_live_camat_c4m4t2026" \
  -H "Content-Type: application/json" \
  -d '{
    "surat_id": 45,
    "target_user_id": 10,
    "tujuan": "Kasi Pemerintahan",
    "catatan": "Harap ditindaklanjuti segera",
    "deadline": "2026-01-10",
    "sifat_disposisi": "Segera"
  }'

# 2. Test get dispositions for user (from Docku)
curl -X GET "https://api.sidiksae.my.id/api/v1/docku/disposisi/10" \
  -H "X-API-KEY: sk_live_docku_x9y8z7w6v5u4t3s2"

# 3. Test submit report (from Docku)
curl -X POST https://api.sidiksae.my.id/api/v1/docku/disposisi/123/laporan \
  -H "X-API-KEY: sk_live_docku_x9y8z7w6v5u4t3s2" \
  -H "Content-Type: application/json" \
  -d '{
    "laporan_catatan": "Sudah selesai",
    "laporan_file_url": "https://..."
  }'

# 4. Test get reports (from Camat)
curl -X GET "https://api.sidiksae.my.id/api/v1/pimpinan/laporan-disposisi" \
  -H "X-API-KEY: sk_live_camat_c4m4t2026"
```

---

## Next Steps

1. **Copy files ke lokasi yang sesuai:**
   - Database migration ke `/home/beni/projectku/api-docksurat/database/migrations/`
   - Controller updates ke `/home/beni/projectku/api-docksurat/app/Controllers/`
   - Route updates ke `/home/beni/projectku/api-docksurat/routes/`

2. **Jalankan migration:**
   ```bash
   cd /home/beni/projectku/api-docksurat
   php spark migrate  # atau manual via mysql
   ```

3. **Restart API server** (jika diperlukan)

4. **Test endpoints** menggunakan curl atau Postman

5. **Implementasi Docku** (lihat file berikutnya untuk template Docku)

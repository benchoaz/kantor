# Debug Guide - Multipart Request Issue

## Problem
Masih dapat error "uuid_surat is required" padahal payload jelas mengirim uuid_surat.

## Debugging Steps

### Step 1: Upload Debug Script

Upload `api/debug_disposisi_request.php` ke server

### Step 2: Temporary Route Test

Tambahkan route temporary di `api/index.php` (line ~33):

```php
// DEBUG ROUTE - REMOVE AFTER TESTING
$router->add('POST', '/api/disposisi/debug', function() {
    require_once __DIR__ . '/debug_disposisi_request.php';
});
```

### Step 3: Test Endpoint

Ubah SuratQu untuk kirim ke `/api/disposisi/debug` (temporary):

```php
// File: suratqu/includes/sidiksae_api_client.php
// Line ~84
$url = rtrim($this->config['base_url'], '/') . '/disposisi/debug'; // TEMPORARY
```

### Step 4: Check Log

Kirim disposisi dari SuratQu, lalu check:
```bash
cat /home/username/public_html/api/debug_request.log
```

**Yang perlu dicek**:
- `content_type` - harus `multipart/form-data`
- `post_data` - harus ada `uuid_surat`
- `files` - harus ada `scan_surat`

### Step 5: Alternative Fix

Kalau `$_POST` kosong tapi `php://input` ada data, berarti:

**Content-Type salah!** SuratQu kirim sebagai **application/json** bukan **multipart/form-data**.

**Quick Fix**: Update SuratQu API client:

```php
// File: suratqu/includes/sidiksae_api_client.php
// Method makeRequest() - Force multipart when file exists

if ($is_multipart) {
    // REMOVE Content-Type header - let cURL auto-set it
    // Don't set: 'Content-Type: application/json'
}
```

### Step 6: Nuclear Option - Accept Both

Update DisposisiController untuk support **BOTH** JSON dan multipart:

```php
// Parse JSON from php://input first (backward compat)
$input = file_get_contents("php://input");
$data = $input ? json_decode($input, true) : [];

// Override with $_POST if multipart (new behavior)
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        $data[$key] = $value;
    }
}

// Decode JSON strings if needed
if (isset($data['penerima']) && is_string($data['penerima'])) {
    $data['penerima'] = json_decode($data['penerima'], true);
}
```

## Most Likely Issue

SuratQu's `makeRequest()` is sending **JSON** even when file exists, instead of **multipart/form-data**.

Check: `suratqu/includes/sidiksae_api_client.php` method `makeRequest()` and ensure it uses proper multipart encoding when `$is_multipart = true`.

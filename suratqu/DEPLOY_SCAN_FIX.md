# Deployment Instructions - Scan Push Fix (2026-01-04)

This package contains the fix for scanned document transmission to the SidikSae API and the new manual retry mechanism.

## Files Included

1.  `includes/integrasi_sistem_handler.php` - Updated to use `CURLFile` for multipart uploads and included `id_surat` in payload.
2.  `includes/sidiksae_api_client.php` - Updated API client for multipart compatibility.
3.  `surat_masuk_detail.php` - Added "Retry Push" button in the UI.
4.  `retry_push.php` - New script for manual API synchronization.

## How to Deploy

1.  **Backup**: Ensure you have backups of the modified files.
2.  **Extract**: Extract the archive to your project root:
    ```bash
    tar -xzvf deploy_scan_push_fix_20260104.tar.gz
    ```
3.  **Permissions**: Ensure `retry_push.php` has the correct web server permissions (usually `644` or similar).

## Verification

1.  Go to **Surat Masuk** > **Detail** on any existing letter.
2.  In the **Riwayat Disposisi** section, you should see a small sync/rotate icon next to the status badge.
3.  Click the icon to manually test the push to the SidikSae API.
4.  Verify that the status changes to "Diterima API" or similar upon success.

> [!NOTE]
> This update switches from Base64 encoding to real file uploads. This is standard and more efficient for modern APIs.

# Deployment Instructions - Camat App Update (Disposisi Fix)

## Version: 3.2
## Date: 2026-01-05

### Description
This update includes Dynamic Recipient Management synchronization, alignment of App and API IDs (using SuratQu ID as primary), and several UI/UX refinements.

### Changes
1. **Modules/Surat/Detail.php**:
   - Added `Dasar Surat` value summary block.
   - Renamed `Catatan` to `Instruksi Pimpinan`.
   - Added hidden fields to form to carry context.

2. **Modules/Disposisi/Process.php**:
   - Updated payload to include `nomor_surat`, `asal_surat`, `scan_surat`, etc.

3. **Config/Api.php**:
   - Reduced Timeout from 30s to 10s to improve perceived performance on slow connections.

### Installation
1. Upload `camat_update_v3.2.tar.gz` to your `public_html` or web root.
2. Extract the file, overwriting existing files.
   ```bash
   tar -xzvf camat_update_v3.2.tar.gz
   ```
3. Ensure directories `modules/`, `config/`, and `helpers/` are updated.

### Verify
- Open a Letter Detail.
- You should see the "Dasar Surat" block.
- Try sending a disposition; it should succeed and redirect back to Inbox.

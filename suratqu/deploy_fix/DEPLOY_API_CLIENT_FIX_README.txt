DEPLOYMENT INSTRUCTIONS - API CLIENT FIX
========================================
Date: 2026-01-04
Files: includes/sidiksae_api_client.php

DESCRIPTION
-----------
This update fixes the API Client authentication logic. The client was previously checking for
'status': 'success' (old format), but the API now returns 'success': true. This caused
valid authentications to be rejected by the application.

INSTRUCTIONS
------------
1. Upload this package to your server (e.g., via SCP or FTP).
2. Extract the package in your SuratQu root directory:
   tar -xzvf deploy_api_client_fix_20260104.tar.gz
   
   OR manually replace the file:
   /path/to/suratqu/includes/sidiksae_api_client.php

3. Verify permissions (optional but recommended):
   chmod 644 includes/sidiksae_api_client.php

VERIFICATION
------------
Try sending a disposition from SuratQu. It should now successfully connect to the SidikSae API.

<?php
/**
 * Fix App Secret (V13)
 * Location: id/admin/fix_app_secret.php
 * 
 * Upserts 'admin_portal' with valid secret hash.
 */

header('Content-Type: text/plain');
echo "--- FIX APP SECRET (V13) ---\n\n";

// CREDENTIALS DARI USER (Step 1333)
$host = 'localhost';
$dbname = 'sidiksae_id';
$user = 'sidiksae_user';
$pass = 'Belajaran123';

echo "[1] Koneksi DB ($dbname)...\n";
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Berhasil.\n";

    $appId = 'admin_portal';
    $rawKey = 'admin_portal_secret_key_2026';
    $appName = 'Identity Admin Portal';
    $scopes = json_encode(["user.profile", "auth.verify", "admin.manage"]);
    
    // Hash the key
    $hash = password_hash($rawKey, PASSWORD_BCRYPT);
    echo "Hash Generated: " . substr($hash, 0, 10) . "...\n";

    // Upsert Logic
    $stmt = $pdo->prepare("SELECT count(*) FROM authorized_apps WHERE app_id = ?");
    $stmt->execute([$appId]);
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        echo "[2] Updating existing app '$appId'...\n";
        $up = $pdo->prepare("UPDATE authorized_apps SET api_secret_hash = ?, scopes = ? WHERE app_id = ?");
        $up->execute([$hash, $scopes, $appId]);
        echo "✅ UPDATE SUKSES.\n";
    } else {
        echo "[2] Inserting new app '$appId'...\n";
        $ins = $pdo->prepare("INSERT INTO authorized_apps (app_id, api_secret_hash, app_name, scopes, created_at) VALUES (?, ?, ?, ?, NOW())");
        $ins->execute([$appId, $hash, $appName, $scopes]);
        echo "✅ INSERT SUKSES.\n";
    }

    echo "\n🚀 App Secret Updated. Login should work now!\n";

} catch (PDOException $e) {
    echo "❌ ERROR DB: " . $e->getMessage() . "\n";
}
?>

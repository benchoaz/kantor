<?php
// docku/fix_uuids_manual.php
// Manual UUID Fix Script for Docku Local
// Based on User Screenshot 2026-01-11

require_once 'config/database.php';
header('Content-Type: text/plain');

echo "=== 🆔 MANUAL UUID MAPPING (Based on Screenshot) ===\n\n";

if (!isset($pdo)) die("No DB Connection.");

// Data from Screenshot 2
// Username => Real UUID from API DB
$mappings = [
    'sekcam'        => '0f721153-ec89-11f0-9d22-ea2d3165cda0',
    'pakcamat'      => '0f720f54-ec89-11f0-9d22-ea2d3165cda0',
    'kasi_pem'      => '0f7212b1-ec89-11f0-9d22-ea2d3165cda0',
    'kasi_ebang'    => '0f72119b-ec89-11f0-9d22-ea2d3165cda0',
    // Assuming pattern for others if needed, but these are visible.
    // If Kasi Pemerintahan (kasi_pem) is critical, we have it.
    // What about others? The screenshot only shows these.
    // We will update these critical ones first.
];

try {
    $stmt = $pdo->prepare("UPDATE users SET uuid = :uuid WHERE username = :user");
    
    foreach ($mappings as $user => $uuid) {
        // Also ensure NO other user has this UUID to avoid constraint error
        $pdo->exec("UPDATE users SET uuid = NULL WHERE uuid = '$uuid' AND username != '$user'");
        
        $stmt->execute([':uuid' => $uuid, ':user' => $user]);
        echo "✅ Updated [$user] -> $uuid (Rows: " . $stmt->rowCount() . ")\n";
    }
    
    echo "\nNOTE: Only users visible in screenshot have been synced.";
    echo "\nIf other users need sync, please provide their UUIDs from API DB.";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

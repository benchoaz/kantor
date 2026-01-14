<?php
/**
 * SYNC SURAT FROM SURATQU TO API
 * One-time script to migrate existing surat
 */

// API Database
require_once __DIR__ . '/config/database.php';
$dbApi = new Database();
$connApi = $dbApi->getConnection();

// SuratQu Database
try {
    $connSQ = new PDO(
        "mysql:host=localhost;dbname=sidiksae_suratqu;charset=utf8mb4",
        "root",
        "Biangkerok77@"
    );
    $connSQ->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection to SuratQu failed: " . $e->getMessage() . "\n");
}

echo "=== SYNC SURAT FROM SURATQU TO API ===\n\n";

// Get surat from SuratQu that are not in API
$stmt = $connSQ->query("
    SELECT 
        uuid,
        no_agenda,
        no_surat,
        asal_surat,
        tgl_surat,
        perihal,
        tujuan,
        klasifikasi,
        file_path,
        status,
        tgl_agenda,
        created_at
    FROM surat_masuk 
    WHERE status = 'terdaftar'
    AND DATE(created_at) >= '2026-01-10'
    ORDER BY created_at DESC
");

$suratList = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($suratList);

echo "Found $total surat in SuratQu to sync\n\n";

$synced = 0;
$skipped = 0;
$failed = 0;

foreach ($suratList as $surat) {
    echo "Processing: {$surat['no_agenda']} - {$surat['perihal']}\n";
    
    // Check if already exists
    $checkStmt = $connApi->prepare("SELECT uuid FROM surat WHERE uuid = :uuid");
    $checkStmt->execute([':uuid' => $surat['uuid']]);
    
    if ($checkStmt->rowCount() > 0) {
        echo "  → Already exists, skipping\n\n";
        $skipped++;
        continue;
    }
    
    // Prepare metadata
    $metadata = [
        'no_agenda' => $surat['no_agenda'],
        'nomor_surat' => $surat['no_surat'],
        'asal_surat' => $surat['asal_surat'],
        'tanggal_surat' => $surat['tgl_surat'],
        'perihal' => $surat['perihal'],
        'tujuan' => $surat['tujuan'],
        'klasifikasi' => $surat['klasifikasi'],
        'sifat' => 'biasa', // Default
        'tgl_agenda' => $surat['tgl_agenda']
    ];
    
    // Construct full URL for file_path
    $filePath = $surat['file_path'];
    if (!empty($filePath) && !str_starts_with($filePath, 'http')) {
        $filePath = 'https://suratqu.sidiksae.my.id/' . $filePath;
    }
    
    // Insert to API database
    try {
        $insertStmt = $connApi->prepare("
            INSERT INTO surat (
                uuid, 
                nomor_surat,
                tanggal_surat,
                perihal,
                pengirim,
                file_path, 
                source_app, 
                external_id, 
                metadata, 
                created_at, 
                updated_at
            ) VALUES (
                :uuid,
                :nomor_surat,
                :tanggal_surat,
                :perihal,
                :pengirim,
                :file_path,
                'suratqu',
                :external_id,
                :metadata,
                :created_at,
                NOW()
            )
        ");
        
        $insertStmt->execute([
            ':uuid' => $surat['uuid'],
            ':nomor_surat' => $surat['no_surat'],
            ':tanggal_surat' => $surat['tgl_surat'],
            ':perihal' => $surat['perihal'],
            ':pengirim' => $surat['asal_surat'],
            ':file_path' => $filePath,
            ':external_id' => $surat['no_agenda'],
            ':metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
            ':created_at' => $surat['created_at']
        ]);
        
        echo "  ✅ Synced successfully\n\n";
        $synced++;
        
    } catch (PDOException $e) {
        echo "  ❌ Failed: " . $e->getMessage() . "\n\n";
        $failed++;
    }
}

echo "=== SYNC COMPLETE ===\n";
echo "Total: $total\n";
echo "Synced: $synced\n";
echo "Skipped: $skipped\n";
echo "Failed: $failed\n\n";

// Verify
$verifyStmt = $connApi->query("SELECT COUNT(*) FROM surat WHERE source_app = 'suratqu'");
$apiTotal = $verifyStmt->fetchColumn();
echo "Total surat in API from SuratQu: $apiTotal\n";

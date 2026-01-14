<?php
/**
 * Diagnostic Script - Check Surat Registration
 * Run this to see if surat exists in API database
 */

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== SURAT REGISTRATION DIAGNOSTIC ===\n\n";

// Check total surat in API
$stmt = $conn->query("SELECT COUNT(*) as total FROM surat");
$total = $stmt->fetchColumn();
echo "Total surat in API database: $total\n\n";

if ($total > 0) {
    // Show recent surat
    echo "Recent surat entries:\n";
    echo str_repeat("-", 100) . "\n";
    
    $stmt = $conn->query("
        SELECT 
            uuid,
            file_path,
            source_app,
            external_id,
            LEFT(metadata, 100) as metadata_preview,
            created_at
        FROM surat 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "UUID: {$row['uuid']}\n";
        echo "File: {$row['file_path']}\n";
        echo "Source: {$row['source_app']}\n";
        echo "External ID: {$row['external_id']}\n";
        echo "Metadata: {$row['metadata_preview']}...\n";
        echo "Created: {$row['created_at']}\n";
        echo str_repeat("-", 100) . "\n";
    }
    
    // Full metadata of latest
    echo "\nFull metadata of latest surat:\n";
    $stmt = $conn->query("SELECT metadata FROM surat ORDER BY created_at DESC LIMIT 1");
    $meta = $stmt->fetchColumn();
    echo json_encode(json_decode($meta), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n\n";
    
} else {
    echo "❌ NO SURAT FOUND IN API DATABASE!\n\n";
    echo "This means surat was NOT registered to API.\n";
    echo "Possible causes:\n";
    echo "1. API integration disabled in SuratQu\n";
    echo "2. API registration failed silently\n";
    echo "3. Database connection error\n\n";
    
    // Check SuratQu database
    echo "Checking SuratQu database...\n";
    try {
        $connSQ = new PDO(
            "mysql:host=localhost;dbname=sidiksae_suratqu;charset=utf8mb4",
            "root",
            "Biangkerok77@"
        );
        
        $stmt = $connSQ->query("SELECT COUNT(*) FROM surat_masuk WHERE DATE(created_at) = CURDATE()");
        $sqTotal = $stmt->fetchColumn();
        
        echo "Surat in SuratQu today: $sqTotal\n\n";
        
        if ($sqTotal > 0) {
            echo "Recent surat in SuratQu:\n";
            $stmt = $connSQ->query("
                SELECT uuid, no_surat, no_agenda, perihal, file_path, status 
                FROM surat_masuk 
                WHERE DATE(created_at) = CURDATE()
                ORDER BY created_at DESC 
                LIMIT 3
            ");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- {$row['no_agenda']}: {$row['perihal']}\n";
                echo "  UUID: {$row['uuid']}\n";
                echo "  File: {$row['file_path']}\n";
                echo "  Status: {$row['status']}\n\n";
            }
            
            echo "⚠️ Surat EXISTS in SuratQu but NOT in API!\n";
            echo "   → API registration FAILED\n";
        }
        
    } catch (PDOException $e) {
        echo "Could not connect to SuratQu database: " . $e->getMessage() . "\n";
    }
}

echo "\n=== END DIAGNOSTIC ===\n";

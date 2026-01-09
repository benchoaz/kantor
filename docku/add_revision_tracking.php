<?php
// Tool: Add revision tracking to kegiatan table
// Run this once: php add_revision_tracking.php

require_once __DIR__ . '/config/database.php';

try {
    echo "Adding revision tracking columns to kegiatan table...\n";
    
    // Check and add revision_note
    try {
        $pdo->exec("ALTER TABLE kegiatan ADD COLUMN revision_note TEXT DEFAULT NULL");
        echo "✓ Added revision_note column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "→ revision_note column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Check and add revision_by
    try {
        $pdo->exec("ALTER TABLE kegiatan ADD COLUMN revision_by INT DEFAULT NULL");
        echo "✓ Added revision_by column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "→ revision_by column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Check and add revision_at
    try {
        $pdo->exec("ALTER TABLE kegiatan ADD COLUMN revision_at DATETIME DEFAULT NULL");
        echo "✓ Added revision_at column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "→ revision_at column already exists\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "Verifiers can now return reports for revision with notes.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

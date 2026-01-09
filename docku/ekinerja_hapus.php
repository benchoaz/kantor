<?php
// ekinerja_hapus.php - Delete e-Kinerja report
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role(['admin', 'operator']);

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        // Get kegiatan_id before deleting
        $stmt = $pdo->prepare("SELECT kegiatan_id FROM laporan_ekinerja WHERE id = ?");
        $stmt->execute([$id]);
        $report = $stmt->fetch();
        
        if ($report) {
            // Delete e-Kinerja report
            $pdo->prepare("DELETE FROM laporan_ekinerja WHERE id = ?")->execute([$id]);
            
            // Update kegiatan status back to 'belum'
            $pdo->prepare("UPDATE kegiatan SET status_ekinerja = 'belum' WHERE id = ?")->execute([$report['kegiatan_id']]);
        }
    } catch (Exception $e) {
        // Handle error silently or log
    }
}

header("Location: ekinerja.php");
exit;

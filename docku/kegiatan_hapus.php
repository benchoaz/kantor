<?php
// kegiatan_hapus.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role(['admin', 'operator']);

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $pdo->beginTransaction();

        // Get photos to delete files
        $stmt_fotos = $pdo->prepare("SELECT file FROM foto_kegiatan WHERE kegiatan_id = ?");
        $stmt_fotos->execute([$id]);
        $fotos = $stmt_fotos->fetchAll();

        foreach ($fotos as $f) {
            $file_path = 'uploads/foto/' . $f['file'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Delete activity (cascade will delete foto_kegiatan records)
        $stmt = $pdo->prepare("DELETE FROM kegiatan WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        header("Location: kegiatan.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Gagal menghapus kegiatan: " . $e->getMessage());
    }
} else {
    header("Location: kegiatan.php");
    exit;
}
?>

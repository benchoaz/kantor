<?php
// disposisi_selesai_proses.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/integrasi_sistem_handler.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_disposisi = $_POST['id_disposisi'];
    $id_sm = $_POST['id_sm'];
    $catatan = $_POST['catatan_hasil'];
    $file_path = null;

    // Handle File Upload
    if (isset($_FILES['file_hasil']) && $_FILES['file_hasil']['error'] == 0) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['file_hasil']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $dir = 'uploads/disposisi_hasil/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            $filename = 'hasil_' . $id_disposisi . '_' . time() . '.' . $ext;
            $target = $dir . $filename;
            
            if (move_uploaded_file($_FILES['file_hasil']['tmp_name'], $target)) {
                $file_path = $target;
            }
        }
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE disposisi SET 
                              status_pengerjaan = 'selesai', 
                              tanggal_selesai = NOW(),
                              catatan_hasil = ?,
                              file_hasil = ?
                              WHERE id_disposisi = ?");
        
        $stmt->execute([$catatan, $file_path, $id_disposisi]);
        
        // Log Activity
        logActivity("Melaporkan penyelesaian disposisi (ID: $id_disposisi)", "disposisi", $id_disposisi);
        
        $db->commit();

        // Integrasi API: Update Status di SidikSae (Jika Connected)
        if (function_exists('updateDisposisiStatusSidikSae')) {
             updateDisposisiStatusSidikSae($db, $id_disposisi, 'selesai', $catatan);
        }

        $_SESSION['alert'] = ['msg' => 'Laporan pekerjaan berhasil disimpan!', 'type' => 'success'];

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['alert'] = ['msg' => 'Gagal menyimpan laporan: ' . $e->getMessage(), 'type' => 'danger'];
    }
}

header("Location: surat_masuk_detail.php?id=$id_sm");
exit;
?>

<?php
// kop_surat_proses.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak!");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_kop'])) {
    $id_kop = $_POST['id_kop'];
    $nama = $_POST['nama_instansi'];
    $l1 = $_POST['nama_instansi_l1'];
    $l2 = $_POST['nama_instansi_l2'];
    $alamat = $_POST['alamat'];
    $kontak = $_POST['kontak'];

    // Fetch existing paths
    $stmt = $db->prepare("SELECT logo_path, logo_kanan_path FROM kop_surat WHERE id_kop = ?");
    $stmt->execute([$id_kop]);
    $existing = $stmt->fetch();
    
    $logo_kiri = $existing['logo_path'];
    $logo_kanan = $existing['logo_kanan_path'];

    $upload_dir = 'uploads/logos/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    // Handle Logo Kiri
    if (isset($_FILES['logo_kiri']) && $_FILES['logo_kiri']['error'] == 0) {
        $filename = 'logo_kiri_' . time() . '_' . $_FILES['logo_kiri']['name'];
        if (move_uploaded_file($_FILES['logo_kiri']['tmp_name'], $upload_dir . $filename)) {
            $logo_kiri = $upload_dir . $filename;
        }
    }

    // Handle Logo Kanan
    if (isset($_FILES['logo_kanan']) && $_FILES['logo_kanan']['error'] == 0) {
        $filename = 'logo_kanan_' . time() . '_' . $_FILES['logo_kanan']['name'];
        if (move_uploaded_file($_FILES['logo_kanan']['tmp_name'], $upload_dir . $filename)) {
            $logo_kanan = $upload_dir . $filename;
        }
    }

    try {
        $stmt = $db->prepare("UPDATE kop_surat SET 
            nama_instansi = ?, 
            nama_instansi_l1 = ?, 
            nama_instansi_l2 = ?, 
            alamat = ?, 
            kontak = ?, 
            logo_path = ?, 
            logo_kanan_path = ? 
            WHERE id_kop = ?");
        $stmt->execute([$nama, $l1, $l2, $alamat, $kontak, $logo_kiri, $logo_kanan, $id_kop]);
        
        logActivity("Mengubah pengaturan Kop Surat Fleksibel");
        $_SESSION['alert'] = ['msg' => 'Pengaturan Kop Surat berhasil disimpan!', 'type' => 'success'];
        header("Location: kop_surat.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['alert'] = ['msg' => 'Error: ' . $e->getMessage(), 'type' => 'danger'];
        header("Location: kop_surat.php");
        exit;
    }
}
?>

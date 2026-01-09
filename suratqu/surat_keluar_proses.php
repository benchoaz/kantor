<?php
// surat_keluar_proses.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_POST['submit'])) {
    $id_user_pembuat = $_POST['id_user_pembuat'];
    $tujuan = $_POST['tujuan'];
    $perihal = $_POST['perihal'];
    $tgl_surat = $_POST['tgl_surat'];
    $klasifikasi = $_POST['klasifikasi'];
    $isi_surat = $_POST['isi_surat']; // Added isi_surat

    try {
        $stmt = $db->prepare("INSERT INTO surat_keluar (tgl_surat, tujuan, perihal, isi_surat, id_user_pembuat, status, klasifikasi) 
                             VALUES (?, ?, ?, ?, ?, 'draft', ?)"); // Added isi_surat column
        $stmt->execute([$tgl_surat, $tujuan, $perihal, $isi_surat, $id_user_pembuat, $klasifikasi]); // Added $isi_surat
        
        $id_new = $db->lastInsertId();
        logActivity("Membuat draft surat keluar baru", "surat_keluar", $id_new);
        
        $_SESSION['alert'] = ['msg' => 'Draft surat berhasil disimpan!', 'type' => 'success'];
        header("Location: surat_keluar.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['alert'] = ['msg' => 'Gagal menyimpan draft: ' . $e->getMessage(), 'type' => 'danger'];
        header("Location: surat_keluar_tambah.php");
        exit;
    }
}
if (isset($_POST['update'])) {
    $id_sk = $_POST['id_sk'];
    $tujuan = $_POST['tujuan'];
    $perihal = $_POST['perihal'];
    $tgl_surat = $_POST['tgl_surat'];
    $klasifikasi = $_POST['klasifikasi'];
    $isi_surat = $_POST['isi_surat']; // Added isi_surat

    try {
        $stmt = $db->prepare("UPDATE surat_keluar SET tgl_surat = ?, tujuan = ?, perihal = ?, isi_surat = ?, klasifikasi = ? WHERE id_sk = ?"); // Added isi_surat = ?
        $stmt->execute([$tgl_surat, $tujuan, $perihal, $isi_surat, $klasifikasi, $id_sk]); // Added $isi_surat
        
        logActivity("Memperbarui data surat keluar id: $id_sk", "surat_keluar", $id_sk);
        
        $_SESSION['alert'] = ['msg' => 'Surat keluar berhasil diperbarui!', 'type' => 'success'];
        header("Location: surat_keluar.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['alert'] = ['msg' => 'Gagal memperbarui surat: ' . $e->getMessage(), 'type' => 'danger'];
        header("Location: surat_keluar_edit.php?id=$id_sk");
        exit;
    }
}

// Action: Request Verification
if (isset($_GET['action']) && $_GET['action'] == 'request_verif') {
    $id = $_GET['id'];
    try {
        $stmt = $db->prepare("UPDATE surat_keluar SET status = 'verifikasi', catatan_koreksi = NULL WHERE id_sk = ?");
        $stmt->execute([$id]);
        
        logActivity("Mengajukan verifikasi surat keluar ID: $id", "surat_keluar", $id);
        redirect('surat_keluar.php', 'Surat telah diajukan untuk verifikasi!');
    } catch (Exception $e) {
        redirect('surat_keluar.php', 'Gagal mengajukan verifikasi: ' . $e->getMessage(), 'danger');
    }
}

// Action: Approve Letter
if (isset($_POST['approve'])) {
    $id = $_POST['id_sk'];
    try {
        // Generate Automatic Nomor Surat (Format: Klasifikasi/No/Year)
        $stmt = $db->prepare("SELECT klasifikasi, YEAR(tgl_surat) as tahun FROM surat_keluar WHERE id_sk = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        $klasifikasi = $row['klasifikasi'] ?: '000';
        $tahun = $row['tahun'];
        
        // Count existing approved letters in this year
        $stmt = $db->prepare("SELECT COUNT(*) FROM surat_keluar WHERE YEAR(tgl_surat) = ? AND status IN ('disetujui', 'terkirim')");
        $stmt->execute([$tahun]);
        $count = $stmt->fetchColumn() + 1;
        
        $no_surat = "$klasifikasi/" . str_pad($count, 3, '0', STR_PAD_LEFT) . "/45.6.XI/$tahun";
        
        $stmt = $db->prepare("UPDATE surat_keluar SET status = 'disetujui', no_surat = ?, catatan_koreksi = NULL WHERE id_sk = ?");
        $stmt->execute([$no_surat, $id]);
        
        logActivity("Menyetujui surat keluar ID: $id (No: $no_surat)", "surat_keluar", $id);
        redirect('verifikasi_surat.php', 'Surat telah disetujui dan nomor resmi telah diterbitkan!');
    } catch (Exception $e) {
        redirect('verifikasi_surat.php', 'Gagal menyetujui surat: ' . $e->getMessage(), 'danger');
    }
}

// Action: Reject Letter
if (isset($_POST['reject'])) {
    $id = $_POST['id_sk'];
    $catatan = $_POST['catatan'];
    try {
        $stmt = $db->prepare("UPDATE surat_keluar SET status = 'draft', catatan_koreksi = ? WHERE id_sk = ?");
        $stmt->execute([$catatan, $id]);
        
        logActivity("Menolak/Koreksi surat keluar ID: $id", "surat_keluar", $id);
        redirect('verifikasi_surat.php', 'Surat telah dikirim kembali ke staf untuk perbaikan.', 'warning');
    } catch (Exception $e) {
        redirect('verifikasi_surat.php', 'Gagal memproses penolakan: ' . $e->getMessage(), 'danger');
    }
}
?>

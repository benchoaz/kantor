<?php
// disposisi_proses.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/integrasi_sistem_handler.php';

if (isset($_POST['submit'])) {
    $id_sm = $_POST['id_sm'];
    $pengirim_id = $_POST['pengirim_id'];
    $penerima_id = $_POST['penerima_id'];
    $instruksi = $_POST['instruksi'];
    $batas_waktu = !empty($_POST['batas_waktu']) ? $_POST['batas_waktu'] : null;

    try {
        $db->beginTransaction();

        // 1. Simpan Transaksi Disposisi
        $stmt = $db->prepare("INSERT INTO disposisi (id_sm, pengirim_id, penerima_id, instruksi, batas_waktu) 
                             VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_sm, $pengirim_id, $penerima_id, $instruksi, $batas_waktu]);
        
        // 2. Update Status Surat Masuk
        $stmt = $db->prepare("UPDATE surat_masuk SET status = 'disposisi' WHERE id_sm = ?");
        $stmt->execute([$id_sm]);
        
        // 3. Ambil data untuk notifikasi
        $stmt = $db->prepare("SELECT no_agenda, asal_surat FROM surat_masuk WHERE id_sm = ?");
        $stmt->execute([$id_sm]);
        $surat = $stmt->fetch();
        
        $stmt = $db->prepare("SELECT telegram_id, nama_lengkap FROM users WHERE id_user = ?");
        $stmt->execute([$penerima_id]);
        $penerima = $stmt->fetch();

        $stmt = $db->prepare("SELECT nama_lengkap FROM users WHERE id_user = ?");
        $stmt->execute([$pengirim_id]);
        $pengirim_nama = $stmt->fetch()['nama_lengkap'];

        $new_disposisi_id = $db->lastInsertId();

        // 4. Log Activity
        logActivity("Mengirim disposisi surat: {$surat['no_agenda']} ke {$penerima['nama_lengkap']}", "disposisi", $new_disposisi_id);
        
        $db->commit();

        // 5. Kirim ke SidikSae API (Integrasi Sistem Terpusat)
        pushDisposisiToSidikSae($db, $new_disposisi_id);

        // 5. Kirim Notifikasi Telegram (Jika ada ID)
        if (!empty($penerima['telegram_id'])) {
            $msg = "<b>⚠️ DISPOSISI BARU</b>\n\n";
            $msg .= "Dari: $pengirim_nama\n";
            $msg .= "Surat: {$surat['asal_surat']}\n";
            $msg .= "No. Agenda: {$surat['no_agenda']}\n\n";
            $msg .= "<b>Instruksi:</b>\n$instruksi";
            if ($batas_waktu) $msg .= "\n\n<i>Batas Waktu: ".format_tgl_indo($batas_waktu)."</i>";
            
            sendTelegram($penerima['telegram_id'], $msg);
        }

        $_SESSION['alert'] = ['msg' => 'Disposisi berhasil dikirim!', 'type' => 'success'];
        header("Location: surat_masuk_detail.php?id=$id_sm");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['alert'] = ['msg' => 'Gagal mengirim disposisi: ' . $e->getMessage(), 'type' => 'danger'];
        header("Location: disposisi.php?id_sm=$id_sm");
        exit;
    }
}
?>

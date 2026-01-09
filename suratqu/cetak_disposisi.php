<?php
// cetak_disposisi.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

$id_sm = $_GET['id'] ?? null;
if (!$id_sm) { die("ID Surat tidak valid."); }

// Fetch Surat
$stmt = $db->prepare("SELECT * FROM surat_masuk WHERE id_sm = ?");
$stmt->execute([$id_sm]);
$surat = $stmt->fetch();

if (!$surat) { die("Surat tidak ditemukan."); }

// Fetch Disposisi Pertama (Dari Operator/System ke Camat)
// Asumsi: Disposisi 'root' atau yang dibuat saat Agendakan
$stmt = $db->prepare("SELECT * FROM disposisi WHERE id_sm = ? ORDER BY tgl_disposisi ASC LIMIT 1");
$stmt->execute([$id_sm]);
$disposisi = $stmt->fetch();

// Fetch Camat Name (Penerima)
$camat_nama = "Camat";
if ($disposisi) {
    $stmt_u = $db->prepare("SELECT nama_lengkap FROM users WHERE id_user = ?");
    $stmt_u->execute([$disposisi['penerima_id']]);
    $camat_nama = $stmt_u->fetchColumn() ?: "Camat";
}

// Config Instansi (Hardcoded or DB)
$instansi_header = "PEMERINTAH KABUPATEN PROBOLINGGO";
$instansi_sub = "KECAMATAN KRAKSAAN";
$alamat = "Jl. Panglima Sudirman No. 123, Kraksaan - Probolinggo";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Disposisi - <?= htmlspecialchars($surat['no_agenda']) ?></title>
    <style>
        body { font-family: "Times New Roman", serif; font-size: 12pt; margin: 0; padding: 20px; }
        .container { width: 100%; max-width: 210mm; margin: 0 auto; border: 2px solid #000; padding: 10px; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h3, .header h2, .header p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 5px 10px; vertical-align: top; }
        .no-border td { border: none; padding: 2px; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .row-gap { height: 10px; }
        .box { min-height: 100px; }
        @media print {
            @page { size: auto; margin: 0mm; }
            body { margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container">
    <div class="header">
        <h3><?= $instansi_header ?></h3>
        <h2><?= $instansi_sub ?></h2>
        <p class="small"><?= $alamat ?></p>
    </div>

    <h3 class="text-center" style="text-decoration: underline; margin-bottom: 20px;">LEMBAR DISPOSISI</h3>

    <table>
        <tr>
            <td width="20%"><strong>Index:</strong><br><?= htmlspecialchars($surat['klasifikasi']) ?></td>
            <td width="30%"><strong>Kode:</strong><br><?= htmlspecialchars($surat['klasifikasi']) ?></td> <!-- Simplified -->
            <td width="50%"><strong>No. Agenda:</strong><br><?= htmlspecialchars($surat['no_agenda']) ?></td>
        </tr>
    </table>

    <table>
        <tr>
            <td width="50%" class="no-border">
                <table class="no-border">
                    <tr>
                        <td width="30%">Surat Dari</td>
                        <td width="5%">:</td>
                        <td><?= htmlspecialchars($surat['asal_surat']) ?></td>
                    </tr>
                    <tr>
                        <td>No. Surat</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($surat['no_surat']) ?></td>
                    </tr>
                    <tr>
                        <td>Tgl. Surat</td>
                        <td>:</td>
                        <td><?= format_tgl_indo($surat['tgl_surat']) ?></td>
                    </tr>
                </table>
            </td>
            <td width="50%" style="vertical-align: middle;">
                <strong>Diterima Tanggal:</strong><br>
                <?= format_tgl_indo($surat['tgl_agenda'] ?? date('Y-m-d')) ?>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="min-height: 60px;">
                <strong>Perihal:</strong><br>
                <?= htmlspecialchars($surat['perihal']) ?>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <th width="50%">DITERUSKAN KEPADA Sdr:</th>
            <th width="50%">DENGAN HORMAT HARAP:</th>
        </tr>
        <tr>
            <td class="box">
                <!-- Static target for Auto-Disposisi -->
                1. <?= htmlspecialchars($camat_nama) ?> (CAMAT)<br>
                <br>
                <small>*Disposisi digital dilesaikan via App Pimpinan</small>
            </td>
            <td class="box">
               <ul style="list-style-type: none; padding-left: 0;">
                   <li>[ ] Tanggapan dan Saran</li>
                   <li>[ ] Proses Lebih Lanjut</li>
                   <li>[ ] Koordinasi / Konfirmasi</li>
                   <li>[ ] ...</li>
               </ul>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="box">
                <strong>Catatan / Instruksi Pimpinan:</strong><br>
                <br>
                <?php if($disposisi && $disposisi['catatan_hasil']): ?>
                    <em>Hasil: <?= htmlspecialchars($disposisi['catatan_hasil']) ?></em>
                <?php else: ?>
                    ...........................................................................................
                <?php endif; ?>
            </td>
        </tr>
    </table>
    
    <div style="float: right; text-align: center; margin-top: 20px;">
        Operator,<br><br><br>
        ( ..................................... )
    </div>
</div>

</body>
</html>

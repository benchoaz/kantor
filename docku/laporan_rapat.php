<?php
// Enable local error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'vendor/fpdf/fpdf.php';
require_once 'includes/pdf_base.php';
require_once 'includes/helpers.php';
require_login();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT k.*, b.nama_bidang, u.nama as pembuat 
                        FROM kegiatan k 
                        JOIN bidang b ON k.bidang_id = b.id 
                        JOIN users u ON k.created_by = u.id 
                        WHERE k.id = ? AND k.tipe_kegiatan = 'rapat'");
$stmt->execute([$id]);
$k = $stmt->fetch();

if (!$k) {
    die("Data rapat tidak ditemukan.");
}

$stmt_foto = $pdo->prepare("SELECT * FROM foto_kegiatan WHERE kegiatan_id = ?");
$stmt_foto->execute([$id]);
$fotos = $stmt_foto->fetchAll();

// Fetch Kop Surat Settings (Safeguard)
try {
    $stmtS = $pdo->query("SELECT * FROM pengaturan LIMIT 1");
    $kop = $stmtS ? $stmtS->fetch() : false;
} catch (Exception $e) {
    $kop = false;
}

if (!$kop) {
    $kop = [
        'nama_instansi_1' => 'PEMERINTAH KABUPATEN PROBOLINGGO',
        'nama_instansi_2' => 'KECAMATAN BESUK',
        'alamat_1' => 'Jalan Raya Besuk No. 1, Besuk, Probolinggo',
        'alamat_2' => 'Email: kecamatan.besuk@probolinggokab.go.id',
        'nama_camat' => 'PUJA KURNIAWAN, S.STP., M.Si',
        'nip_camat' => '19800101 200001 1 001'
    ];
}

// 1. Get Orientation & Size from parameters
$orient = $_GET['orient'] ?? 'P';
$size = $_GET['size'] ?? 'F4';

if (!class_exists('PDF')) {
    class PDF extends PDF_Base {
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(128);
            $this->Cell(0, 10, 'BESUK SAE / Notulen Rapat / ' . date('d/m/Y H:i') . ' - Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
    }
}

$pdf = new PDF($orient, 'mm', $size);
$pdf->setKop($kop);
$pdf->AliasNbPages();

// Set Dynamic Margins
if ($orient === 'P') {
    $pdf->SetMargins(40, 30, 30);
} else {
    $pdf->SetMargins(30, 40, 30);
}

$pdf->AddPage();

// Title
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'NOTULEN RAPAT', 0, 1, 'C');
$pdf->Ln(10);

// Information Block (Standard 4.1)
// Function now from pdf_base.php

addInfoRow($pdf, 'Hari / Tanggal', format_tanggal_indonesia($k['tanggal']));
addInfoRow($pdf, 'Tempat / Lokasi', $k['lokasi']);
addInfoRow($pdf, 'Agenda / Acara', $k['agenda'] ?: '-');
addInfoRow($pdf, 'Pimpinan Rapat', $k['pimpinan_rapat'] ?: '-');
addInfoRow($pdf, 'Notulis', $k['notulis'] ?: '-');

$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'I. HASIL PEMBAHASAN / KESIMPULAN', 0, 1, 'L');
$pdf->SetFont('Arial', '', 11);
$pdf->SetX($pdf->GetLMargin() + 12.5);
// Standard spacing 1.5
$pdf->MultiCell(0, 9, $k['kesimpulan'] ?: 'Tidak ada kesimpulan yang dicatat.', 0, 'J');

// Documentation
if (!empty($fotos)) {
    $pdf->SetMargins(30, 40, 30);
    $pdf->AddPage('L');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'LAMPIRAN DOKUMENTASI RAPAT', 0, 1, 'C');
    $pdf->Ln(5);

    $grid_w = $pdf->GetPageWidth() - $pdf->GetLMargin() - $pdf->GetRMargin();
    $img_w = ($grid_w - 15) / 2;
    $img_h = $img_w * 0.75;
    
    $count = 0;
    foreach ($fotos as $idx => $f) {
        $img_path = 'uploads/foto/' . $f['file'];
        if (file_exists($img_path)) {
            if ($count > 0 && $count % 4 == 0) {
                $pdf->AddPage('L');
                $pdf->SetY(85);
            }
            $col = $count % 2;
            $row = floor(($count % 4) / 2);
            $x = $pdf->GetLMargin() + ($col * ($img_w + 10));
            $y = $pdf->GetY() + ($row * ($img_h + 20));

            $img_type = get_fpdf_image_type($img_path);

            if ($img_type) {
                $pdf->Image($img_path, $x, $y, $img_w, $img_h, $img_type);
                $pdf->SetXY($x, $y + $img_h + 2);
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell($img_w, 6, 'Gambar ' . ($idx + 1) . '.', 0, 1, 'C');
                $count++;
            }
        }
    }
}

// Double Signatures
if ($pdf->GetY() > ($pdf->GetPageHeight() - 85)) $pdf->AddPage($orient);

if ($orient === 'P') {
    $pdf->SetMargins(40, 30, 30);
} else {
    $pdf->SetMargins(30, 40, 30);
}

$pdf->SetY($pdf->GetPageHeight() - 75);
$pdf->SetFont('Arial', '', 11);

$sign_box_w = ($pdf->GetPageWidth() - $pdf->GetLMargin() - $pdf->GetRMargin()) / 2;

$pdf->SetX($pdf->GetLMargin());
$pdf->Cell($sign_box_w, 6, 'Mengetahui,', 0, 0, 'C');
$pdf->SetX($pdf->GetPageWidth() - $pdf->GetRMargin() - $sign_box_w);
$pdf->Cell($sign_box_w, 6, 'Besuk, ' . date('d', strtotime($k['tanggal'])) . ' ' . ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][date('n', strtotime($k['tanggal']))] . ' ' . date('Y', strtotime($k['tanggal'])), 0, 1, 'C');

$pdf->SetX($pdf->GetLMargin());
$pdf->Cell($sign_box_w, 6, 'Pimpinan Rapat,', 0, 0, 'C');
$pdf->SetX($pdf->GetPageWidth() - $pdf->GetRMargin() - $sign_box_w);
$pdf->Cell($sign_box_w, 6, 'Notulis,', 0, 1, 'C');

$pdf->Ln(20);

$pdf->SetX($pdf->GetLMargin());
$pdf->SetFont('Arial', 'BU', 11);
$pdf->Cell($sign_box_w, 6, ($k['pimpinan_rapat'] ?: '...........................'), 0, 0, 'C');
$pdf->SetX($pdf->GetPageWidth() - $pdf->GetRMargin() - $sign_box_w);
$pdf->Cell($sign_box_w, 6, ($k['notulis'] ?: '...........................'), 0, 1, 'C');

if (!empty($kop['golongan_ttd'])) {
    $pdf->SetX($pdf->GetLMargin());
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($sign_box_w, 6, $kop['golongan_ttd'], 0, 1, 'C');
}

$pdf->Output('I', 'Notulen_Rapat_' . $k['id'] . '.pdf');
?>

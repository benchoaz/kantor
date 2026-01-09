<?php
// laporan_rekap_pdf.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'vendor/fpdf/fpdf.php';
require_once 'includes/pdf_base.php';
require_once 'includes/helpers.php';
require_login();

// 1. Get Filters
$jenis = $_GET['jenis'] ?? 'harian';
$user_id = $_GET['user_id'] ?? 0;

if (!$user_id) die("Pilih personil terlebih dahulu.");

// 2. Fetch User Info (Reporter)
$stmt_user = $pdo->prepare("SELECT nama, nip, jabatan, bidang_id FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$u = $stmt_user->fetch();

if (!$u) die("User tidak ditemukan.");

// 3. Build Query
$sql = "SELECT k.*, b.nama_bidang 
        FROM kegiatan k 
        LEFT JOIN bidang b ON k.bidang_id = b.id 
        WHERE k.created_by = ?";
$params = [$user_id];
$periode_label = "";

if ($jenis === 'harian') {
    $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
    $sql .= " AND DATE(k.tanggal) = ?";
    $params[] = $tanggal;
    $periode_label = format_tanggal_indonesia($tanggal);
} elseif ($jenis === 'bulanan') {
    $bulan = $_GET['bulan'] ?? date('m');
    $tahun = $_GET['tahun'] ?? date('Y');
    $sql .= " AND MONTH(k.tanggal) = ? AND YEAR(k.tanggal) = ?";
    $params[] = $bulan;
    $params[] = $tahun;
    $periode_label = get_bulan_indo($bulan) . " " . $tahun;
} elseif ($jenis === 'tahunan') {
    $tahun = $_GET['tahun'] ?? date('Y');
    $sql .= " AND YEAR(k.tanggal) = ?";
    $params[] = $tahun;
    $periode_label = "Tahun " . $tahun;
}

$sql .= " ORDER BY k.tanggal ASC, k.created_at ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $kegiatan = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error Database (Belum Migrasi?): " . $e->getMessage());
}

// Fetch Kop
try {
    $stmtS = $pdo->query("SELECT * FROM pengaturan LIMIT 1");
    $kop = $stmtS ? $stmtS->fetch() : false;
} catch (Exception $e) { $kop = false; }

if (!$kop) {
    $kop = [
        'nama_instansi_1' => 'PEMERINTAH KABUPATEN PROBOLINGGO',
        'nama_instansi_2' => 'KECAMATAN BESUK',
        'alamat_1' => 'Jalan Raya Besuk No. 1, Besuk, Probolinggo',
        'alamat_2' => 'Email: kecamatan.besuk@probolinggokab.go.id',
        'nama_camat' => 'PUJA KURNIAWAN, S.STP., M.Si',
        'nip_camat' => '19800101 200001 1 001',
        'jabatan_ttd' => 'Camat Besuk',
        'golongan_ttd' => 'Pembina Tingkat I'
    ];
}

// Helper for Month (Now in helpers.php)

if (!class_exists('PDF')) { class PDF extends PDF_Base {} }

$pdf = new PDF('P', 'mm', 'F4');
$pdf->setKop($kop);
$pdf->AliasNbPages();
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// === HEADER BIODATA ===
$pdf->SetFont('Arial', '', 10);
$pdf->SetY($pdf->tMargin - 10 + 40); // Standard header area

$label_w = 35; $sep_w = 5; $val_w = 100;

$pdf->Cell($label_w, 6, 'Nama Lengkap', 0, 0);
$pdf->Cell($sep_w, 6, ':', 0, 0);
$pdf->Cell($val_w, 6, strtoupper($u['nama']), 0, 1);

$pdf->Cell($label_w, 6, 'NIP', 0, 0);
$pdf->Cell($sep_w, 6, ':', 0, 0);
$pdf->Cell($val_w, 6, $u['nip'] ?: '-', 0, 1);

$pdf->Cell($label_w, 6, 'Jabatan', 0, 0);
$pdf->Cell($sep_w, 6, ':', 0, 0);
$pdf->Cell($val_w, 6, $u['jabatan'] ?: '-', 0, 1);

$pdf->Cell($label_w, 6, 'Periode Laporan', 0, 0);
$pdf->Cell($sep_w, 6, ':', 0, 0);
$pdf->Cell($val_w, 6, $periode_label, 0, 1);

$pdf->Ln(5);

// === TABLE HEADER ===
$w_no = 10;
$w_waktu = 25;
$w_uraian = 60;
$w_lokasi = 35;
$w_dok = 50;

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell($w_no, 10, 'NO', 1, 0, 'C', true);
$pdf->Cell($w_waktu, 10, 'WAKTU', 1, 0, 'C', true);
$pdf->Cell($w_uraian, 10, 'URAIAN KEGIATAN', 1, 0, 'C', true);
$pdf->Cell($w_lokasi, 10, 'LOKASI', 1, 0, 'C', true);
$pdf->Cell($w_dok, 10, 'DOKUMENTASI', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);

if (empty($kegiatan)) {
    $pdf->Cell($w_no+$w_waktu+$w_uraian+$w_lokasi+$w_dok, 10, 'Tidak ada kegiatan pada periode ini.', 1, 1, 'C');
} else {
    $no = 1;
    foreach ($kegiatan as $k) {
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        // 1. Prepare Content
        $text_uraian = $k['judul'] . "\n" . $k['deskripsi'];
        
        // 2. Fetch First Photo
        $stmt_foto = $pdo->prepare("SELECT file FROM foto_kegiatan WHERE kegiatan_id = ? ORDER BY uploaded_at ASC LIMIT 1");
        $stmt_foto->execute([$k['id']]);
        $foto = $stmt_foto->fetch();

        // 3. Calc Heights
        $lines_uraian = $pdf->NbLines($w_uraian, $text_uraian);
        $h_line = 5;
        $h_text = max(10, $lines_uraian * $h_line + 4); 
        $h_image = 35;
        $row_h = max($h_text, 40);

        // 4. Page Break Check
        if ($y + $row_h > $pdf->GetPageHeight() - 25) { 
            $pdf->AddPage();
            // Reprint Header
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell($w_no, 10, 'NO', 1, 0, 'C', true);
            $pdf->Cell($w_waktu, 10, 'WAKTU', 1, 0, 'C', true);
            $pdf->Cell($w_uraian, 10, 'URAIAN KEGIATAN', 1, 0, 'C', true);
            $pdf->Cell($w_lokasi, 10, 'LOKASI', 1, 0, 'C', true);
            $pdf->Cell($w_dok, 10, 'DOKUMENTASI', 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 10);
            $y = $pdf->GetY();
            $x = $pdf->GetX();
        }

        // 5. Render Cells
        $pdf->SetXY($x, $y);
        $pdf->Cell($w_no, $row_h, '', 1, 0);
        $pdf->Text($x + ($w_no/2) - 2, $y + ($row_h/2) + 1, $no++);

        $pdf->SetXY($x + $w_no, $y);
        $pdf->Cell($w_waktu, $row_h, '', 1, 0);
        $tgl_str = date('d/m/y', strtotime($k['tanggal']));
        
        $wkt_str = format_wib($k['created_at'], true);
        if (isset($k['jam_mulai']) && !empty($k['jam_mulai'])) {
            $wkt_str = format_wib($k['jam_mulai'], false);
            if (isset($k['jam_selesai']) && !empty($k['jam_selesai'])) {
                $wkt_str .= '-' . format_wib($k['jam_selesai'], false);
            }
        }
        
        $pdf->Text($x + $w_no + 5, $y + ($row_h/2) - 2, $tgl_str);
        $pdf->Text($x + $w_no + 8, $y + ($row_h/2) + 3, $wkt_str . " WIB");

        // Uraian
        $pdf->SetXY($x + $w_no + $w_waktu, $y);
        $pdf->MultiCell($w_uraian, $h_line, "\n" . $text_uraian, 0, 'L');
        $pdf->SetXY($x + $w_no + $w_waktu, $y);
        $pdf->Cell($w_uraian, $row_h, '', 1, 0);

        // Lokasi
        $pdf->SetXY($x + $w_no + $w_waktu + $w_uraian, $y);
        $pdf->Cell($w_lokasi, $row_h, '', 1, 0); 
        $pdf->Text($x + $w_no + $w_waktu + $w_uraian + ($w_lokasi/2) - ($pdf->GetStringWidth($k['lokasi'])/2), $y + ($row_h/2) + 1, $k['lokasi']);

        // Dokumentasi
        $pdf->SetXY($x + $w_no + $w_waktu + $w_uraian + $w_lokasi, $y);
        $pdf->Cell($w_dok, $row_h, '', 1, 0);

        // Image
        if ($foto && file_exists('uploads/foto/' . $foto['file'])) {
            $img_path = 'uploads/foto/' . $foto['file'];
            $img_w_avail = $w_dok - 6;
            $img_h_avail = $h_image;
            $x_img = $x + $w_no + $w_waktu + $w_uraian + $w_lokasi + 3;
            $y_img = $y + ($row_h - $img_h_avail) / 2;
            $pdf->Image($img_path, $x_img, $y_img, $img_w_avail, $img_h_avail);
        } else {
             $pdf->Text($x + $w_no + $w_waktu + $w_uraian + $w_lokasi + 15, $y + ($row_h/2), '(No Image)');
        }

        $pdf->Ln($row_h);
    }
}

$pdf->Ln(10);

// === SIGNATURE (Atomic Block) ===
$sig_height = 90;
$remaining_space = $pdf->GetPageHeight() - $pdf->GetRMargin() - $pdf->GetY();

if ($remaining_space < $sig_height) {
    $pdf->AddPage();
}

$right_w = 80;
$x_block = $pdf->GetPageWidth() - $pdf->GetRMargin() - $right_w;

// 1. Tempat & Tanggal
$pdf->SetX($x_block);
$pdf->Cell($right_w, 6, 'Besuk, ' . format_tanggal_indonesia(date('Y-m-d'), false), 0, 1, 'C');

// 2. Mengetahui
$pdf->SetX($x_block);
$pdf->Cell($right_w, 6, 'Mengetahui,', 0, 1, 'C');

// 3. Jabatan
$pdf->SetX($x_block);
$pdf->Cell($right_w, 6, $kop['jabatan_ttd'] ?? 'Camat Besuk', 0, 1, 'C');

// 4. Space for Signature
$y_sig = $pdf->GetY();
if (file_exists('assets/img/stempel.png')) {
    $pdf->Image('assets/img/stempel.png', $x_block - 10, $y_sig - 5, 35);
}
if (file_exists('assets/img/ttd_camat.png')) {
    $pdf->Image('assets/img/ttd_camat.png', $x_block + 5, $y_sig, 30);
}
$pdf->Ln(25);

// 5. Nama Lengkap (Bold, Upper, Underline)
$pdf->SetX($x_block);
$pdf->SetFont('Arial', 'BU', 11);
$pdf->Cell($right_w, 6, strtoupper($kop['nama_camat']), 0, 1, 'C');

// 6. Pangkat/Golongan
if (!empty($kop['golongan_ttd'])) {
    $pdf->SetX($x_block);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($right_w, 6, $kop['golongan_ttd'], 0, 1, 'C');
}

// 7. NIP
$pdf->SetX($x_block);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell($right_w, 6, 'NIP. ' . $kop['nip_camat'], 0, 0, 'C');

$pdf->Output('I', 'Laporan_Rekap_' . $periode_label . '.pdf');
?>

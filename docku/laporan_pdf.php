<?php
// laporan_pdf.php - Table Format
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display to prevent PDF corruption

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'vendor/fpdf/fpdf.php';
require_once 'includes/pdf_base.php';
require_once 'includes/helpers.php';
require_login();

$id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT k.*, b.nama_bidang, u.nama as pembuat, u.nip as user_nip 
                            FROM kegiatan k 
                            JOIN bidang b ON k.bidang_id = b.id 
                            JOIN users u ON k.created_by = u.id 
                            WHERE k.id = ?");
    $stmt->execute([$id]);
    $k = $stmt->fetch();
} catch (Exception $e) {
    die("Error Database (Belum Migrasi?): " . $e->getMessage());
}

if (!$k) {
    die("Kegiatan tidak ditemukan.");
}

// Fetch photos
try {
    $stmt_fotos = $pdo->prepare("SELECT fk.*, u.nama as contributor
                                FROM foto_kegiatan fk
                                LEFT JOIN users u ON fk.user_id = u.id
                                WHERE fk.kegiatan_id = ?
                                ORDER BY fk.uploaded_at ASC");
    $stmt_fotos->execute([$id]);
    $fotos = $stmt_fotos->fetchAll();
} catch (Exception $e) {
    $fotos = [];
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

// Use F4 size
$pdf = new PDF('P', 'mm', 'F4');
$pdf->setKop($kop);
$pdf->AliasNbPages();
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();
$pdf->Ln(5);

// Mode Formal (Laporan ke Bupati)
if (isset($_GET['mode']) && $_GET['mode'] === 'formal') {
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, 'Besuk, ' . format_tanggal_indonesia($k['tanggal'], false), 0, 1, 'R');
    $pdf->Ln(5);
    $pdf->Cell(0, 6, 'Kepada Yth.', 0, 1);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 6, 'Bapak Bupati Probolinggo', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, 'di -', 0, 1);
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->Cell(0, 6, '       PROBOLINGGO', 0, 1);
    $pdf->Ln(10);
    
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, 'LAPORAN KEGIATAN KHUSUS', 0, 1, 'C');
} else {
    // === TITLE ===
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, 'LAPORAN KEGIATAN', 0, 1, 'C');
    if (!empty($k['nama_bidang'])) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 6, strtoupper($k['nama_bidang']), 0, 1, 'C');
    }
}
$pdf->Ln(5);

// === IDENTITY BLOCK (Restored) ===
$pdf->SetFont('Arial', '', 11);
$start_x = 20; // Left margin + indent
$label_w = 35;
$sep_w = 5;

// Helper to print row
function print_identity_row($pdf, $label, $value, $x, $w1, $w2) {
    $pdf->SetX($x);
    $pdf->Cell($w1, 6, $label, 0, 0, 'L');
    $pdf->Cell($w2, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, $value, 0, 1, 'L');
}

// 1. Nama Lengkap
print_identity_row($pdf, 'Nama Lengkap', $k['pembuat'], $start_x, $label_w, $sep_w);

// 2. NIP (Fetch from u.nip if available, need to ensure query selects it)
// We need to check if 'nip' is in the SELECT. It currently isn't in my previous write.
// I will rely on $k['nip'] if I update the query, or skip if not.
// Use 'created_by' user's NIP.
// Check if $k has 'user_nip'. If not, we might need a separate query or update the main one.
// For now, I'll update the main query in the next step, but here I'll try to use it.
if (!empty($k['user_nip'])) {
    print_identity_row($pdf, 'NIP', $k['user_nip'], $start_x, $label_w, $sep_w);
}

// 3. Unit Kerja
print_identity_row($pdf, 'Unit Kerja', $k['nama_bidang'], $start_x, $label_w, $sep_w);

// 4. Tanggal
print_identity_row($pdf, 'Tanggal', format_tanggal_indonesia($k['tanggal']), $start_x, $label_w, $sep_w);

$pdf->Ln(5);

// === TABLE HEADER ===
// Adjusted widths for better fit
$w_no = 10;
$w_waktu = 30; // Widened from 25
$w_uraian = 60;
$w_lokasi = 30; // Reduced from 35
$w_dok = 50;
// Total = 10 + 30 + 60 + 30 + 50 = 180 (Fits in A4/F4 margins)

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell($w_no, 10, 'NO', 1, 0, 'C', true);
$pdf->Cell($w_waktu, 10, 'WAKTU', 1, 0, 'C', true);
$pdf->Cell($w_uraian, 10, 'URAIAN KEGIATAN', 1, 0, 'C', true);
$pdf->Cell($w_lokasi, 10, 'LOKASI', 1, 0, 'C', true);
$pdf->Cell($w_dok, 10, 'DOKUMENTASI', 1, 1, 'C', true);

// === CONTENT ROW ===
$pdf->SetFont('Arial', '', 10);

$x = $pdf->GetX();
$y = $pdf->GetY();
$no = 1;

// Prepare Content Text
$text_uraian = $k['judul'] . "\n\n" . ($k['deskripsi'] ?: '-');
if ($k['tipe_kegiatan'] === 'monev') {
    $text_uraian .= "\n\n[MONEV]\nTemuan: " . ($k['temuan']?:'-') . "\nSaran: " . ($k['saran_rekomendasi']?:'-');
}

// Calculate Heights
$lines_uraian = $pdf->NbLines($w_uraian, $text_uraian);
$h_line = 5;
$h_text = max(10, $lines_uraian * $h_line + 4); 
$h_image = 35; // Fixed height for image in table
$row_h = max($h_text, 40);

// Determine valid image
$foto_file = null;
if (!empty($fotos) && file_exists('uploads/foto/' . $fotos[0]['file'])) {
    $foto_file = 'uploads/foto/' . $fotos[0]['file'];
}

// Check Page Break
if ($y + $row_h > $pdf->GetPageHeight() - 50) { 
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

// 1. No
$pdf->SetXY($x, $y);
$pdf->Cell($w_no, $row_h, '', 1, 0);
$pdf->Text($x + ($w_no/2) - 2, $y + ($row_h/2) + 1, $no);

// 2. Waktu
$pdf->SetXY($x + $w_no, $y);
$pdf->Cell($w_waktu, $row_h, '', 1, 0);
$tgl_str = date('d/m/y', strtotime($k['tanggal']));

$wkt_str = format_wib($k['created_at'], true); // Set true to convert UTC to WIB
if (isset($k['jam_mulai']) && !empty($k['jam_mulai'])) {
    $wkt_str = format_wib($k['jam_mulai'], false); // Set false for TIME type
    if (isset($k['jam_selesai']) && !empty($k['jam_selesai'])) {
        $wkt_str .= '-' . format_wib($k['jam_selesai'], false);
    }
}
// Manually position Date and Time lines
$pdf->Text($x + $w_no + 3, $y + ($row_h/2) - 3, $tgl_str);
$pdf->Text($x + $w_no + 3, $y + ($row_h/2) + 3, $wkt_str . " WIB");

// 3. Uraian
$pdf->SetXY($x + $w_no + $w_waktu, $y);
$pdf->MultiCell($w_uraian, $h_line, "\n" . $text_uraian, 0, 'L');
$pdf->SetXY($x + $w_no + $w_waktu, $y);
$pdf->Cell($w_uraian, $row_h, '', 1, 0);

// 4. Lokasi
$pdf->SetXY($x + $w_no + $w_waktu + $w_uraian, $y);
$pdf->Cell($w_lokasi, $row_h, '', 1, 0); 
// MultiCell for Location to wrap
$pdf->SetXY($x + $w_no + $w_waktu + $w_uraian, $y + ($row_h/2) - 5);
$pdf->MultiCell($w_lokasi, 5, $k['lokasi'], 0, 'C');
$pdf->SetXY($x + $w_no + $w_waktu + $w_uraian, $y); // Reset pos

// 5. Dokumentasi
$pdf->SetXY($x + $w_no + $w_waktu + $w_uraian + $w_lokasi, $y);
$pdf->Cell($w_dok, $row_h, '', 1, 0);

if ($foto_file) {
    $img_w_avail = $w_dok - 6;
    $img_h_avail = $h_image;
    $x_img = $x + $w_no + $w_waktu + $w_uraian + $w_lokasi + 3;
    $y_img = $y + ($row_h - $img_h_avail) / 2;
    // Check if valid image
    $type = get_fpdf_image_type($foto_file);
    if ($type) {
        $pdf->Image($foto_file, $x_img, $y_img, $img_w_avail, $img_h_avail);
    }
} else {
     $pdf->Text($x + $w_no + $w_waktu + $w_uraian + $w_lokasi + 15, $y + ($row_h/2), '(No Image)');
}

$pdf->Ln($row_h);

// === SIGNATURE (Bottom Anchor) ===
$marg_bottom = 30; // 3cm bottom margin
$sig_height = 60; // Height of signature block
$page_h = $pdf->GetPageHeight();
$y_bottom_sig = $page_h - $marg_bottom - $sig_height;

// If current Y is overlapping with signature area, add page
if ($pdf->GetY() > $y_bottom_sig) {
    $pdf->AddPage();
}

// Force SetY to bottom
$pdf->SetY($y_bottom_sig);

$right_w = 80;
$x_block = $pdf->GetPageWidth() - $pdf->GetRMargin() - $right_w;

// 1. Tempat & Tanggal
$pdf->SetX($x_block);
$pdf->Cell($right_w, 6, 'Besuk, ' . format_tanggal_indonesia($k['tanggal'], false), 0, 1, 'C');

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
    // Remove (IV/b) etc.
    $golongan_clean = preg_replace('/\s*\(.*\)/', '', $kop['golongan_ttd']);
    $pdf->Cell($right_w, 6, $golongan_clean, 0, 1, 'C');
}

// 7. NIP
$pdf->SetX($x_block);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell($right_w, 6, 'NIP. ' . $kop['nip_camat'], 0, 0, 'C');

$pdf->Output('I', 'Laporan_' . preg_replace('/[^a-zA-Z0-9]/', '_', $k['judul']) . '.pdf');
?>

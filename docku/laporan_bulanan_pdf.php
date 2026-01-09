<?php
// laporan_bulanan_pdf.php - Generate Monthly Report PDF
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable errors

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_once 'vendor/fpdf/fpdf.php';
require_once 'includes/pdf_base.php';
require_role(['admin', 'pimpinan', 'staff', 'operator']);

// 1. Get Parameters
$bulan = $_GET['bulan'] ?? date('n');
$tahun = $_GET['tahun'] ?? date('Y');
$bidang_id = $_GET['bidang_id'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$p_user_id = $_GET['p_user_id'] ?? '';

// 2. Build Query
$sql = "SELECT k.*, b.nama_bidang, u.nama as pembuat
        FROM kegiatan k
        JOIN bidang b ON k.bidang_id = b.id
        JOIN users u ON k.created_by = u.id
        WHERE MONTH(k.tanggal) = ? AND YEAR(k.tanggal) = ?";
$params = [$bulan, $tahun];

// Role-based filtering + Parameter filter
if (!has_role(['admin', 'pimpinan', 'operator'])) {
    $sql .= " AND k.created_by = ?";
    $params[] = $_SESSION['user_id'];
} elseif ($p_user_id) {
    $sql .= " AND k.created_by = ?";
    $params[] = $p_user_id;
}

if ($bidang_id) {
    $sql .= " AND k.bidang_id = ?";
    $params[] = $bidang_id;
}
if ($kategori) {
    $sql .= " AND k.kategori = ?";
    $params[] = $kategori;
}
$sql .= " ORDER BY k.tanggal ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Get bidang name if filtered
$bidang_name = "Seluruh Kecamatan";
if ($bidang_id) {
    $stmt = $pdo->prepare("SELECT nama_bidang FROM bidang WHERE id = ?");
    $stmt->execute([$bidang_id]);
    $b = $stmt->fetch();
    if ($b) $bidang_name = $b['nama_bidang'];
}

// Calculate statistics
$total_kegiatan = count($activities);
$stats_by_cat = [];
$stats_by_bidang = [];
$stats_by_type = [
    'biasa' => 0,
    'rapat' => 0,
    'pengaduan' => 0,
    'monev' => 0
];

foreach ($activities as $k) {
    $cat = $k['kategori'] ?: 'Tanpa Kategori';
    if (!isset($stats_by_cat[$cat])) {
        $stats_by_cat[$cat] = 0;
    }
    $stats_by_cat[$cat]++;
    
    if (!isset($stats_by_bidang[$k['nama_bidang']])) {
        $stats_by_bidang[$k['nama_bidang']] = 0;
    }
    $stats_by_bidang[$k['nama_bidang']]++;
    
    // Count Types (Fix)
    $type = $k['tipe_kegiatan'] ?? 'biasa';
    if (isset($stats_by_type[$type])) {
        $stats_by_type[$type]++;
    } else {
        $stats_by_type['biasa']++;
    }
}

// Fetch kop surat
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

// Month names
$months = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", 
          "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
$month_name = $months[$bulan];

// 1. Get Orientation & Size from parameters
$orient = $_GET['orient'] ?? 'P';
$size = $_GET['size'] ?? 'F4';

class PDF extends PDF_Base {
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Laporan Bulanan / ' . date('d/m/Y H:i') . ' - Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
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
$pdf->Cell(0, 10, 'LAPORAN KEGIATAN BULANAN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, strtoupper($month_name) . ' ' . $tahun, 0, 1, 'C');
$pdf->Ln(10);

// Info block
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(50, 7, 'Periode Laporan', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->Cell(0, 7, $month_name . ' ' . $tahun, 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(50, 7, 'Cakupan', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->Cell(0, 7, $bidang_name, 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(50, 7, 'Total Kegiatan', 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->Cell(0, 7, $total_kegiatan . ' kegiatan terdokumentasi', 0, 1);

$pdf->Ln(5);

// Statistics Section
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'I. REKAPITULASI STATISTIK', 0, 1, 'L');
$pdf->Ln(2);

// Statistics by category
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetX($pdf->GetLMargin() + 5);
$pdf->Cell(0, 7, 'Distribusi Per Kategori Tugas:', 0, 1);
$pdf->SetFont('Arial', '', 11);
foreach ($stats_by_cat as $cat => $count) {
    $pdf->SetX($pdf->GetLMargin() + 10);
    $pdf->Cell(70, 7, $cat, 0, 0);
    $pdf->Cell(5, 7, ':', 0, 0);
    $pdf->Cell(0, 7, $count . ' kegiatan', 0, 1);
}

// Statistics by bidang (if all bidang shown)
if (!$bidang_id && !empty($stats_by_bidang)) {
    $pdf->Ln(3);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetX($pdf->GetLMargin() + 5);
    $pdf->Cell(0, 7, 'Distribusi Per Bidang:', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    
    foreach ($stats_by_bidang as $bidang => $count) {
        $pdf->SetX($pdf->GetLMargin() + 10);
        $pdf->Cell(70, 6, $bidang, 0, 0);
        $pdf->Cell(5, 6, ':', 0, 0);
        $pdf->Cell(0, 6, $count . ' kegiatan', 0, 1);
    }
}

$pdf->Ln(8);

// Activity List
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'II. DAFTAR KEGIATAN', 0, 1, 'L');
$pdf->Ln(2);

if (empty($activities)) {
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->Cell(0, 7, 'Tidak ada kegiatan terdokumentasi pada periode ini.', 0, 1);
} else {
    // 1. Define Column Widths based on Orientation
    $full_w = $pdf->GetPageWidth() - $pdf->GetLMargin() - $pdf->GetRMargin();
    if ($orient === 'P') {
        $w = [10, 30, 40, 0]; // 0 means fill remaining
        $w[3] = $full_w - array_sum(array_slice($w, 0, 3));
    } else {
        $w = [10, 40, 60, 0];
        $w[3] = $full_w - array_sum(array_slice($w, 0, 3));
    }

    // 2. Table Header
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell($w[0], 10, 'NO', 1, 0, 'C', true);
    $pdf->Cell($w[1], 10, 'TANGGAL', 1, 0, 'C', true);
    $pdf->Cell($w[2], 10, 'BIDANG', 1, 0, 'C', true);
    $pdf->Cell($w[3], 10, 'NAMA KEGIATAN & DESKRIPSI', 1, 1, 'C', true);

    // 3. Table Content
    $pdf->SetFont('Arial', '', 9);
    $no = 1;
    foreach ($activities as $k) {
        $tgl_indo = date('d/m/Y', strtotime($k['tanggal']));
        $judul = $k['judul'];
        if ($k['deskripsi']) {
            $judul .= "\n\nKeterangan: " . $k['deskripsi'];
        }
        
        // Calculate height for multi-cell
        $nb = $pdf->NbLines($w[3], $judul);
        $h = 7 * $nb;
        if ($h < 10) $h = 10;

        // Check page break
        if ($pdf->GetY() + $h > ($pdf->GetPageHeight() - 30)) {
            $pdf->AddPage($orient);
            // Redraw Header
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell($w[0], 10, 'NO', 1, 0, 'C', true);
            $pdf->Cell($w[1], 10, 'TANGGAL', 1, 0, 'C', true);
            $pdf->Cell($w[2], 10, 'BIDANG', 1, 0, 'C', true);
            $pdf->Cell($w[3], 10, 'NAMA KEGIATAN & DESKRIPSI', 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 9);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Rect($x, $y, $w[0], $h);
        $pdf->Cell($w[0], $h, $no . '.', 0, 0, 'C');
        
        $pdf->Rect($x + $w[0], $y, $w[1], $h);
        $pdf->Cell($w[1], $h, $tgl_indo, 0, 0, 'C');
        
        $pdf->Rect($x + $w[0] + $w[1], $y, $w[2], $h);
        $pdf->SetXY($x + $w[0] + $w[1] + 1, $y + 2);
        $pdf->MultiCell($w[2]-2, 5, $k['nama_bidang'], 0, 'L');
        
        $pdf->SetXY($x + $w[0] + $w[1] + $w[2], $y);
        $pdf->Rect($x + $w[0] + $w[1] + $w[2], $y, $w[3], $h);
        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + 1, $y + 2);
        $pdf->MultiCell($w[3]-2, 5, $judul, 0, 'J');
        
        $pdf->SetXY($x, $y + $h);
        $no++;
    }
}

// Summary Narrative
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'III. NARASI SINGKAT', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 11);
$pdf->SetX($pdf->GetLMargin() + 10);

$narasi = "Pada bulan {$month_name} {$tahun}, {$bidang_name} telah melaksanakan dan mendokumentasikan {$total_kegiatan} kegiatan. ";
$narasi .= "Terdiri dari {$stats_by_type['biasa']} kegiatan dokumentasi, {$stats_by_type['rapat']} notulen rapat, dan {$stats_by_type['pengaduan']} laporan pengaduan masyarakat. ";
$narasi .= "Seluruh kegiatan telah didokumentasikan dengan bukti foto dan laporan terstruktur sesuai standar tata kelola pemerintahan.";

$pdf->MultiCell(0, 7, $narasi, 0, 'J');

// Signature
if ($pdf->GetY() > ($pdf->GetPageHeight() - 80)) $pdf->AddPage($orient);

if ($orient === 'P') {
    $pdf->SetMargins(40, 30, 30);
} else {
    $pdf->SetMargins(30, 40, 30);
}

$pdf->SetY($pdf->GetPageHeight() - 70);
$pdf->SetFont('Arial', '', 11);

$right_w = 75;
$pdf->SetX($pdf->GetPageWidth() - $pdf->GetRMargin() - $right_w);
$pdf->Cell($right_w, 6, 'Besuk, ' . date('d') . ' ' . $months[date('n')] . ' ' . date('Y'), 0, 1, 'L');
$pdf->SetX($pdf->GetPageWidth() - $pdf->GetRMargin() - $right_w);
$pdf->Cell($right_w, 6, $kop['jabatan_ttd'] ?? 'Camat Besuk', 0, 1, 'L');
$pdf->Ln(20);
$pdf->SetX($pdf->GetPageWidth() - $pdf->GetRMargin() - $right_w);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell($right_w, 6, strtoupper($kop['nama_camat']), 0, 1, 'L');
if (!empty($kop['golongan_ttd'])) {
    $pdf->SetFont('Arial', '', 11);
    // Remove (IV/b) etc.
    $golongan_clean = preg_replace('/\s*\(.*\)/', '', $kop['golongan_ttd']);
    $pdf->Cell($right_w, 6, $golongan_clean, 0, 1, 'L');
}
$pdf->SetX($pdf->GetPageWidth() - $pdf->GetRMargin() - $right_w);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell($right_w, 6, 'NIP. ' . $kop['nip_camat'], 0, 0, 'L');

$filename = 'Laporan_Bulanan_' . $month_name . '_' . $tahun . '.pdf';
$pdf->Output('I', $filename);
?>

<?php
// includes/pdf_base.php
require_once 'vendor/fpdf/fpdf.php';
require_once 'includes/helpers.php';

class PDF_Base extends FPDF {
    public $kop;
    
    function __construct($orientation='P', $unit='mm', $size='F4') {
        if ($size == 'F4') $size = [210, 330];
        parent::__construct($orientation, $unit, $size);
    }

    function setKop($kop) {
        $this->kop = $kop;
    }

    function Header() {
        if ($this->PageNo() == 1) {
            // Determine Logo Path
            $logoPath = 'assets/img/logo.png'; // default
            if (!empty($this->kop['logo']) && file_exists('assets/img/' . $this->kop['logo'])) {
                $logoPath = 'assets/img/' . $this->kop['logo'];
            }

            if (file_exists($logoPath)) {
                $this->Image($logoPath, $this->GetLMargin(), $this->tMargin - 10, 18);
            }

            $this->SetY($this->tMargin - 10);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 6, strtoupper($this->kop['nama_instansi_1'] ?? 'PEMERINTAH KABUPATEN PROBOLINGGO'), 0, 1, 'C');
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 7, strtoupper($this->kop['nama_instansi_2'] ?? 'KECAMATAN BESUK'), 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, $this->kop['alamat_1'] ?? 'Jalan Raya Besuk No. 1, Besuk, Probolinggo', 0, 1, 'C');
            $this->Cell(0, 4, $this->kop['alamat_2'] ?? 'Email: kecamatan.besuk@probolinggokab.go.id', 0, 1, 'C');
            
            $this->Ln(3);
            $currY = $this->GetY();
            $this->SetLineWidth(0.6);
            $this->Line($this->GetLMargin(), $currY, $this->GetPageWidth() - $this->GetRMargin(), $currY);
            $this->SetLineWidth(0.2);
            $this->Ln(10);
        }
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'BESUK SAE / Dokumen Dinas / ' . date('d/m/Y H:i') . ' - Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function GetW() { return $this->w; }
    function GetH() { return $this->h; }
    function GetLMargin() { return $this->lMargin; }
    function GetRMargin() { return $this->rMargin; }

    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb > 0 and $s[$nb-1] == "\n") $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i < $nb) {
            $c = $s[$i];
            if($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c == ' ') $sep = $i;
            $l += $cw[$c];
            if($l > $wmax) {
                if($sep == -1) {
                    if($i == $j) $i++;
                } else $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else $i++;
        }
        return $nl;
    }
}

function addInfoRow($pdf, $label, $value) {
    $w1 = ($pdf->GetPageWidth() - $pdf->GetLMargin() - $pdf->GetRMargin()) * 0.35;
    $w2 = 5;
    $w3 = ($pdf->GetPageWidth() - $pdf->GetLMargin() - $pdf->GetRMargin()) * 0.60;
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($w1, 8, $label, 0, 0);
    $pdf->Cell($w2, 8, ':', 0, 0);
    $pdf->MultiCell($w3, 8, $value ?: '-', 0, 'L');
}

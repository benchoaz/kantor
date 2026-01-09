<?php
// api_ocr.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_auth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Validate file
    $allowed = ['pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung (Gunakan PDF Surat Asli)']);
        exit;
    }

    // Temporary upload for processing
    $temp_dir = 'uploads/tmp_ocr/';
    if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);
    
    $temp_path = $temp_dir . 'ocr_' . time() . '.' . $ext;
    
    if (move_uploaded_file($file['tmp_name'], $temp_path)) {
        $extractedText = performOCR($temp_path);
        
        // Clean up temp file
        unlink($temp_path);
        
        // Pre-processing: Bersihkan teks dari spasi berlebih dan karakter aneh hasil OCR
        $cleanText = preg_replace('/\s+/', ' ', $extractedText); // Satukan spasi berlebih
        $cleanText = str_replace(';', ':', $cleanText); // Biasanya OCR salah baca ':' jadi ';'
        
        $no_surat = '';
        $perihal = '';

        // 1. Deteksi Nomor Surat (Gunakan variasi regex yang lebih luas)
        $patterns_no = [
            '/Nomor\s*[:\.]\s*([0-9\.\-\/A-Z]+)/i', // Prioritaskan pola angka/kode rapat tanpa spasi banyak
            '/No\s*[:\.]\s*([0-9\.\-\/A-Z]+)/i',
            '/Nomor\s*[:\.]\s*([A-Z0-9.\-\/ ]+)/i', // Fallback longgar
            '/No\s*[:\.]\s*([A-Z0-9.\-\/ ]+)/i'
        ];

        foreach ($patterns_no as $pattern) {
            if (preg_match($pattern, $cleanText, $matches)) {
                $candidate = trim($matches[1]);
                // Validasi: Abaikan jika terlihat seperti alamat atau nomor telepon
                if (stripos($candidate, 'Telp') !== false || stripos($candidate, 'Fax') !== false || stripos($candidate, 'Jalan') !== false || stripos($candidate, 'Kecamatan') !== false) {
                    continue;
                }
                
                $no_surat = $candidate;
                // Bersihkan kata-kata pengganggu di akhir nomor
                $no_surat = preg_replace('/\s+(Sifat|Lampiran|Hal|Perihal).*$/i', '', $no_surat);
                break;
            }
        }

        // 2. Deteksi Perihal
        $patterns_hal = [
            '/Perihal\s*[:\.]\s*([^\n\r:]+)/i',
            '/Hal\s*[:\.]\s*([^\n\r:]+)/i'
        ];

        foreach ($patterns_hal as $pattern) {
            if (preg_match($pattern, $cleanText, $matches)) {
                $perihal = trim($matches[1]);
                // Bersihkan jika perihal terambil sampai ke bagian lain
                $perihal = preg_replace('/\s+(Kepada|Yth|Di|Menindaklanjuti|Dengan).*$/i', '', $perihal);
                $perihal = substr($perihal, 0, 150);
                break;
            }
        }

        // FIX: Cross-Check / Swap Logic (Jika Perihal isinya Angka, dan No Surat salah/kosong)
        // Definisi polana nomor surat yang kuat (biasanya ada angka, garing, atau titik)
        $is_perihal_numeric = preg_match('/[0-9]{3,}[\/.][0-9A-Z\/.]+/', $perihal);
        $is_no_surat_valid = !empty($no_surat) && preg_match('/[0-9\/]/', $no_surat) && strlen($no_surat) > 3;

        // KASUS 1: Perihal malah isinya Nomor Surat (User Complaint)
        if ($is_perihal_numeric && !$is_no_surat_valid) {
            $no_surat = $perihal; // Pindahkan Perihal ke No Surat
            $perihal = ''; // Reset Perihal
        }

        // KASUS 2: Perihal Masih Kosong atau tadi di-reset -> Coba cari lagi
        if (empty($perihal) || strlen($perihal) < 3) {
            // Coba cari keyword umum surat dinas
            $keywords = 'Undangan|Rapat|Permohonan|Pemberitahuan|Laporan|Keputusan|Perintah|Tugas|Edaran|Nota';
            
            // 1. Cari baris yang diawali keyword tersebut (Case insensitive) di TEXT ASLI (ada newlines)
            // Gunakan $extractedText agar ^ dan $ mencocokkan per baris
            if (preg_match('/^[\s\t]*('.$keywords.').*$/mi', $extractedText, $match_hal)) {
                $candidate = trim($match_hal[0]);
                // Bersihkan prefix jika ada (Misal: "Perihal : Undangan...")
                $candidate = preg_replace('/^(Perihal|Hal|Sifat)\s*[:\.]\s*/i', '', $candidate);
                // Batasi panjang max 150 char biar ga ambil sampah
                $perihal = substr($candidate, 0, 150);
            }
            // 2. Jika tidak ketemu, cari secara longgar di cleanText tapi batasi panjang
            elseif (preg_match('/('.$keywords.')\s+[^:\n\r]{5,50}/i', $cleanText, $match_hal)) {
                $perihal = trim($match_hal[0]);
            }
        }

        // 3. Deteksi Tujuan (Kepada / Yth)
        $tujuan_surat = '';
        $patterns_tujuan = [
            '/(?:Kepada|Yth)\.?\s+(?:Bapak\/Ibu\s+)?([^,\r\n]+)/i',
            '/Kepada\s+:\s*([^\r\n]+)/i'
        ];

        foreach ($patterns_tujuan as $pattern) {
            if (preg_match($pattern, $extractedText, $matches)) {
                $tujuan_surat = trim($matches[1]);
                $tujuan_surat = ltrim($tujuan_surat, ":. ");
                break;
            }
        }

        // 4. Deteksi Asal Surat (Coba ambil baris awal/Kop)
        $asal_surat = '';
        $lines = explode("\n", $extractedText);
        if (count($lines) > 0) {
            $header = trim($lines[0] . ' ' . ($lines[1] ?? ''));
            if (strlen($header) > 5) {
                $asal_surat = preg_replace('/PEMERINTAH KABUPATEN [A-Z]+/i', '', $header);
                $asal_surat = trim($asal_surat, " \t\n\r\0\x0B,.");
            }
        }

        // 5. Deteksi Tanggal
        $tgl_surat = '';
        if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i', $extractedText, $matches)) {
            $list_bulan = ['Januari'=>'01', 'Februari'=>'02', 'Maret'=>'03', 'April'=>'04', 'Mei'=>'05', 'Juni'=>'06', 'Juli'=>'07', 'Agustus'=>'08', 'September'=>'09', 'Oktober'=>'10', 'November'=>'11', 'Desember'=>'12'];
            $bln = $list_bulan[ucfirst(strtolower($matches[2]))];
            $tgl_surat = $matches[3] . '-' . $bln . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        } elseif (preg_match('/(\d{2})[-\/](\d{2})[-\/](\d{4})/', $cleanText, $matches)) {
            $tgl_surat = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        echo json_encode([
            'status' => 'success',
            'no_surat' => $no_surat,
            'perihal' => $perihal,
            'asal_surat' => $asal_surat,
            'tujuan_surat' => $tujuan_surat,
            'tgl_surat' => $tgl_surat,
            'raw_text' => $extractedText
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file sementara']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Request tidak valid']);
}

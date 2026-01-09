<?php
// includes/helpers.php
date_default_timezone_set('Asia/Jakarta');

/**
 * Detect image type and return FPDF compatible type string
 */
function get_fpdf_image_type($path) {
    if (!file_exists($path)) return false;
    $info = getimagesize($path);
    if (!$info) return false;
    
    switch ($info[2]) {
        case IMAGETYPE_JPEG: return 'JPEG';
        case IMAGETYPE_PNG:  return 'PNG';
        case IMAGETYPE_GIF:  return 'GIF';
        default: return false;
    }
}

/**
 * Handle multiple file uploads with duplicate check
 */
function handle_uploads($files, $pdo, $kegiatan_id, &$skipped_files, $user_id = null, $keterangans = []) {
    $upload_dir = 'uploads/foto/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    foreach ($files['tmp_name'] as $key => $tmp_name) {
        if (empty($tmp_name)) continue;
        
        $file_name = $files['name'][$key];
        $file_error = $files['error'][$key];
        $keterangan = $keterangans[$key] ?? null;

        if ($file_error === 0) {
            $file_hash = md5_file($tmp_name);
            
            // Duplicate Check
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM foto_kegiatan WHERE file_hash = ?");
            $stmt->execute([$file_hash]);
            if ($stmt->fetchColumn() > 0) {
                $skipped_files[] = $file_name . " (Sudah ada)";
                continue;
            }

            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_name = 'DOC_' . date('YmdHis') . '_' . uniqid() . '.' . $ext;
            $target = $upload_dir . $new_name;

            if (move_uploaded_file($tmp_name, $target)) {
                $stmt = $pdo->prepare("INSERT INTO foto_kegiatan (kegiatan_id, user_id, file, file_hash, keterangan) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$kegiatan_id, $user_id, $new_name, $file_hash, $keterangan]);
            }
        }
    }
}

/**
 * Get Indonesian month name from number (1-12)
 */
function get_bulan_indo($m) {
    $bln = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $bln[(int)$m];
}

/**
 * Format date to Indonesian format
 * Example: 2025-12-30 -> "Selasa, 30 Desember 2025"
 */
function format_tanggal_indonesia($date, $include_day = true) {
    if (empty($date)) return '-';
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    
    $timestamp = strtotime($date);
    $hari_nama = $hari[date('w', $timestamp)];
    $tanggal = date('d', $timestamp);
    $bulan_nama = get_bulan_indo(date('n', $timestamp));
    $tahun = date('Y', $timestamp);
    
    return $include_day ? "$hari_nama, $tanggal $bulan_nama $tahun" : "$tanggal $bulan_nama $tahun";
}

/**
 * Generate BKN text for e-Kinerja report
 * Format: "Melaksanakan kegiatan {output_kinerja} pada {date in Indonesian}."
 */
function generate_teks_bkn($judul_kegiatan, $tanggal, $output_kinerja_nama) {
    $tanggal_indo = format_tanggal_indonesia($tanggal);
    return "Melaksanakan kegiatan {$output_kinerja_nama} pada {$tanggal_indo}.";
}
/**
 * Format waktu ke WIB (Konversi UTC ke Asia/Jakarta jika timestamp penuh)
 * @param string $time_str
 * @param bool $is_timestamp Set true if this is for created_at (UTC from DB)
 */
function format_wib($time_str, $is_timestamp = false) {
    if (empty($time_str) || $time_str == '00:00:00') return '-';
    
    // Jika is_timestamp = true, asumsikan string dari DB adalah UTC
    // Gunakan strtotime dengan suffix UTC untuk memaksa konversi ke timezone script (Asia/Jakarta)
    if ($is_timestamp) {
        $timestamp = strtotime($time_str . " UTC");
        return date('H:i', $timestamp);
    }
    
    // Untuk jam_mulai/jam_selesai (TIME column), asumsikan sudah local time
    return date('H:i', strtotime($time_str));
}

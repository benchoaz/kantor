<?php
// includes/ekinerja_helpers.php
// Helper functions for e-Kinerja compliance system
// Basis: PP 30/2019, PermenPANRB 6/2022

/**
 * Get jabatan level dari jabatan lengkap user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return string 'staf'|'kasi'|'sekcam'|'camat'|null
 */
function get_user_jabatan_level($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT jabatan FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['jabatan']) {
        return 'staf'; // Default to staf if no jabatan set
    }
    
    $jabatan = strtolower($user['jabatan']);
    
    // Mapping jabatan → level
    if (strpos($jabatan, 'camat') !== false && strpos($jabatan, 'sekretaris') === false) {
        return 'camat';
    }
    
    if (strpos($jabatan, 'sekretaris camat') !== false || strpos($jabatan, 'sekcam') !== false) {
        return 'sekcam';
    }
    
    if (strpos($jabatan, 'kasi') !== false || 
        strpos($jabatan, 'kepala seksi') !== false ||
        strpos($jabatan, 'kasubbag') !== false) {
        return 'kasi';
    }
    
    // Staf dan lainnya
    return 'staf';
}

/**
 * Get output templates by jabatan level
 * 
 * @param PDO $pdo Database connection
 * @param string $level_jabatan 'staf'|'kasi'|'sekcam'|'camat'
 * @param int|null $bidang_id Optional bidang filter
 * @return array List of output templates
 */
function get_output_by_jabatan($pdo, $level_jabatan, $bidang_id = null) {
    $sql = "SELECT * FROM output_kinerja 
            WHERE level_jabatan = ? AND is_active = 1";
    $params = [$level_jabatan];
    
    if ($bidang_id) {
        $sql .= " AND (bidang_id = ? OR bidang_id IS NULL)";
        $params[] = $bidang_id;
    } else {
        $sql .= " AND bidang_id IS NULL"; // General templates only
    }
    
    $sql .= " ORDER BY nama_output";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Validate output compliance dengan jabatan
 * 
 * @param string $output_text Output text
 * @param string $jabatan_level User's jabatan level
 * @return array ['valid' => bool, 'message' => string]
 */
function validate_output_compliance($output_text, $jabatan_level) {
    $output_lower = strtolower($output_text);
    
    // Kata terlarang untuk semua jabatan
    $forbidden_all = ['optimal', 'maksimal', 'luar biasa', 'signifikan'];
    
    foreach ($forbidden_all as $word) {
        if (strpos($output_lower, $word) !== false) {
            return [
                'valid' => false,
                'message' => "Kata '$word' tidak diperbolehkan dalam output e-Kinerja (PermenPANRB 6/2022)"
            ];
        }
    }
    
    // Validasi kata kerja sesuai jabatan
    $level_keywords = [
        'staf' => ['terlaksananya dokumentasi', 'tersusunnya', 'terdokumentasinya', 'terinputnya'],
        'kasi' => ['terlaksananya koordinasi', 'terkendalinya', 'terfasilitasinya'],
        'sekcam' => ['tersinkronisasinya', 'terfasilitasinya', 'terlaksananya fasilitasi'],
        'camat' => ['terlaksananya pembinaan', 'ditetapkannya', 'terlaksananya pengendalian']
    ];
    
    // Check if output contains at least one valid keyword for the level
    $valid_keywords = $level_keywords[$jabatan_level] ?? [];
    $has_valid_keyword = false;
    
    foreach ($valid_keywords as $keyword) {
        if (strpos($output_lower, $keyword) !== false) {
            $has_valid_keyword = true;
            break;
        }
    }
    
    if (!$has_valid_keyword) {
        return [
            'valid' => false,
            'message' => "Output tidak sesuai dengan level jabatan $jabatan_level. Gunakan template yang disediakan."
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'Output sesuai compliance PP 30/2019'
    ];
}

/**
 * Get jabatan level label (human readable)
 * 
 * @param string $level 'staf'|'kasi'|'sekcam'|'camat'
 * @return string
 */
function get_jabatan_level_label($level) {
    $labels = [
        'staf' => 'Staf / Pelaksana',
        'kasi' => 'Kepala Seksi / Kasubbag',
        'sekcam' => 'Sekretaris Camat',
        'camat' => 'Camat'
    ];
    
    return $labels[$level] ?? 'Tidak Diketahui';
}

/**
 * Get compliance badge HTML
 * 
 * @param boolean $is_compliance Whether output is compliance
 * @return string HTML badge
 */
function get_compliance_badge($is_compliance = true) {
    if ($is_compliance) {
        return '<span class="badge bg-success"><i class="bi bi-patch-check"></i> Compliance PP 30/2019</span>';
    } else {
        return '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Non-Compliance</span>';
    }
}

/**
 * Format output dengan highlight kata kerja
 * 
 * @param string $output_text Output text
 * @return string HTML formatted output
 */
function format_output_with_highlight($output_text) {
    // Highlight kata kerja hasil
    $keywords = [
        'Terlaksananya', 'Tersusunnya', 'Terdokumentasinya', 'Terinputnya',
        'Terkendalinya', 'Terfasilitasinya', 'Tersinkronisasinya', 'Ditetapkannya'
    ];
    
    $formatted = $output_text;
    foreach ($keywords as $keyword) {
        $formatted = str_replace($keyword, "<strong class='text-primary'>$keyword</strong>", $formatted);
    }
    
    return $formatted;
}
?>

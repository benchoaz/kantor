<?php
/**
 * get_address.php - Enhanced Reverse Geocoding
 * Menggunakan cURL untuk performa dan handling yang lebih baik.
 * Dioptimalkan untuk struktur wilayah di Indonesia.
 */
header('Content-Type: application/json');

$lat = $_GET['lat'] ?? '';
$lon = $_GET['lon'] ?? '';

if (!$lat || !$lon) {
    echo json_encode(['success' => false, 'message' => 'Coordinates missing']);
    exit;
}

// Nominatim URL (OpenStreetMap)
$url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$lat&lon=$lon&addressdetails=1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'BESUKSAE/1.1 (SidikSae Indonesia)');
curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error || $httpCode !== 200) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil alamat: ' . ($error ?: "HTTP $httpCode")]);
    exit;
}

$data = json_decode($response, true);
if (isset($data['address'])) {
    $addr = $data['address'];
    
    // 1. Desa / Kelurahan
    $desa = $addr['village'] ?? $addr['suburb'] ?? $addr['neighbourhood'] ?? $addr['hamlet'] ?? $addr['quarter'] ?? '';
    
    // 2. Kecamatan
    $kecamatan = $addr['city_district'] 
        ?? $addr['suburb'] 
        ?? $addr['municipality'] 
        ?? $addr['district'] 
        ?? '';
        
    // 3. Kabupaten / Kota
    $kabupaten = $addr['city'] 
        ?? $addr['state_district'] 
        ?? $addr['county'] 
        ?? $addr['regency'] 
        ?? '';

    // Clean up "Kabupaten" or "Kota" prefix if duplicate
    $kabupaten = preg_replace('/^(Kabupaten|Kota)\s+/i', '', $kabupaten);
    if (!empty($kabupaten)) {
        $kabupaten = (strpos($data['display_name'], 'Kota') !== false ? 'Kota ' : 'Kab. ') . $kabupaten;
    }

    $province = $addr['state'] ?? 'Jawa Timur';
    $postCode = $addr['postcode'] ?? '';
    
    // Format Alamat Lengkap untuk Display
    $formatted_address = "";
    if ($desa) $formatted_address .= "$desa, ";
    if ($kecamatan) $formatted_address .= "$kecamatan, ";
    if ($kabupaten) $formatted_address .= "$kabupaten, ";
    $formatted_address .= $province;

    // Generate Plus Code (Simulation for visual)
    $p1 = strtoupper(substr(md5($lat . $lon), 0, 4));
    $p2 = strtoupper(substr(md5($lon . $lat), 0, 3));
    $plusCode = $p1 . "+" . $p2;

    echo json_encode([
        'success' => true,
        'address' => $formatted_address,
        'display_name' => $data['display_name'],
        'details' => [
            'desa' => $desa ?: '-',
            'kecamatan' => $kecamatan ?: 'Besuk', // Default fallback
            'kabupaten' => $kabupaten ?: 'Probolinggo',
            'province' => $province,
            'postCode' => $postCode ?: '-',
            'plusCode' => $plusCode
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lokasi tidak ditemukan']);
}

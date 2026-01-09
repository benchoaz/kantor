<?php
// camera.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role(['admin', 'operator', 'pimpinan', 'staff']);

// Ambil informasi user untuk validasi barang bukti
$user_id = $_SESSION['user_id'];

// Try to get user with bidang, fallback to basic info if bidang table doesn't exist
try {
    $stmt = $pdo->prepare("
        SELECT u.username, u.nama, u.role, b.nama_bidang as bidang 
        FROM users u 
        LEFT JOIN bidang b ON u.bidang_id = b.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback: bidang table might not exist yet
    $stmt = $pdo->prepare("SELECT username, nama, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_data['bidang'] = null;
}

// Data user untuk JavaScript
$user_info = [
    'nama' => $user_data['nama'] ?? 'Unknown',
    'username' => $user_data['username'] ?? 'unknown',
    'role' => $user_data['role'] ?? 'user',
    'bidang' => $user_data['bidang'] ?? '-'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kamera Verifikasi Pro - BESUKSAE</title>
    <!-- Leaflet Map JS & CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Image Plugin for Map Screenshot -->
    <script src="https://cdn.jsdelivr.net/npm/leaflet-image@0.4.0/leaflet-image.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --glass-bg: rgba(0, 0, 0, 0.5);
            --glass-border: rgba(255, 255, 255, 0.2);
            --accent-color: #00BCD4; 
            --bg-gray: #f2f2f2;
        }
        body { background: #000; color: #fff; overflow: hidden; margin: 0; font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        
        /* Camera Container - Support auto-rotation */
        body { background: #000; color: #fff; overflow: hidden; margin: 0; font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        
        /* Global Shadow for Readability */
        .text-shadow { text-shadow: 0 1px 3px rgba(0,0,0,0.8); }
        #camera-container { 
            position: relative; 
            width: 100vw; 
            height: 100vh; 
            height: 100dvh; 
            background: #000; 
            overflow: hidden;
            z-index: 1;
        }
        
        video { 
            position: absolute;
            top: 0;
            left: 0;
            width: 100%; 
            height: 100%; 
            object-fit: cover;
            z-index: 5; /* Ensure video is at the bottom layer but still visible */
        }
        
        .front-camera { transform: scaleX(-1); }
        
        /* Top Navigation - Consolidation and inward shift */
        .top-nav { 
            position: absolute; 
            top: 30px; 
            left: 25px; 
            right: 25px; 
            display: flex; 
            justify-content: flex-end; 
            align-items: center; 
            z-index: 80; 
        }
        .top-icon { 
            font-size: 1.4rem; color: #fff; cursor: pointer; width: 48px; height: 48px; 
            display: flex; align-items: center; justify-content: center; 
            background: rgba(0,0,0,0.4); border-radius: 50%;
            backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Compass Overlay */
        .compass-overlay {
            position: absolute; top: 80px; left: 20px; width: 60px; height: 60px;
            background: rgba(0,0,0,0.3); border-radius: 50%; border: 2px solid rgba(255,255,255,0.5);
            display: flex; align-items: center; justify-content: center; z-index: 40;
        }
        .compass-needle { width: 4px; height: 40px; background: linear-gradient(to bottom, #f00 50%, #fff 50%); border-radius: 2px; transition: transform 0.1s linear; }

        /* Timemark Branding Header - More Prominent */
        .branding-header {
            position: absolute; top: 25px; left: 50%; transform: translateX(-50%);
            background: rgba(0,0,0,0.6); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            padding: 8px 25px; border-radius: 4px;
            border-top: 3px solid var(--accent-color);
            display: flex; flex-direction: column; align-items: center; z-index: 50;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            min-width: 140px;
        }
        #branding-text { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: 1px; text-transform: uppercase; }
        #branding-subtitle { font-size: 9px; font-weight: 600; color: var(--accent-color); text-transform: uppercase; margin-top: -2px; }

        /* Bottom Overlays (Map & Meta) - Adjusted for new bottom bar */
        .bottom-overlays {
            position: absolute; bottom: calc(140px + 15px + env(safe-area-inset-bottom)); left: 0; width: 100%;
            display: flex; padding: 0 15px; z-index: 50;
            pointer-events: none;
            justify-content: space-between;
            align-items: flex-end;
            transition: all 0.3s ease;
        }
        /* LIVE PREVIEW - Simplified (no box, transparent) */
        #live-meta {
            background: transparent;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            padding: 0;
            border-radius: 0;
            width: 260px;
            max-width: 260px;
            /* NO FIXED HEIGHT - auto menyesuaikan konten */
            pointer-events: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
            box-shadow: none;
            overflow: hidden;
        }
        
        /* Vertical Stack - Simple & Safe */
        #m-time {
            font-size: 44px;
            font-weight: 300;
            line-height: 1;
            color: #fff;
            text-shadow: 0 2px 6px rgba(0,0,0,0.95);
            letter-spacing: -0.5px;
            margin-bottom: 2px;
        }
        
        #m-date {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 1px 4px rgba(0,0,0,0.95);
            line-height: 1.2;
        }
        
        #m-day {
            font-size: 12px;
            font-weight: 400;
            color: rgba(255,255,255,0.9);
            text-shadow: 0 1px 3px rgba(0,0,0,0.95);
            line-height: 1.2;
            margin-bottom: 6px;
        }
        
        /* Address - multiline, auto-wrap, safe */
        #m-addr-wrapper {
            width: 100%;
            margin-top: 2px;
        }
        
        #m-addr {
            font-size: 11px;
            font-weight: 400;
            color: #eee;
            line-height: 1.3;
            text-shadow: 0 1px 3px rgba(0,0,0,0.95);
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            display: block;
        }
        
        /* GPS coordinates - safe, can wrap if needed */
        #m-gps-wrapper {
            width: 100%;
            margin-top: 3px;
        }
        
        #m-gps {
            font-size: 10px;
            font-weight: 400;
            color: rgba(255,255,255,0.7);
            line-height: 1.3;
            text-shadow: 0 1px 3px rgba(0,0,0,0.95);
            display: block;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        #l-branding-wrapper {
            margin-bottom: 8px;
            padding-left: 2px;
            pointer-events: none;
            display: flex;
            flex-direction: column; /* Stacked vertical */
            align-items: flex-start;
            gap: 0;
        }
        /* Sidebar/Quick Actions */
        .side-actions {
            position: absolute; right: 25px; top: 50%; transform: translateY(-50%);
            display: flex; flex-direction: column; gap: 20px; z-index: 80;
        }
        
        /* Grid Overlay */
        #grid-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: grid; grid-template-columns: 1fr 1fr 1fr; grid-template-rows: 1fr 1fr 1fr;
            pointer-events: none; z-index: 10; opacity: 0; transition: opacity 0.3s ease;
        }
        #grid-overlay div { border: 0.5px solid rgba(255,255,255,0.15); }
        #grid-overlay.show { opacity: 1; }

        /* Bottom Bar - White Theme per Reference */
        .bottom-bar {
            position: absolute; bottom: 0; left: 0; width: 100%;
            height: 140px; 
            background: #ffffff;  /* White background */
            /* No visible divider needed for white bar, or subtle one */
            /* border-top: 1px solid rgba(0,0,0,0.1); */
            
            display: flex; 
            flex-direction: column;
            justify-content: center; 
            align-items: center;
            padding: 0 20px;
            padding-bottom: env(safe-area-inset-bottom);
            z-index: 60;
            gap: 16px;
        }
        
        /* Control row - Balanced 3-column layout */
        .control-row {
            width: 100%;
            max-width: 450px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .control-item {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Shutter in center */
        .btn-shutter-wrapper {
            display: flex;
            justify-content: center;
            flex: 1;
        }
        

        
        /* Button containers */
        .btn-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-container:active {
            transform: scale(0.95);
        }
        
        /* Shutter button */
        /* Shutter button - Light Theme (Dark Ring) */
        .btn-shutter { 
            position: relative;
            width: 76px; height: 76px; 
            border-radius: 50%; 
            border: 4px solid #333; /* Dark border */
            padding: 0;
            cursor: pointer; 
            background: rgba(255,255,255,0.8);
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s ease;
        }
        .btn-shutter:active { transform: scale(0.92); }
        .btn-shutter-inner { 
            width: 60px; height: 60px; 
            background: #333; /* Dark inner circle */
            border-radius: 50%; 
            transition: 0.15s; 
        }
        .btn-shutter:active .btn-shutter-inner { transform: scale(0.85); background: #000; }

        /* Label below button - Dark Text for White Bar */
        .btn-label {
            font-size: 10px;
            color: #333; /* Dark text */
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            /* text-shadow: 0 2px 4px rgba(0,0,0,0.9); */ /* Remove shadow on light bg */
        }
        
        /* Side buttons - Light Theme */
        .btn-side {
            width: 56px; height: 56px; 
            border-radius: 50%; 
            border: 1px solid #ddd;
            background: #f8f9fa;
            color: #333;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-side i { 
            font-size: 1.4rem; 
            color: #333; /* Dark icon */
            filter: none;
        }

        /* Settings Overlay - List Style per Screenshot */
        #settings-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.8); z-index: 200; display: none; 
            align-items: center; justify-content: center; padding: 15px;
            opacity: 0; transition: opacity 0.3s ease;
        }
        #settings-overlay.show { display: flex; opacity: 1; }
        .settings-card { 
            background: #fff; color: #333; border-radius: 12px; 
            width: 100%; max-width: 380px; max-height: 90vh; 
            display: flex; flex-direction: column; overflow: hidden;
            transform: translateY(20px); transition: transform 0.3s ease;
        }
        #settings-overlay.show .settings-card { transform: translateY(0); }
        .settings-header { padding: 15px 20px; border-bottom: 2px solid var(--accent-color); display: flex; justify-content: space-between; align-items: center; }
        .settings-header h4 { margin: 0; color: var(--accent-color); font-weight: 700; }
        
        .settings-body { flex: 1; overflow-y: auto; background: var(--bg-gray); }
        .settings-list-item { 
            background: #fff; padding: 12px 20px; margin-bottom: 1px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #eee;
        }
        .settings-list-item label { margin: 0; font-size: 14px; font-weight: 500; flex: 1; }
        .settings-list-item select { border: none; background: none; text-align: right; color: #666; font-size: 14px; cursor: pointer; outline: none; }
        
        /* Toggle/Switch Style */
        .form-switch .form-check-input { width: 40px; height: 20px; cursor: pointer; }
        
        .settings-preview-area { background: #fff; padding: 15px 20px; margin: 10px 0; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; }
        .preview-text-line { font-size: 13px; color: #777; margin-bottom: 3px; border-bottom: 1px solid #f2f2f2; padding-bottom: 2px; }
        
        .settings-footer { padding: 10px 20px; background: #fff; display: flex; justify-content: space-between; gap: 10px; }
        .btn-restore { border: none; background: none; color: #333; font-weight: 600; padding: 10px; }
        .btn-ok { background: none; border: none; color: #333; font-weight: 700; padding: 10px; }

        /* Preview full screen - Premium Blur Entry */
        #preview-container { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            background: #000; display: none; z-index: 200;
            opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            flex-direction: column;
        }
        #preview-container.show { display: flex; opacity: 1; }
        #preview-img { 
            width: 100%; 
            height: calc(100% - 140px); /* Ends exactly at action bar */
            object-fit: contain; 
            background: #000;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
            border-radius: 0;
        }
        
        /* Preview overlays - Timemark Style Side Stack */
        .preview-overlays {
            position: absolute; 
            bottom: calc(140px + 15px); /* Positioned relative to photo boundary */
            left: 15px; right: 15px;
            display: flex; align-items: flex-end; justify-content: space-between; gap: 15px; z-index: 110;
            pointer-events: none;
        }
        .preview-map-widget { 
            width: 100px; height: 100px; border: 2px solid #fff; 
            background: #000; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.6);
            border-radius: 12px; flex-shrink: 0;
            position: relative;
        }
        .preview-meta-box {
            text-align: left; 
            display: flex; flex-direction: column; gap: 2px;
            z-index: 110; pointer-events: none;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            padding: 12px 18px; border-radius: 4px;
            border-left: 5px solid var(--accent-color);
            box-shadow: 0 8px 32px rgba(0,0,0,0.6);
            max-width: 65%;
            /* margin-left: auto; Removed to allow left positioning */
            color: #fff;
        }
        .meta-line:nth-child(1) { animation: scan-in 0.3s ease forwards 0.1s; opacity: 0; }
        .meta-line:nth-child(2) { animation: scan-in 0.3s ease forwards 0.2s; opacity: 0; }
        .meta-line:nth-child(3) { animation: scan-in 0.3s ease forwards 0.3s; opacity: 0; }
        .meta-line:nth-child(4) { animation: scan-in 0.3s ease forwards 0.4s; opacity: 0; }
        .meta-line:nth-child(5) { animation: scan-in 0.3s ease forwards 0.5s; opacity: 0; }

        @keyframes scan-in {
            from { transform: translateX(20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Preview Layout - White Bottom Bar like Reference */
        .preview-actions { 
            position: absolute; bottom: 0; left: 0; width: 100%; 
            height: 140px; 
            background: #ffffff;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            padding: 0 20px;
            z-index: 120; 
            padding-bottom: env(safe-area-inset-bottom);
        }
        
        /* Preview Control Row (Reuse .control-row logic but specific if needed) */
        .preview-control-row {
            width: 100%;
            max-width: 450px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        /* Visual Flash Effect */
        #capture-flash {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: #fff; z-index: 2000; opacity: 0; pointer-events: none;
        }
        .flash-active { animation: flash-trigger 0.4s ease-out; }
        @keyframes flash-trigger {
            0% { opacity: 0; }
            10% { opacity: 1; }
            100% { opacity: 0; }
        }

        #canvas-buffer { display: none; }
        
        /* Landscape Orientation - Auto-responsive adjustments */
        @media screen and (orientation: landscape) {
            .bottom-overlays {
                bottom: 100px;
            }
            .bottom-bar {
                height: 90px;
            }
            .meta-box {
                font-size: 10px;
            }
            .map-widget {
                width: 90px;
                height: 90px;
            }
            .btn-shutter {
                width: 64px;
                height: 64px;
            }
            .top-nav {
                top: 15px;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .bottom-bar {
                height: 135px;
            }
            .control-row {
                max-width: 100%;
            }
            .btn-gallery {
                left: 5px;
            }
            .btn-settings-wrapper {
                right: 25px;
            }
            .btn-shutter {
                width: 62px;
                height: 62px;
            }
            .btn-side {
                width: 52px;
                height: 52px;
            }
            .btn-side i {
                font-size: 1.3rem;
            }
            .btn-label {
                font-size: 9.5px;
            }
            .meta-box {
                font-size: 9px;
                gap: 2px;
            }
            .meta-box > div:first-child {
                font-size: 10px;
            }
            .map-widget {
                width: 90px;
                height: 90px;
            }
        }
        
        @media (max-width: 360px) {
            .bottom-bar {
                height: 125px;
                gap: 14px;
            }
            .btn-gallery {
                left: 0;
            }
            .btn-settings-wrapper {
                right: 20px;
            }
            .btn-shutter {
                width: 60px;
                height: 60px;
            }
            .btn-side {
                width: 48px;
                height: 48px;
            }
            .btn-side i {
                font-size: 1.2rem;
            }
            .btn-label {
                font-size: 9px;
            }
            .btn-container {
                gap: 6px;
            }
            .meta-box { 
                font-size: 8px;
                gap: 1px;
            }
            .meta-box > div:first-child {
                font-size: 9px;
            }
            .map-widget { 
                width: 80px; 
                height: 80px; 
            }
        }
    </style>
</head>
<body>

<div id="camera-container">
    <video id="video" autoplay playsinline muted></video>

    <canvas id="canvas-buffer"></canvas>
    
    <!-- Top Nav -->
    <div class="top-nav">
        <div class="top-icon" onclick="switchCamera();" title="Rotate Camera">
            <i class="bi bi-arrow-repeat"></i>
        </div>
    </div>

    <!-- Compass -->
    <div class="compass-overlay" id="compass-ui" style="display:none;">
        <div class="compass-needle" id="needle"></div>
    </div>

    <!-- Branding Header (Removed from center-top) -->

    <div class="bottom-overlays">
        <div style="flex: 1; display: flex; flex-direction: column; align-items: flex-start;">
            <!-- Branding Row (Outside Box, Stacked) -->
            <div id="l-branding-wrapper">
                <span id="l-branding-text" style="font-size: 14px; font-weight: 800; color: #FFC107; letter-spacing: 0.5px; text-shadow: 0 1px 2px rgba(0,0,0,0.8);">BESUK SAE</span>
                <span id="l-branding-subtitle" style="font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.9); text-transform: uppercase; text-shadow: 0 1px 2px rgba(0,0,0,0.8); margin-top: -2px;">Melayani setulus hati</span>
            </div>

            <!-- Metadata Box (Vertical Stack - Timemark Safe) -->
            <div id="live-meta">
                <!-- 1. Jam (Besar) -->
                <div id="m-time">14:08</div>
                
                <!-- 2. Tanggal & Hari (Sedang) -->
                <div id="m-date">01 Jan 2026</div>
                <div id="m-day">Kamis</div>
                
                <!-- 3. Lokasi (Multiline, Auto-wrap) -->
                <div id="m-addr-wrapper">
                    <span id="m-addr">Memuat lokasi...</span>
                </div>
                
                <!-- 4. GPS Koordinat (Kecil, Safe) -->
                <div id="m-gps-wrapper">
                    <span id="m-gps">7.8058708S 113.3716904E</span>
                </div>
            </div>
        </div>
        
        <!-- Right Map -->
        <div class="map-widget" id="camera-map" style="width: 100px; height: 100px; border-radius: 10px; border: 1.5px solid #fff;"></div>
    </div>



    <!-- Bottom Controls - Perfectly Balanced 3-Column Layout -->
    <div class="bottom-bar">
        <div class="control-row">
            <!-- Gallery Button (Left) -->
            <div class="control-item">
                <div class="btn-container btn-gallery" onclick="location.href='galeri.php'">
                    <div class="btn-side">
                        <i class="bi bi-images"></i>
                    </div>
                    <div class="btn-label">Galeri</div>
                </div>
            </div>
            
            <!-- Shutter Button (Center) -->
            <div class="btn-shutter-wrapper">
                <div class="btn-shutter" id="shutter-btn" style="background: rgba(255,255,255,0.8); border: 4px solid #333;">
                    <div class="btn-shutter-inner" style="background: #333;"></div>
                </div>
            </div>
            
            <!-- Settings Button (Right) -->
            <div class="control-item">
                <div class="btn-container btn-settings-wrapper" onclick="toggleSettings()">
                    <div class="btn-side">
                        <i class="bi bi-gear-fill"></i>
                    </div>
                    <div class="btn-label">Atur</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Capture Flash -->
<div id="capture-flash"></div>

<!-- Settings Overlay -->
<div id="settings-overlay">
    <div class="settings-card shadow-lg glass-card" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
        <div class="settings-header">
            <h4><i class="bi bi-sliders2-vertical me-2"></i>Kamera Pro</h4>
            <div class="top-icon" onclick="toggleSettings()"><i class="bi bi-x-lg" style="color:#666; font-size: 1.2rem;"></i></div>
        </div>
        <div class="settings-body">
            <!-- Font Selection -->
            <div class="settings-list-item">
                <label>Jenis Huruf (Font)</label>
                <select id="set-font-family">
                    <option value="'Inter', sans-serif" selected>Inter (Modern)</option>
                    <option value="'Roboto', sans-serif">Roboto (Clean)</option>
                    <option value="'Montserrat', sans-serif">Montserrat (Stylish)</option>
                    <option value="'Courier New', monospace">Courier (Typewriter)</option>
                    <option value="'Playfair Display', serif">Playfair (Formal)</option>
                </select>
            </div>
            
            <!-- List Items -->
            <div class="settings-list-item">
                <label>Font Color</label>
                <select id="set-font-color">
                    <option value="#ffffff">White</option>
                    <option value="#f7d312">Yellow</option>
                    <option value="#ff5252">Red</option>
                    <option value="#000000">Black</option>
                </select>
            </div>
            <div class="settings-list-item">
                <label>Text size</label>
                <select id="set-text-size">
                    <option value="small">Small</option>
                    <option value="medium" selected>Medium</option>
                    <option value="large">Large</option>
                    <option value="extra-large">Extra Large</option>
                </select>
            </div>
            
            <div class="settings-list-item">
                <label>Font Weight</label>
                <select id="set-font-weight">
                    <option value="normal">Normal (400)</option>
                    <option value="semibold" selected>Semibold (600)</option>
                    <option value="bold">Bold (700)</option>
                    <option value="extra-bold">Extra Bold (800)</option>
                </select>
            </div>
            
            <div class="settings-list-item">
                <label>Position</label>
                <select id="set-position">
                    <option value="bottom-right">Bottom Right</option>
                    <option value="bottom-left" selected>Bottom Left</option>
                    <option value="top-right">Top Right</option>
                    <option value="top-left">Top Left</option>
                    <option value="bottom-center">Bottom Center</option>
                    <option value="top-center">Top Center</option>
                </select>
            </div>
            
            <div class="settings-list-item">
                <label>Text Shadow</label>
                <select id="set-text-shadow">
                    <option value="none">None</option>
                    <option value="light">Light</option>
                    <option value="medium" selected>Medium</option>
                    <option value="strong">Strong</option>
                    <option value="extra-strong">Extra Strong</option>
                </select>
            </div>
            
            <div class="settings-list-item">
                <label>Line Spacing</label>
                <select id="set-line-spacing">
                    <option value="compact">Compact</option>
                    <option value="normal" selected>Normal</option>
                    <option value="relaxed">Relaxed</option>
                    <option value="loose">Loose</option>
                </select>
            </div>

            <!-- Preview/Value Lines -->
            <div class="settings-preview-area">
                <div class="preview-text-line" id="p-date">30 Des 2025 19.50.43</div>
                <div class="preview-text-line" id="p-addr">Jalan Dusun Krajan Selogudi..</div>
                <div class="preview-text-line" id="p-gps">7.8058708S 113.3716904E</div>
                <div class="preview-text-line" id="p-res">3264 x 2448</div>
            </div>

            <!-- Branding Settings -->
            <div class="settings-list-item">
                <label>Show branding</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="set-show-branding" checked>
                </div>
            </div>
            <div class="settings-list-item">
                <label>Branding text</label>
                <input type="text" id="set-branding-text" value="BESUK SAE" 
                       style="border:none; background:#f2f2f2; padding:4px 8px; border-radius:4px; text-align:right; max-width:150px; font-size:14px;">
            </div>
            <div class="settings-list-item">
                <label>Subtitle</label>
                <input type="text" id="set-branding-subtitle" value="Melayani setulus hati" 
                       style="border:none; background:#f2f2f2; padding:4px 8px; border-radius:4px; text-align:right; max-width:150px; font-size:13px;">
            </div>

            <!-- Toggles -->
            <div class="settings-list-item">
                <label>Show map</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="set-show-map" checked>
                </div>
            </div>
            <div class="settings-list-item">
                <label>Show compass</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="set-show-compass" checked>
                </div>
            </div>
            
            <!-- Metadata Display Settings -->
            <div class="settings-list-item" style="background: #e8f5e9; border-top: 2px solid #4CAF50;">
                <label style="color: #2E7D32; font-weight: 700;">📋 Metadata Display</label>
                <span style="font-size: 11px; color: #666;">Pilih info yang mau ditampilkan</span>
            </div>
            
            <div class="settings-list-item">
                <label>📅 Show Date & Time</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="set-show-date" checked>
                </div>
            </div>
            
            <div class="settings-list-item">
                <label>👤 Show User Info</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="set-show-user" checked>
                </div>
            </div>
            
            <div class="settings-list-item">
                <label>📍 Show GPS Coordinates</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="set-show-gps" checked>
                </div>
            </div>
            
            <div class="settings-list-item">
                <label>🧭 Show Heading/Direction</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="set-show-heading" checked>
                </div>
            </div>
            
            <div class="settings-list-item">
                <label>📌 Show Address</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="set-show-address" checked>
                </div>
            </div>
            
            <div class="settings-list-item">
                <label>😊 Show Icons/Emoji</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="set-show-icons" checked>
                </div>
            </div>
        </div>
        <div class="settings-footer">
            <button class="btn-restore" onclick="resetSettings()">Restore</button>
            <button class="btn-ok" onclick="saveSettings()">OK</button>
        </div>
    </div>
</div>

<div id="preview-container">
    <img id="preview-img" src="" alt="">
    
    <!-- Preview Overlays - Timemark Style -->
    <div class="preview-overlays" id="preview-overlays" style="padding: 0 25px;">
        <div class="preview-meta-box" id="preview-meta" style="flex: 1; margin-right: 15px;">
            <div id="pm-time" class="main-time" style="font-size: 16px; font-weight: 800; color: var(--accent-color);">11.24.13</div>
            <div id="pm-date" class="sub-info" style="font-size: 10px; color: rgba(255,255,255,0.8);">31 Des 2025</div>
            <div id="pm-user" class="sub-info" style="font-size: 10px; color: rgba(255,255,255,0.8);">👤 User Name</div>
            <div id="pm-gps" class="sub-info" style="font-size: 10px; color: rgba(255,255,255,0.8);">📍 7.8058713 113.3716904</div>
            <div id="pm-addr" class="sub-info" style="font-size: 9px; color: rgba(255,255,255,0.7); margin-top:2px; padding-top:2px; border-top:1px solid rgba(255,255,255,0.1);">📌 Alamat Lokasi</div>
        </div>
        <div class="preview-map-widget" id="preview-map"></div>
    </div>
    
    <div class="preview-actions">
        <div class="preview-control-row">
            <!-- Retake (Left) -->
            <div class="control-item">
                <div class="btn-container" id="retake-btn">
                    <div class="btn-side">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </div>
                    <div class="btn-label">ULANG</div>
                </div>
            </div>
            
            <!-- Spacer (Center) -->
            <div class="control-item"></div>
            
            <!-- Save (Right) -->
            <div class="control-item">
                <div class="btn-container" id="use-btn">
                    <div class="btn-side">
                        <i class="bi bi-check-circle" style="font-size: 1.8rem;"></i>
                    </div>
                    <div class="btn-label">SIMPAN</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas-buffer');
    const shutterBtn = document.getElementById('shutter-btn');
    const previewContainer = document.getElementById('preview-container');
    const previewImg = document.getElementById('preview-img');
    const retakeBtn = document.getElementById('retake-btn');
    const useBtn = document.getElementById('use-btn');

    let currentStream = null;
    let facingMode = 'environment';
    let gpsData = { lat:-7.8058708, lon:113.3716904 };
    let heading = 0;
    let address = "Mencari lokasi...";
    let currentRotation = 0; // Track device orientation (0, 90, 180, 270)
    
    // User information untuk validasi barang bukti
    const userInfo = <?php echo json_encode($user_info); ?>;
    
    let cameraSettings = {
        fontColor: '#ffffff',
        textSize: 'medium',
        fontWeight: 'semibold',
        position: 'bottom-left',
        textShadow: 'medium',
        lineSpacing: 'normal',
        showMap: false,  // Default: map hidden
        showCompass: true,
        showBranding: true,
        brandingText: '',
        brandingSubtitle: '',
        // Metadata display toggles - untuk fleksibilitas user
        showDate: true,
        showUser: true,
        showGPS: true,
        showHeading: true,
        showAddress: true,
        showIcons: true,
        fontFamily: "'Inter', sans-serif"
    };

    // 1. Camera Logic - Robust with Security Checks & Fallbacks
    async function initCamera() {
        // SECURITY CHECK: getUserMedia requires HTTPS or localhost
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.error("navigator.mediaDevices.getUserMedia is not supported.");
            alert("Akses kamera ditolak. Pastikan Anda menggunakan koneksi aman (HTTPS) dan browser terbaru.");
            return;
        }

        if (currentStream) {
            currentStream.getTracks().forEach(t => t.stop());
        }
        
        // Define fallback sequence for constraints
        const constraintSequence = [
            // Attempt 1: High Resolution (matching device capability)
            { 
                video: { 
                    facingMode: facingMode, 
                    width: { ideal: (facingMode === 'user' ? 1920 : 3840) }, 
                    height: { ideal: (facingMode === 'user' ? 1080 : 2160) } 
                } 
            },
            // Attempt 2: Standard HD
            { 
                video: { 
                    facingMode: facingMode, 
                    width: { ideal: 1280 }, 
                    height: { ideal: 720 } 
                } 
            },
            // Attempt 3: Basic Video
            { video: { facingMode: facingMode } },
            // Attempt 4: Any camera (last resort)
            { video: true }
        ];

        let success = false;
        for (const constraints of constraintSequence) {
            try {
                console.log("Memulai kamera dengan:", constraints);
                currentStream = await navigator.mediaDevices.getUserMedia(constraints);
                console.log("Kamera berhasil diinisialisasi.");
                success = true;
                break;
            } catch (err) {
                console.warn("Gagal inisialisasi dengan paket constraints ini:", err.name);
                continue;
            }
        }

        if (success && currentStream) {
            video.srcObject = currentStream;
            video.onloadedmetadata = () => {
                video.play().then(() => {
                    console.log("Video berhasil diputar.");
                }).catch(e => {
                    console.error("Gagal memutar video otomatis:", e);
                    // Force retry on click if still dark
                    video.onclick = () => video.play();
                });
            };
            video.classList.toggle('front-camera', facingMode === 'user');
        } else {
            // Final generic fallback (any camera, no constraints)
            try {
                console.log('Mencoba fallback umum tanpa constraints');
                const genericStream = await navigator.mediaDevices.getUserMedia({ video: true });
                currentStream = genericStream;
                video.srcObject = genericStream;
                video.onloadedmetadata = async () => {
                    await video.play();
                    console.log('Video berhasil diputar dengan fallback umum');
                };
                video.classList.toggle('front-camera', facingMode === 'user');
                success = true;
            } catch (e) {
                console.warn('Fallback umum gagal:', e.name);
                alert('Tidak dapat mengakses kamera. Pastikan izin kamera diberikan dan koneksi aman (HTTPS).');
            }
        }

    }

    async function switchCamera() {
        facingMode = (facingMode === 'user' ? 'environment' : 'user');
        await initCamera();
    }

    

    // 2. Map & GPS
    let map = null, marker = null;
    function initMap(lat, lon) {
        if (!cameraSettings.showMap) return;
        
        const mapContainer = document.getElementById('camera-map');
        if (!mapContainer) return;
        
        if (!map) {
            // Ensure container is visible
            mapContainer.style.display = 'block';
            
            map = L.map('camera-map', { 
                zoomControl: false, 
                attributionControl: false 
            }).setView([lat, lon], 18); // Zoom 18 = Street/Village detail visible
            
            // Google Hybrid (Satellite + Road Names)
            const googleHybrid = L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                attribution: '© Google Maps'
            });
            
            // OSM Fallback (Standard Map)
            const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            });
            
            googleHybrid.addTo(map);
            
            // If tiles fail to load, switch to OSM
            googleHybrid.on('tileerror', () => {
                console.warn('Satellite tiles failed, switching to OSM');
                map.removeLayer(googleHybrid);
                osm.addTo(map);
            });
            
            // Shrunk Marker pin
            const smallIcon = L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [18, 28], // Smaller size (default is 25, 41)
                iconAnchor: [9, 28],
                shadowSize: [28, 28]
            });
            marker = L.marker([lat, lon], { icon: smallIcon }).addTo(map);
            
            // Fix for initial render issue
            setTimeout(() => {
                if (map) map.invalidateSize();
            }, 300);
        } else {
            map.setView([lat, lon]);
            marker.setLatLng([lat, lon]);
        }
    }

    // 3. Sensors & Address
    if (navigator.geolocation) {
        const geoOptions = {
            enableHighAccuracy: true,
            timeout: 10000, // Increased to 10s for better reliability
            maximumAge: 0
        };
        
        let lastLat = null, lastLon = null;
        
        navigator.geolocation.watchPosition(async (p) => {
            const lat = p.coords.latitude;
            const lon = p.coords.longitude;
            gpsData.lat = lat;
            gpsData.lon = lon;
            
            // Sync UI
            const gpsIcon = cameraSettings.showIcons ? '📍 ' : '';
            const gpsEl = document.getElementById('m-gps');
            if(gpsEl) {
                gpsEl.innerText = `${gpsIcon}${gpsData.lat.toFixed(7)}${gpsData.lat<0?'S':'N'} ${gpsData.lon.toFixed(7)}${gpsData.lon<0?'W':'E'}`;
                autoScaleText(gpsEl);
            }
            
            // Ensure Map stays updated
            initMap(gpsData.lat, gpsData.lon);
            
            // Geocode logic
            if (lastLat && lastLon) {
                const dist = Math.sqrt(Math.pow(lat - lastLat, 2) + Math.pow(lon - lastLon, 2));
                if (dist < 0.00005) return; 
            }
            lastLat = lat; lastLon = lon;

            try {
                const addrLabel = document.getElementById('m-addr');
                if(addrLabel) addrLabel.innerText = "Mencari alamat...";
                
                const res = await fetch(`get_address.php?lat=${gpsData.lat}&lon=${gpsData.lon}`);
                const data = await res.json();
                if (data.success && data.address) {
                    address = data.address;
                    const addrIcon = cameraSettings.showIcons ? '📌 ' : '';
                    if(addrLabel) {
                        addrLabel.innerText = `${addrIcon}${address}`;
                        autoScaleText(addrLabel);
                    }
                } else if (addrLabel) {
                    addrLabel.innerText = "Gagal memuat alamat. Ketuk untuk refresh.";
                    autoScaleText(addrLabel);
                }
            } catch (e) {
                if(document.getElementById('m-addr')) {
                    document.getElementById('m-addr').innerText = "Koneksi lambat. Menunggu data...";
                }
            }
        }, (err) => {
            console.warn("GPS Warning: ", err.message);
            if(err.code === 3 && document.getElementById('m-addr')) {
                document.getElementById('m-addr').innerText = "Sinyal GPS lemah...";
            }
            // Fallback: Still init map with default/cached coords if map is missing
            if(!map) initMap(gpsData.lat, gpsData.lon);
        }, geoOptions);
    } else {
        // Fallback for no geolocation support
        initMap(gpsData.lat, gpsData.lon);
    }

    if (window.DeviceOrientationEvent) {
        window.addEventListener('deviceorientationabsolute', (e) => {
            if (e.alpha !== null) {
                heading = Math.round(360 - e.alpha);
                const dirs = ['N','NE','E','SE','S','SW','W','NW'];
                const dir = dirs[Math.round(heading / 45) % 8];
                const icon = cameraSettings.showIcons ? '🧭 ' : '';
                const headingEl = document.getElementById('m-heading');
                const needleEl = document.getElementById('needle');
                if(headingEl) headingEl.innerText = `${icon}${heading}° ${dir}`;
                if(needleEl) needleEl.style.transform = `rotate(${heading}deg)`;
            }
        }, true);
    }
    
    
    
    // Update user info display
    function updateUserInfo() {
        const userText = `${cameraSettings.showIcons ? '👤 ' : ''}${userInfo.nama} (${userInfo.role}${userInfo.bidang !== '-' ? ' - ' + userInfo.bidang : ''})`;
        const userEl = document.getElementById('m-user');
        if(userEl) userEl.innerText = userText;
    }
    
    // Auto-scale text to fit in fixed-size container
    function autoScaleText(element) {
        if (!element) return;
        
        const container = element.parentElement;
        if (!container) return;
        
        const baseSize = parseInt(element.getAttribute('data-base-size')) || 14;
        const maxWidth = container.offsetWidth - 24; // Account for padding
        const maxHeight = container.offsetHeight - 16;
        
        let fontSize = baseSize;
        element.style.fontSize = fontSize + 'px';
        
        // Scale down font if text overflows (down only, never up)
        let iterations = 0;
        while ((element.scrollWidth > maxWidth || element.scrollHeight > maxHeight) && fontSize > 8 && iterations < 20) {
            fontSize -= 0.5;
            element.style.fontSize = fontSize + 'px';
            iterations++;
        }
    }
    
    // Auto-scale all metadata text elements
    function autoScaleAllMetadata() {
        autoScaleText(document.getElementById('m-time'));
        autoScaleText(document.getElementById('m-date'));
        autoScaleText(document.getElementById('m-gps'));
        autoScaleText(document.getElementById('m-addr'));
    }
    
    // Update metadata visibility based on settings
    function updateMetadataVisibility() {
        const dateBox = document.getElementById('m-date')?.parentElement;
        const gpsBox = document.getElementById('m-gps-wrapper');
        const addrBox = document.getElementById('m-addr-wrapper');
        
        if(dateBox) dateBox.style.display = cameraSettings.showDate ? 'flex' : 'none';
        if(gpsBox) gpsBox.style.display = cameraSettings.showGPS ? 'flex' : 'none';
        if(addrBox) addrBox.style.display = cameraSettings.showAddress ? 'flex' : 'none';
        
        // Time & Branding Visibility
        const topBranding = document.getElementById('l-branding-wrapper');
        if(topBranding) topBranding.style.display = cameraSettings.showBranding ? 'flex' : 'none';
        
        // Re-scale after visibility changes
        setTimeout(autoScaleAllMetadata, 100);
    }

    // 4. UI Functions
    function toggleSettings() {
        const overlay = document.getElementById('settings-overlay');
        const isVisible = overlay.classList.contains('show');
        if (isVisible) {
            overlay.classList.remove('show');
            setTimeout(() => { if(!overlay.classList.contains('show')) overlay.style.display = 'none'; }, 300);
        } else {
            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('show'), 10);
            updatePreviewTexts();
        }
    }

    function updatePreviewTexts() {
        document.getElementById('p-date').innerText = document.getElementById('m-date').innerText;
        document.getElementById('p-gps').innerText = document.getElementById('m-gps').innerText;
        document.getElementById('p-addr').innerText = address.substring(0, 30) + "...";
        document.getElementById('p-res').innerText = `${video.videoWidth} x ${video.videoHeight}`;
    }

    function saveSettings() {
        cameraSettings.fontFamily = document.getElementById('set-font-family').value;
        cameraSettings.fontColor = document.getElementById('set-font-color').value;
        cameraSettings.textSize = document.getElementById('set-text-size').value;
        cameraSettings.fontWeight = document.getElementById('set-font-weight').value;
        cameraSettings.position = document.getElementById('set-position').value;
        cameraSettings.textShadow = document.getElementById('set-text-shadow').value;
        cameraSettings.lineSpacing = document.getElementById('set-line-spacing').value;
        cameraSettings.showMap = document.getElementById('set-show-map').checked;
        cameraSettings.showCompass = document.getElementById('set-show-compass').checked;
        cameraSettings.showBranding = document.getElementById('set-show-branding').checked;
        cameraSettings.brandingText = document.getElementById('set-branding-text').value || 'BESUK SAE';
        cameraSettings.brandingSubtitle = document.getElementById('set-branding-subtitle').value || 'Melayani setulus hati';
        
        // Metadata display toggles
        cameraSettings.showDate = document.getElementById('set-show-date').checked;
        cameraSettings.showUser = document.getElementById('set-show-user').checked;
        cameraSettings.showGPS = document.getElementById('set-show-gps').checked;
        cameraSettings.showHeading = document.getElementById('set-show-heading').checked;
        cameraSettings.showAddress = document.getElementById('set-show-address').checked;
        cameraSettings.showIcons = document.getElementById('set-show-icons').checked;
        
        // Update integrated branding in live HUD
        const brandLabel = document.getElementById('l-branding-text');
        const subLabel = document.getElementById('l-branding-subtitle');
        if (brandLabel) {
            brandLabel.innerText = cameraSettings.brandingText.toUpperCase();
            if (brandLabel.parentElement) {
                brandLabel.parentElement.style.display = cameraSettings.showBranding ? 'flex' : 'none';
            }
        }
        if (subLabel) {
            subLabel.innerText = cameraSettings.brandingSubtitle;
        }
        
        // Update Live HUD visibility
        updateMetadataVisibility();
        updateUserInfo();

        // Apply accent color (CSS Variable)
        const accent = cameraSettings.fontColor || '#00BCD4';
        document.documentElement.style.setProperty('--accent-color', accent);
        
        // Update GPS & Address with icons
        const iconP = cameraSettings.showIcons ? ' ' : '';
        const gpsVal = `${gpsData.lat.toFixed(7)}${gpsData.lat<0?'S':'N'} ${gpsData.lon.toFixed(7)}${gpsData.lon<0?'W':'E'}`;
        const gpsEl = document.getElementById('m-gps');
        const addrEl = document.getElementById('m-addr');
        if(gpsEl) gpsEl.innerText = (cameraSettings.showIcons ? '📍 ' : '') + gpsVal;
        if(addrEl) addrEl.innerText = (cameraSettings.showIcons ? '📌 ' : '') + address;
        
        // Toggle map visibility
        const mapWidget = document.getElementById('camera-map');
        if (cameraSettings.showMap) {
            mapWidget.classList.remove('hidden');
            setTimeout(() => { if(map) map.invalidateSize(); }, 300);
        } else {
            mapWidget.classList.add('hidden');
        }
        
        // Position & Layout
        const overlays = document.querySelector('.bottom-overlays');
        const meta = document.getElementById('live-meta');
        if (cameraSettings.position.includes('top')) {
            overlays.style.bottom = 'auto'; overlays.style.top = '10vh';
        } else {
            overlays.style.top = 'auto'; overlays.style.bottom = '18vh';
        }
        
        if (cameraSettings.position.includes('center')) {
            overlays.style.flexDirection = 'column';
            overlays.style.alignItems = 'center';
            overlays.style.justifyContent = cameraSettings.position.includes('top') ? 'flex-start' : 'flex-end';
            meta.style.textAlign = 'center';
        } else {
            overlays.style.flexDirection = cameraSettings.position.includes('left') ? 'row-reverse' : 'row';
            overlays.style.alignItems = 'flex-end';
            overlays.style.justifyContent = 'space-between';
            meta.style.textAlign = cameraSettings.position.includes('left') ? 'left' : 'right';
        }

        document.getElementById('compass-ui').style.display = cameraSettings.showCompass ? 'flex' : 'none';
        
        // Update Font Family to HUD
        document.getElementById('live-meta').style.fontFamily = cameraSettings.fontFamily;
        document.getElementById('l-branding-text').style.fontFamily = cameraSettings.fontFamily;
        document.getElementById('l-branding-subtitle').style.fontFamily = cameraSettings.fontFamily;

        localStorage.setItem('besuksae_camera_settings_v2', JSON.stringify(cameraSettings));
        toggleSettings();
        updatePreviewTexts();
    }


    // 5. Capture Logic
    let previewMap = null, previewMarker = null;
    let captureSnapshot = { lat: null, lon: null, address: '', heading: 0, date: '' };
    
    function initPreviewMap(lat, lon) {
        const mapContainer = document.getElementById('preview-map');
        if (!mapContainer) return;
        
        // Clear existing map
        if (previewMap) {
            previewMap.remove();
            previewMap = null;
        }
        
        // Create new map
        mapContainer.style.display = 'block';
        previewMap = L.map('preview-map', { 
            zoomControl: false, 
            attributionControl: false,
            dragging: false,
            touchZoom: false,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            boxZoom: false
        }).setView([lat, lon], 14);
        
        // Google Hybrid
        L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            attribution: '© Google Maps'
        }).addTo(previewMap);
        
        // Shrunk Preview Marker
        const pIcon = L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [18, 28],
            iconAnchor: [9, 28],
            shadowSize: [28, 28]
        });
        previewMarker = L.marker([lat, lon], { icon: pIcon }).addTo(previewMap);
        
        // Fix render
        setTimeout(() => {
            if (previewMap) previewMap.invalidateSize();
        }, 300);
    }
    
    async function capturePhotoWithMap() {
        // Get current date with short month format
        const now = new Date();
        const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const day = String(now.getDate()).padStart(2, '0');
        const month = shortMonths[now.getMonth()];
        const year = now.getFullYear();
        const formattedDate = `${day} ${month} ${year}`;
        const formattedTime = now.toLocaleTimeString('id-ID', { hour12:false, hour: '2-digit', minute:'2-digit' });
        
        // PERBAIKAN: Snapshot data SAAT INI agar tidak berubah saat proses rendering (mencegah drift)
        captureSnapshot = {
            lat: gpsData.lat,
            lon: gpsData.lon,
            address: address,
            heading: heading,
            date: formattedDate,
            time: formattedTime
        };

        return new Promise((resolve) => {
            const ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            if (facingMode === 'user') {
                ctx.translate(canvas.width, 0); ctx.scale(-1, 1);
            }
            // Draw regular view
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            ctx.setTransform(1, 0, 0, 1, 0, 0);

            // === Burn Branding Header (Removed to match Live HUD) ===

            // === Burn Metadata ===
            // Font size mapping
            const fontSizeMap = {
                'small': canvas.height / 70,
                'medium': canvas.height / 50,
                'large': canvas.height / 40,
                'extra-large': canvas.height / 30
            };
            const fontSize = fontSizeMap[cameraSettings.textSize] || fontSizeMap['medium'];
            
            // Font weight mapping
            const fontWeightMap = {
                'normal': '400',
                'semibold': '600',
                'bold': '700',
                'extra-bold': '800'
            };
            const fontWeight = fontWeightMap[cameraSettings.fontWeight] || '600';
            
            // Text shadow mapping
            const shadowMap = {
                'none': { blur: 0, offset: 0 },
                'light': { blur: 2, offset: 1 },
                'medium': { blur: 4, offset: 2 },
                'strong': { blur: 6, offset: 3 },
                'extra-strong': { blur: 8, offset: 4 }
            };
            const shadow = shadowMap[cameraSettings.textShadow] || shadowMap['medium'];
            
            // Line spacing mapping - Renamed to avoid global conflict
            const mLineHeightMap = {
                'compact': 1.2,
                'normal': 1.35,
                'relaxed': 1.5,
                'loose': 1.7
            };
            const mLineHeight = mLineHeightMap[cameraSettings.lineSpacing] || 1.35;
            
            ctx.fillStyle = cameraSettings.fontColor;
            ctx.shadowColor = "black"; 
            ctx.shadowBlur = shadow.blur;
            ctx.shadowOffsetX = shadow.offset;
            ctx.shadowOffsetY = shadow.offset;

            // PREPARATION: Calculate base units based on orientations
            // We use the "visual" width/height meaning the dimension after possible rotation
            const isLandscape = (screen.orientation && screen.orientation.type.includes('landscape')) || (Math.abs(window.orientation) === 90);
            const vWidth = canvas.width;
            const vHeight = canvas.height;
            const vh = vHeight / 100;
            const vw = vWidth / 100;

            ctx.font = `${fontWeight} ${fontSize}px Inter`;
            
            // Text alignment based on position
            let textAlign = 'right';
            if (cameraSettings.position.includes('center')) {
                textAlign = 'center';
            } else if (cameraSettings.position.includes('left')) {
                textAlign = 'left';
            }
            const margin = canvas.width * 0.04;

            // Build lines array based on Timemark Style
            const iconPrefix = cameraSettings.showIcons;
            const brandText = (cameraSettings.brandingText || 'BESUK SAE').toUpperCase();
            const brandSub = cameraSettings.brandingSubtitle || 'Melayani setulus hati';

            // === RENDER METADATA BOX (iOS / Timemark Style) ===
            const vh = canvas.height / 100;
            const vw = canvas.width / 100;

            // 1. Configuration & Sizes - Adaptive for Orientation
            const boxPadding = 1.8 * vh;
            const itemGap = 0.8 * vh;
            
            // Adjust font sizes for landscape to ensure readability
            const scaleFactor = isLandscape ? 1.2 : 1.0; 
            const brandFontSize = 1.6 * vh * scaleFactor;
            const timeFontSize = 6.5 * vh * scaleFactor;
            const dateFontSize = 1.8 * vh * scaleFactor;
            const addrFontSize = 1.5 * vh * scaleFactor;
            const gpsFontSize = 1.3 * vh * scaleFactor;
            
            const maxBoxWidth = isLandscape ? 45 * vw : 58 * vw;
            const cornerRadius = 1.2 * vh;
            
            // Branding Text
            const brandingText = (cameraSettings.brandingText || 'BESUK SAE').toUpperCase();
            const brandingSub = (cameraSettings.brandingSubtitle || 'Melayani setulus hati').toUpperCase();

            // --- Pre-calculate Height (EXCLUDING Branding) ---
            let totalHeight = 0;
            totalHeight += timeFontSize; // Time block
            
            // Address wrapping
            ctx.font = `400 ${addrFontSize}px Inter`;
            const addrWords = captureSnapshot.address.split(' ');
            let addrLines = [];
            let currentLine = '';
            for (let n = 0; n < addrWords.length; n++) {
                let testLine = currentLine + addrWords[n] + ' ';
                if (ctx.measureText(testLine).width > maxBoxWidth - (boxPadding * 2)) {
                    addrLines.push(currentLine.trim());
                    currentLine = addrWords[n] + ' ';
                } else {
                    currentLine = testLine;
                }
            }
            if (currentLine) addrLines.push(currentLine.trim());
            totalHeight += (addrLines.length * addrFontSize * 1.3) + itemGap;

            // GPS
            if (cameraSettings.showGPS) {
                totalHeight += gpsFontSize * 1.3;
            }

            // Box position (Bottom Left)
            const boxX = 4 * vw;
            const boxBottomMargin = 6 * vh; 
            const boxY = canvas.height - boxBottomMargin - totalHeight - (boxPadding * 2);
            
            // --- DRAW BRANDING (OUTSIDE BOX, STACKED) ---
            const brandYBase = boxY - (4.0 * vh); // Shift further above box for stacking
            ctx.textAlign = 'left';
            
            // Branding background shadow for readability
            ctx.shadowColor = 'rgba(0,0,0,0.8)';
            ctx.shadowBlur = 4;
            ctx.shadowOffsetX = 1;
            ctx.shadowOffsetY = 1;

            // 1. Main Branding Text
            ctx.fillStyle = '#FFC107'; // Yellow for brand
            ctx.font = `800 ${brandFontSize * 1.2}px Inter`; // Slightly larger
            ctx.fillText(brandingText, boxX + (0.5 * vw), brandYBase);
            
            // 2. Subtitle (Below Main)
            ctx.fillStyle = 'rgba(255,255,255,0.95)';
            ctx.font = `600 ${brandFontSize * 0.8}px Inter`;
            ctx.fillText(brandingSub, boxX + (0.5 * vw), brandYBase + (brandFontSize * 1.1));
            
            ctx.shadowBlur = 0; // Reset shadow for box

            // --- DRAW BOX (TRANSPARENT) ---
            ctx.save();
            ctx.fillStyle = 'rgba(0,0,0,0)'; // FULLY TRANSPARENT BACKGROUND
            ctx.strokeStyle = 'rgba(255,255,255,0.45)'; // White border
            ctx.lineWidth = 0.15 * vh;

            // Manual rounded rect helper
            function drawRoundRect(x, y, w, h, r) {
                ctx.beginPath();
                ctx.moveTo(x + r, y);
                ctx.lineTo(x + w - r, y);
                ctx.quadraticCurveTo(x + w, y, x + w, y + r);
                ctx.lineTo(x + w, y + h - r);
                ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
                ctx.lineTo(x + r, y + h);
                ctx.quadraticCurveTo(x, y + h, x, y + h - r);
                ctx.lineTo(x, y + r);
                ctx.quadraticCurveTo(x, y, x + r, y);
                ctx.closePath();
                // ctx.fill(); // REMOVED FILL FOR TRANSPARENCY
                ctx.stroke();
            }
            drawRoundRect(boxX, boxY, maxBoxWidth, totalHeight + (boxPadding * 2), cornerRadius);
            ctx.restore();

            // --- DRAW CONTENT ---
            ctx.textAlign = 'left';
            let currentY = boxY + boxPadding + (timeFontSize * 0.8);
            
            // 1. Time & Date
            ctx.fillStyle = '#FFFFFF';
            ctx.font = `300 ${timeFontSize}px Inter`;
            const tStr = captureSnapshot.time.substring(0, 5);
            ctx.fillText(tStr, boxX + boxPadding, currentY);
            
            const tWidth = ctx.measureText(tStr).width;
            
            // Vertical bar
            ctx.fillStyle = '#FFC107';
            ctx.fillRect(boxX + boxPadding + tWidth + (1.5 * vw), currentY - (timeFontSize * 0.7), 0.35 * vw, timeFontSize * 0.75);
            
            // Date & Day
            ctx.fillStyle = '#FFFFFF';
            const dx = boxX + boxPadding + tWidth + (3 * vw);
            const dtText = captureSnapshot.date;
            ctx.font = `700 ${dateFontSize}px Inter`;
            ctx.fillText(dtText, dx, currentY - (dateFontSize * 0.8));
            
            ctx.font = `400 ${dateFontSize}px Inter`;
            const dName = new Date().toLocaleDateString('id-ID', { weekday: 'long' });
            ctx.fillText(dName, dx, currentY + (dateFontSize * 0.3));

            currentY += itemGap;

            // 2. Address
            ctx.font = `400 ${addrFontSize}px Inter`;
            for (let line of addrLines) {
                currentY += addrFontSize * 1.3;
                ctx.fillText(line, boxX + boxPadding, currentY);
            }

            // 3. GPS (Bottom of box)
            if (cameraSettings.showGPS) {
                currentY += itemGap + (gpsFontSize * 1.1);
                ctx.fillStyle = 'rgba(255,255,255,0.85)';
                ctx.font = `400 ${gpsFontSize}px Inter`;
                ctx.fillText(`GPS: ${captureSnapshot.lat.toFixed(7)}, ${captureSnapshot.lon.toFixed(7)}`, boxX + boxPadding, currentY);
            }

            // === Burn Map Widget to Canvas ===
            const mapSize = 18 * vh;
            const mapX = canvas.width - (4 * vw) - mapSize;
            const mapY = canvas.height - boxBottomMargin - mapSize; // Lowered with boxBottomMargin

            if (cameraSettings.showMap && map && typeof leafletImage !== 'undefined') {
                // Use leaflet-image to capture the map
                leafletImage(map, function(err, mapCanvas) {
                    if (!err && mapCanvas) {
                        try {
                            // Draw white border for map
                            ctx.strokeStyle = '#fff';
                            ctx.lineWidth = 0.2 * vh;
                            ctx.strokeRect(mapX - 1, mapY - 1, mapSize + 2, mapSize + 2);
                            ctx.drawImage(mapCanvas, mapX, mapY, mapSize, mapSize);
                            
                            // Resolve with final image
                            resolve(canvas.toDataURL('image/jpeg', 0.90));
                        } catch (e) {
                            console.error('Failed to burn map:', e);
                            resolve(canvas.toDataURL('image/jpeg', 0.90));
                        }
                    } else {
                        // If map capture fails, resolve without map
                        resolve(canvas.toDataURL('image/jpeg', 0.90));
                    }
                });
            } else {
                // No map to burn, resolve immediately
                resolve(canvas.toDataURL('image/jpeg', 0.90));
            }
        });
    }
    
    // Final init check
    window.addEventListener('load', () => {
        // Fallback init if geolocation didn't trigger it
        setTimeout(() => {
            if(!map) initMap(gpsData.lat, gpsData.lon);
            else map.invalidateSize();
        }, 1000);
    });



    shutterBtn.onclick = async () => {
        // Trigger Flash
        const flash = document.getElementById('capture-flash');
        flash.classList.add('flash-active');
        setTimeout(() => flash.classList.remove('flash-active'), 400);

        shutterBtn.style.pointerEvents = 'none';
        shutterBtn.style.opacity = '0.5';
        
        try {
            const imageData = await capturePhotoWithMap();
            previewImg.src = imageData;
            
            // Perbaikan: Gunakan snapshot agar data pratinjau sama persis dengan yang di-burn
            const timeStr = captureSnapshot.time;
            const dateStr = captureSnapshot.date;
            
            if(document.getElementById('pm-time')) document.getElementById('pm-time').innerText = timeStr;
            if(document.getElementById('pm-date')) document.getElementById('pm-date').innerText = dateStr;
            if(document.getElementById('pm-user')) document.getElementById('pm-user').innerText = `👤 ${userInfo.nama}`;
            if(document.getElementById('pm-gps')) document.getElementById('pm-gps').innerText = `📍 ${captureSnapshot.lat.toFixed(7)} ${captureSnapshot.lon.toFixed(7)}`;
            if(document.getElementById('pm-addr')) document.getElementById('pm-addr').innerText = `📌 ${captureSnapshot.address}`;
            
            // Perbaikan: Sembunyikan live overlay agar tidak duplikat dengan yang sudah di-burn di foto
            const previewOverlays = document.getElementById('preview-overlays');
            if(previewOverlays) previewOverlays.style.display = 'none';
            
            previewContainer.style.display = 'flex';
            setTimeout(() => previewContainer.classList.add('show'), 10);
        } finally {
            // Re-enable button
            shutterBtn.style.pointerEvents = 'auto';
            shutterBtn.style.opacity = '1';
        }
    };

    retakeBtn.onclick = () => {
        previewContainer.classList.remove('show');
        setTimeout(() => { 
            if(!previewContainer.classList.contains('show')) {
                previewContainer.style.display = 'none';
                // Clear preview map
                if (previewMap) {
                    previewMap.remove();
                    previewMap = null;
                }
            }
        }, 300);
    };

    useBtn.onclick = async () => {
        // Prevent double click
        if(useBtn.style.opacity === '0.5') return;
        
        useBtn.style.opacity = '0.5';
        const labelEl = useBtn.querySelector('.btn-label');
        const originalText = labelEl ? labelEl.innerText : 'SIMPAN';
        if(labelEl) labelEl.innerText = 'KIRIM...';
        
        try {
            // PERBAIKAN: Gunakan data dari snapshot untuk memastikan database sinkron dengan foto
            const res = await fetch('save_camera_photo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    image: previewImg.src, 
                    lat: captureSnapshot.lat, 
                    lon: captureSnapshot.lon 
                })
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = 'camera_keterangan.php?file=' + data.filename;
            } else alert('Gagal: ' + data.message);
        } catch (e) { alert('Terjadi kesalahan.'); }
        finally { 
            useBtn.style.opacity = '1'; 
            if(labelEl) labelEl.innerText = originalText;
        }
    };

    // Time update - with short month format
    setInterval(() => {
        const now = new Date();
        
        // Short month names mapping
        const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const day = String(now.getDate()).padStart(2, '0');
        const month = shortMonths[now.getMonth()];
        const year = now.getFullYear();
        const dateStr = `${day} ${month} ${year}`;
        
        const dayName = now.toLocaleDateString('id-ID', { weekday: 'long' });
        const timeShort = now.toLocaleTimeString('id-ID', { hour12:false, hour: '2-digit', minute:'2-digit' });
        
        const timeEl = document.getElementById('m-time');
        const dateEl = document.getElementById('m-date');
        const dayEl = document.getElementById('m-day');
        
        if(timeEl) {
            timeEl.innerText = timeShort;
            autoScaleText(timeEl);
        }
        if(dateEl) {
            dateEl.innerText = dateStr;
            autoScaleText(dateEl);
        }
        if(dayEl) {
            dayEl.innerText = dayName;
        }
    }, 1000);

    // Screen Orientation Auto-Rotation Support
    // Allow screen to auto-rotate when device tilts
    async function unlockOrientation() {
        if (screen.orientation && screen.orientation.unlock) {
            try {
                await screen.orientation.unlock();
                console.log('Screen orientation unlocked - auto-rotation enabled');
            } catch (e) {
                console.log('Orientation already unlocked or not supported');
            }
        }
    }
    
    // Handle orientation changes for UI adjustments
    function updateRotation() {
        if (screen.orientation) {
            currentRotation = screen.orientation.angle;
        } else if (typeof window.orientation !== 'undefined') {
            currentRotation = window.orientation;
        }
        console.log('Current Rotation Updated:', currentRotation);
    }

    // Fallback: show start button if video does not play automatically
    function monitorVideoPlayback() {
        setTimeout(() => {
            if (video.readyState < 2) { // HAVE_CURRENT_DATA
                console.warn('Video not playing, showing manual start button.');
                const btn = document.getElementById('start-camera-btn');
                if (btn) btn.style.display = 'block';
            }
        }, 2000);
    }

    // Call monitor after attempting to init camera
    const originalInitCamera = initCamera;
    async function initCameraWrapper() {
        console.log('initCameraWrapper dipanggil');
        await originalInitCamera();
        monitorVideoPlayback();
        console.log('initCameraWrapper selesai');
    }
    // Override initCamera dengan wrapper
    initCamera = initCameraWrapper;

    if (screen.orientation) {
        screen.orientation.addEventListener('change', () => {
            updateRotation();
            const type = screen.orientation.type;
            console.log('Orientation changed to:', type);
            
            // Optional: Adjust map on orientation change
            if (map) {
                setTimeout(() => {
                    map.invalidateSize();
                }, 300);
            }
        });
    } else {
        window.addEventListener('orientationchange', updateRotation);
    }
    updateRotation();

    // Initialize
    try {
        unlockOrientation();
    } catch(e) { console.warn("Orientation unlock failed:", e); }
    
    initCamera();
    updateUserInfo();
    updateMetadataVisibility();
    
    // Initial auto-scale after DOM is ready
    window.addEventListener('resize', () => {
        autoScaleAllMetadata();
        if (map) map.invalidateSize();
    });
    setTimeout(autoScaleAllMetadata, 500);
</script>
</body>
</html>

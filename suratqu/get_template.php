<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $db->prepare("SELECT konten_html FROM template_surat WHERE id_template = ?");
    $stmt->execute([$id]);
    $template = $stmt->fetch();
    
    header('Content-Type: application/json');
    if ($template) {
        echo json_encode(['success' => true, 'content' => $template['konten_html']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID missing']);
}

<?php
// docku/check_schema_web.php
require_once 'config/database.php';
// Disable HTML errors for JSON output
ini_set('html_errors', 0);
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM disposisi_penerima");
    echo json_encode(['success' => true, 'columns' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

<?php
require_once __DIR__ . '/../config/database.php';

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        $pdo = getConnection();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}
?>

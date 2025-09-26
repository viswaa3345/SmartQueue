<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/AuthService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $auth = new AuthService();
    $result = $auth->logout();
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Logout endpoint error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Logout failed. Please try again.'
    ]);
}
?>
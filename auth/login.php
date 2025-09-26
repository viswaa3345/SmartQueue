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
    
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    // Login user
    $result = $auth->login($email, $password, $role);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Login endpoint error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Login failed. Please try again.'
    ]);
}
?>
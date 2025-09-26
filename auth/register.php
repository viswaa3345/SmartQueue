<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if required files exist
    $authFile = __DIR__ . '/../includes/AuthService.php';
    if (!file_exists($authFile)) {
        throw new Exception("AuthService.php not found at: $authFile");
    }
    
    require_once $authFile;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $auth = new AuthService();
    
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $adminKey = $_POST['adminKey'] ?? '';
    
    // Debug logging
    error_log("Registration attempt - Email: $email, Role: $role, Name: $name");
    
    // Register user
    $result = $auth->register($email, $password, $name, $role, $adminKey);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Registration endpoint error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>
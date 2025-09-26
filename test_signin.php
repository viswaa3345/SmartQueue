<?php
// Direct sign-in test
header('Content-Type: application/json');

try {
    // Test with the existing admin user
    require_once 'includes/AuthService.php';
    $auth = new AuthService();
    
    // Test login with admin credentials
    echo "Testing admin login...\n";
    $result = $auth->login('admin@restaurant.com', 'admin123', 'admin');
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test login with customer credentials
    echo "Testing customer login...\n";
    $result2 = $auth->login('customer@example.com', 'password123', 'customer');
    echo json_encode($result2, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
?>
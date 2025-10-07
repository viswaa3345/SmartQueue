<?php
require_once 'headers.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check authentication using session variables from AuthService
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        // Get current active tokens with food item details
        $stmt = $pdo->prepare("
            SELECT t.*, f.name as food_name, f.price, f.preparation_time 
            FROM tokens t 
            LEFT JOIN food_items f ON t.food_item_id = f.id 
            WHERE t.user_id = ? AND t.status = 'active'
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $tokens = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true, 
            'tokens' => $tokens
        ]);
        
    } catch (PDOException $e) {
        error_log("Customer token error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>

<?php
require_once 'headers.php';
require_once 'db.php';
require_once 'auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['token_id'])) {
    echo json_encode(['success' => false, 'error' => 'Token ID is required']);
    exit;
}

$user = getCurrentUser();
$token_id = $input['token_id'];

try {
    // Get token details
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE id = ?");
    $stmt->execute([$token_id]);
    $token = $stmt->fetch();
    
    if (!$token) {
        echo json_encode(['success' => false, 'error' => 'Token not found']);
        exit;
    }
    
    // Check permissions
    if ($user['role'] !== 'admin' && $token['user_id'] != $user['id']) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Check if token can be cancelled
    if ($token['status'] !== 'active') {
        echo json_encode(['success' => false, 'error' => 'Token cannot be cancelled']);
        exit;
    }
    
    // Update token status
    $stmt = $pdo->prepare("UPDATE tokens SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?");
    $stmt->execute([$token_id]);
    
    // Create notification
    $message = "Token {$token['token_number']} has been cancelled.";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, token_id, type, message) VALUES (?, ?, 'system', ?)");
    $stmt->execute([$token['user_id'], $token_id, $message]);
    
    echo json_encode(['success' => true, 'message' => 'Token cancelled successfully']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>

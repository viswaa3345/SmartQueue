<?php
require_once 'headers.php';
require_once 'db.php';
require_once 'auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

requireAuth('admin');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['token_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'error' => 'Token ID and action are required']);
    exit;
}

$token_id = $input['token_id'];
$action = $input['action'];
$admin_user = getCurrentUser();

try {
    // Get token details
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE id = ?");
    $stmt->execute([$token_id]);
    $token = $stmt->fetch();
    
    if (!$token) {
        echo json_encode(['success' => false, 'error' => 'Token not found']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    switch ($action) {
        case 'call':
            if ($token['status'] !== 'active') {
                throw new Exception('Token must be active to call');
            }
            
            $stmt = $pdo->prepare("UPDATE tokens SET status = 'called', called_at = NOW() WHERE id = ?");
            $stmt->execute([$token_id]);
            
            // Create notification
            $message = "Your token {$token['token_number']} is now being called. Please proceed to the pickup counter.";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, token_id, type, message) VALUES (?, ?, 'token_called', ?)");
            $stmt->execute([$token['user_id'], $token_id, $message]);
            
            $action_message = 'Token called successfully';
            break;
            
        case 'complete':
            if (!in_array($token['status'], ['active', 'called'])) {
                throw new Exception('Token must be active or called to complete');
            }
            
            $stmt = $pdo->prepare("UPDATE tokens SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$token_id]);
            
            // Create notification
            $message = "Your token {$token['token_number']} has been completed. Thank you for your visit!";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, token_id, type, message) VALUES (?, ?, 'system', ?)");
            $stmt->execute([$token['user_id'], $token_id, $message]);
            
            $action_message = 'Token completed successfully';
            break;
            
        case 'cancel':
            if (!in_array($token['status'], ['active', 'called'])) {
                throw new Exception('Token must be active or called to cancel');
            }
            
            $stmt = $pdo->prepare("UPDATE tokens SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?");
            $stmt->execute([$token_id]);
            
            // Create notification
            $message = "Your token {$token['token_number']} has been cancelled by admin.";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, token_id, type, message) VALUES (?, ?, 'system', ?)");
            $stmt->execute([$token['user_id'], $token_id, $message]);
            
            $action_message = 'Token cancelled successfully';
            break;
            
        case 'reactivate':
            if ($token['status'] !== 'called') {
                throw new Exception('Only called tokens can be reactivated');
            }
            
            $stmt = $pdo->prepare("UPDATE tokens SET status = 'active', called_at = NULL WHERE id = ?");
            $stmt->execute([$token_id]);
            
            // Create notification
            $message = "Your token {$token['token_number']} has been reactivated and is back in queue.";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, token_id, type, message) VALUES (?, ?, 'system', ?)");
            $stmt->execute([$token['user_id'], $token_id, $message]);
            
            $action_message = 'Token reactivated successfully';
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $action_message
    ]);
    
} catch (Exception $e) {
    $pdo->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
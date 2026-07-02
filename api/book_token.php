<?php
require_once 'headers.php';
require_once 'db.php';
require_once 'auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check authentication using session variables from AuthService
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['food_item_id']) || !isset($input['quantity'])) {
        echo json_encode(['success' => false, 'error' => 'Food item and quantity are required']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $food_item_id = $input['food_item_id'];
    $quantity = (int)$input['quantity'];
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    $cart_items = $input['cart_items'] ?? [];
    $order_summary = trim((string)($input['order_summary'] ?? ''));
    $order_total = isset($input['order_total']) ? (float)$input['order_total'] : null;
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid quantity']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if food item exists and is available (with fallback for missing column)
        try {
            $stmt = $pdo->prepare("SELECT * FROM food_items WHERE id = ? AND is_available = 1");
            $stmt->execute([$food_item_id]);
            $food_item = $stmt->fetch();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Unknown column 'is_available'") !== false) {
                $stmt = $pdo->prepare("SELECT * FROM food_items WHERE id = ?");
                $stmt->execute([$food_item_id]);
                $food_item = $stmt->fetch();
            } else {
                throw $e;
            }
        }
        
        if (!$food_item) {
            throw new Exception('Food item not available');
        }
        
        // Check if user has an active token
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tokens WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$user_id]);
        $active_count = $stmt->fetch()['count'];
        
        if ($active_count > 0) {
            throw new Exception('You already have an active token. Please wait for it to be processed.');
        }
        
        // Generate unique token number
        do {
            $token_number = 'T' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tokens WHERE token_number = ?");
            $stmt->execute([$token_number]);
        } while ($stmt->fetch()['count'] > 0);
        
        // Calculate estimated time
        $stmt = $pdo->prepare("SELECT COUNT(*) as queue_count FROM tokens WHERE status = 'active' AND created_at < NOW()");
        $stmt->execute();
        $queue_position = $stmt->fetch()['queue_count'] + 1;
        
        $estimated_time = ($queue_position * 5) + $food_item['preparation_time']; // 5 min avg per token + prep time
        
        // If the client sent a multi-item cart, use the summed quantity for the token.
        if (is_array($cart_items) && count($cart_items) > 0) {
            $quantity = array_reduce($cart_items, function ($sum, $item) {
                return $sum + (int)($item['quantity'] ?? 0);
            }, 0) ?: $quantity;
        }

        // Insert token
        $stmt = $pdo->prepare("INSERT INTO tokens (token_number, user_id, food_item_id, quantity, estimated_time) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$token_number, $user_id, $food_item_id, $quantity, $estimated_time]);
        
        $token_id = $pdo->lastInsertId();
        
        // Store location if provided
        if ($latitude && $longitude) {
            $stmt = $pdo->prepare("INSERT INTO locations (user_id, token_id, latitude, longitude) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $token_id, $latitude, $longitude]);
        }
        
        // Create notification
        $orderLabel = $order_summary !== '' ? $order_summary : ($food_item['name'] . ' x ' . $quantity);
        $message = "Your token {$token_number} has been created for {$orderLabel}. Estimated waiting time: {$estimated_time} minutes.";
        if ($order_total !== null) {
            $message .= " Order total: $" . number_format($order_total, 2) . ".";
        }
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, token_id, type, message) VALUES (?, ?, 'system', ?)");
        $stmt->execute([$user_id, $token_id, $message]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'token' => [
                'id' => $token_id,
                'token_number' => $token_number,
                'estimated_time' => $estimated_time,
                'queue_position' => $queue_position,
                'food_item' => $food_item['name'],
                'quantity' => $quantity,
                'order_summary' => $orderLabel,
                'order_total' => $order_total
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAuth();
    
    $user = getCurrentUser();
    
    try {
        if ($user['role'] === 'admin') {
            // Get all tokens for admin
            $stmt = $pdo->query("
                SELECT t.*, f.name as food_name, u.name as customer_name, u.phone 
                FROM tokens t 
                JOIN food_items f ON t.food_item_id = f.id 
                LEFT JOIN users u ON t.user_id = u.id 
                ORDER BY t.created_at ASC
            ");
        } else {
            // Get user's tokens
            $stmt = $pdo->prepare("
                SELECT t.*, f.name as food_name 
                FROM tokens t 
                JOIN food_items f ON t.food_item_id = f.id 
                WHERE t.user_id = ? 
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$user['id']]);
        }
        
        $tokens = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'tokens' => $tokens]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}
?>

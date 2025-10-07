<?php
require_once 'headers.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check admin authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user_role = $_SESSION['user_role'];
if ($user_role !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

try {
    // Get all tokens with user and food item details for admin view
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            u.name as user_name,
            u.email as user_email,
            f.name as food_name,
            f.price as food_price,
            f.preparation_time
        FROM tokens t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN food_items f ON t.food_item_id = f.id
        ORDER BY 
            CASE t.status 
                WHEN 'active' THEN 1 
                WHEN 'called' THEN 2 
                WHEN 'completed' THEN 3 
                WHEN 'cancelled' THEN 4 
                ELSE 5 
            END,
            t.created_at ASC
    ");
    $stmt->execute();
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats = [
        'active' => 0,
        'called' => 0,
        'completed_today' => 0,
        'total_customers' => 0
    ];
    
    // Count active and called tokens
    foreach ($tokens as $token) {
        if ($token['status'] === 'active') {
            $stats['active']++;
        } elseif ($token['status'] === 'called') {
            $stats['called']++;
        }
    }
    
    // Count completed today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tokens WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
    $stmt->execute();
    $stats['completed_today'] = $stmt->fetch()['count'];
    
    // Count total unique customers
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as count FROM tokens");
    $stmt->execute();
    $stats['total_customers'] = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'tokens' => $tokens,
        'stats' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("Admin tokens error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
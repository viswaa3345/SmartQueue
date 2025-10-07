<?php
require_once 'api/headers.php';
require_once 'api/db.php';

header('Content-Type: application/json');

try {
    $response = [
        'session_check' => [],
        'database_check' => [],
        'booking_simulation' => []
    ];
    
    // 1. Check session status
    $response['session_check'] = [
        'session_started' => session_status() === PHP_SESSION_ACTIVE,
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
        'user_role' => $_SESSION['user_role'] ?? 'NOT SET',
        'all_session_data' => $_SESSION ?? []
    ];
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // 2. Database check
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tokens WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$user_id]);
        $active_count = $stmt->fetch()['count'];
        
        $stmt = $pdo->prepare("SELECT * FROM tokens WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$user_id]);
        $recent_tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['database_check'] = [
            'user_id' => $user_id,
            'active_token_count' => $active_count,
            'recent_tokens' => $recent_tokens,
            'can_book' => $active_count == 0 ? 'YES' : 'NO'
        ];
        
        // 3. Simulate booking check
        if ($active_count == 0) {
            $response['booking_simulation']['status'] = 'SHOULD_WORK';
            $response['booking_simulation']['message'] = 'No active tokens found, booking should be allowed';
        } else {
            $response['booking_simulation']['status'] = 'BLOCKED';
            $response['booking_simulation']['message'] = 'Active tokens found, booking will be blocked';
        }
    } else {
        $response['database_check'] = 'USER_NOT_LOGGED_IN';
        $response['booking_simulation'] = 'USER_NOT_LOGGED_IN';
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
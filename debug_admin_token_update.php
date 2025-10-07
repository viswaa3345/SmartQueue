<?php
require_once 'api/headers.php';
require_once 'api/db.php';

header('Content-Type: application/json');

try {
    $response = [
        'current_session' => [],
        'admin_token_test' => [],
        'token_list' => []
    ];
    
    // 1. Check current session
    $response['current_session'] = [
        'session_started' => session_status() === PHP_SESSION_ACTIVE,
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
        'user_role' => $_SESSION['user_role'] ?? 'NOT SET',
        'user_email' => $_SESSION['user_email'] ?? 'NOT SET',
        'user_name' => $_SESSION['user_name'] ?? 'NOT SET',
        'all_session_data' => $_SESSION ?? []
    ];
    
    // 2. Test admin_token API call simulation
    if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
        // Get first active token for testing
        $stmt = $pdo->prepare("SELECT id FROM tokens WHERE status = 'active' LIMIT 1");
        $stmt->execute();
        $testToken = $stmt->fetch();
        
        if ($testToken) {
            $response['admin_token_test'] = [
                'status' => 'ready_to_test',
                'test_token_id' => $testToken['id'],
                'message' => 'Admin session valid, found active token to test with'
            ];
        } else {
            $response['admin_token_test'] = [
                'status' => 'no_tokens',
                'message' => 'Admin session valid but no active tokens to test with'
            ];
        }
    } else {
        $response['admin_token_test'] = [
            'status' => 'not_admin',
            'message' => 'User not logged in as admin'
        ];
    }
    
    // 3. Get token list for reference
    $stmt = $pdo->prepare("SELECT id, token_number, status, user_id FROM tokens ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['token_list'] = $tokens;
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
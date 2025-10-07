<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=queue_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check all tokens for user ID 3 (viswaa AP)
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE user_id = 3 ORDER BY created_at DESC");
    $stmt->execute();
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'user_tokens' => $tokens,
        'total_tokens' => count($tokens)
    ];
    
    // Check active tokens specifically
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE user_id = 3 AND status = 'active'");
    $stmt->execute();
    $activeTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['active_tokens'] = $activeTokens;
    $response['active_count'] = count($activeTokens);
    
    // Check if there are any tokens without proper status
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE user_id = 3 AND (status IS NULL OR status = '')");
    $stmt->execute();
    $nullStatusTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['null_status_tokens'] = $nullStatusTokens;
    $response['null_status_count'] = count($nullStatusTokens);
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
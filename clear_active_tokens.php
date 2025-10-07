<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=queue_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CHECKING ACTIVE TOKENS ===\n";
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE user_id = 3");
    $stmt->execute();
    $tokens = $stmt->fetchAll();
    
    if (count($tokens) > 0) {
        echo "Active tokens for user 3:\n";
        foreach ($tokens as $token) {
            echo "Token ID: {$token['id']}, Number: {$token['token_number']}, Status: {$token['status']}\n";
        }
        
        echo "\nClearing active tokens for testing...\n";
        $pdo->exec("DELETE FROM tokens WHERE user_id = 3");
        echo "✅ Active tokens cleared\n";
    } else {
        echo "No active tokens found for user 3\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
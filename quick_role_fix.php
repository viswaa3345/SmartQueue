<?php
header('Content-Type: text/plain');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=queue_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== QUICK ROLE FIX ===\n\n";
    
    // Fix the specific user's role
    $stmt = $pdo->prepare("UPDATE users SET role = 'customer', status = 'active' WHERE email = 'viswaapalanisamy@gmail.com'");
    $result = $stmt->execute();
    
    if ($result) {
        echo "✅ User role fixed successfully!\n";
        
        // Verify the fix
        $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE email = 'viswaapalanisamy@gmail.com'");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "Verified: ID={$user['id']}, Role='{$user['role']}', Status='{$user['status']}'\n";
            echo "\n🎉 LOGIN SHOULD NOW WORK!\n";
            echo "Try logging in with:\n";
            echo "Email: viswaapalanisamy@gmail.com\n";
            echo "Password: viswaa123\n\n";
            echo "⚠️ IMPORTANT: Clear your browser cache (Ctrl+Shift+R) before trying to login!\n";
        }
    } else {
        echo "❌ Failed to update user role\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
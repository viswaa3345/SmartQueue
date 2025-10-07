<?php
// Debug auth login directly
require_once __DIR__ . '/includes/AuthService.php';

header('Content-Type: text/html');
echo "<h1>Debug Auth Login</h1>";

try {
    $auth = new AuthService();
    echo "<p style='color: green;'>✅ AuthService initialized successfully</p>";
    
    $email = 'viswaapalanisamy@gmail.com';
    $password = 'viswaa123';
    $role = 'customer';
    
    echo "<h2>Testing login with:</h2>";
    echo "<ul>";
    echo "<li>Email: $email</li>";
    echo "<li>Password: $password</li>";
    echo "<li>Role: $role</li>";
    echo "</ul>";
    
    $result = $auth->login($email, $password, $role);
    
    echo "<h2>Login Result:</h2>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    
    if ($result['success']) {
        echo "<p style='color: green; font-size: 18px;'>✅ Login Successful!</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ Login Failed: " . $result['message'] . "</p>";
        
        // Additional debugging - check if user exists
        echo "<h3>Debug Info:</h3>";
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=queue_db', 'root', '');
        
        // Check user with customer role
        $stmt1 = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer'");
        $stmt1->execute([$email]);
        $customerUser = $stmt1->fetch(PDO::FETCH_ASSOC);
        echo "<p>User with role 'customer': " . ($customerUser ? "Found" : "Not found") . "</p>";
        
        // Check user with user role  
        $stmt2 = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'user'");
        $stmt2->execute([$email]);
        $userUser = $stmt2->fetch(PDO::FETCH_ASSOC);
        echo "<p>User with role 'user': " . ($userUser ? "Found" : "Not found") . "</p>";
        
        if ($userUser) {
            echo "<p>User data: " . json_encode($userUser) . "</p>";
            echo "<p>Password verification: " . (password_verify($password, $userUser['password']) ? "✅ Valid" : "❌ Invalid") . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
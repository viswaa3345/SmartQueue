<?php
// Check and reset user password for viswaapalanisamy@gmail.com
try {
    $pdo = new PDO('mysql:host=localhost;dbname=queue_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $email = 'viswaapalanisamy@gmail.com';
    
    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<h2>Current User Data:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($user as $key => $value) {
            if ($key === 'password') {
                echo "<tr><td>$key</td><td>" . substr($value, 0, 20) . "... (hashed)</td></tr>";
            } else {
                echo "<tr><td>$key</td><td>$value</td></tr>";
            }
        }
        echo "</table>";
        
        // Update password to a known value
        $newPassword = 'viswaa123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        echo "<h3>Updating password...</h3>";
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $updateStmt->execute([$hashedPassword, $email]);
        
        echo "<p style='color: green;'>✅ Password updated successfully!</p>";
        echo "<p><strong>New login credentials:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> $email</li>";
        echo "<li><strong>Password:</strong> $newPassword</li>";
        echo "<li><strong>Role:</strong> Customer</li>";
        echo "</ul>";
        
        // Test the password
        if (password_verify($newPassword, $hashedPassword)) {
            echo "<p style='color: green;'>✅ Password verification successful!</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification failed!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ User not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
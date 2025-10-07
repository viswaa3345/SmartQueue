<?php
// Fix user role for login issue
try {
    $pdo = new PDO('mysql:host=localhost;dbname=queue_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check current user status
    $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE email = ?");
    $stmt->execute(['viswaapalanisamy@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<h2>Current User Data:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($user as $key => $value) {
            echo "<tr><td>$key</td><td>" . ($value ?: '[NULL/EMPTY]') . "</td></tr>";
        }
        echo "</table>";
        
        // Fix the role if it's not 'user' (which maps to 'customer' in login)
        if ($user['role'] !== 'user') {
            echo "<h3>Fixing role...</h3>";
            $updateStmt = $pdo->prepare("UPDATE users SET role = 'user', status = 'active' WHERE email = ?");
            $updateStmt->execute(['viswaapalanisamy@gmail.com']);
            echo "<p style='color: green;'>✅ Role updated to 'user' and status set to 'active'</p>";
        }
        
        if ($user['status'] !== 'active') {
            echo "<h3>Activating user...</h3>";
            $updateStmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE email = ?");
            $updateStmt->execute(['viswaapalanisamy@gmail.com']);
            echo "<p style='color: green;'>✅ User status set to 'active'</p>";
        }
        
        // Show updated data
        $stmt->execute(['viswaapalanisamy@gmail.com']);
        $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h2>Updated User Data:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($updatedUser as $key => $value) {
            echo "<tr><td>$key</td><td>" . ($value ?: '[NULL/EMPTY]') . "</td></tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ User not found with email: viswaapalanisamy@gmail.com</p>";
        
        // Show all users
        echo "<h3>All users in database:</h3>";
        $allUsers = $pdo->query("SELECT id, name, email, role, status FROM users")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        foreach ($allUsers as $u) {
            echo "<tr><td>{$u['id']}</td><td>{$u['name']}</td><td>{$u['email']}</td><td>{$u['role']}</td><td>{$u['status']}</td></tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=queue_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user already exists
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $checkStmt->execute(['viswaapalanisamy@gmail.com']);
    
    if ($checkStmt->rowCount() > 0) {
        // Update existing user - try both 'customer' and 'user' roles
        try {
            $updateStmt = $pdo->prepare('UPDATE users SET name = ?, password = ?, role = ?, status = ? WHERE email = ?');
            $updateStmt->execute([
                'viswaa AP',
                password_hash('viswaa123', PASSWORD_DEFAULT),
                'customer',
                'active',
                'viswaapalanisamy@gmail.com'
            ]);
        } catch (Exception $e) {
            // Try 'user' role if 'customer' fails  
            $updateStmt = $pdo->prepare('UPDATE users SET name = ?, password = ?, role = ?, status = ? WHERE email = ?');
            $updateStmt->execute([
                'viswaa AP',
                password_hash('viswaa123', PASSWORD_DEFAULT),
                'user',
                'active',
                'viswaapalanisamy@gmail.com'
            ]);
        }
        echo "User viswaapalanisamy@gmail.com updated successfully\n";
    } else {
        // Create new user - try both 'customer' and 'user' roles
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                'viswaa AP', 
                'viswaapalanisamy@gmail.com', 
                password_hash('viswaa123', PASSWORD_DEFAULT), 
                'customer', 
                'active'
            ]);
        } catch (Exception $e) {
            // Try 'user' role if 'customer' fails
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                'viswaa AP', 
                'viswaapalanisamy@gmail.com', 
                password_hash('viswaa123', PASSWORD_DEFAULT), 
                'user', 
                'active'
            ]);
        }
        echo "User viswaapalanisamy@gmail.com created successfully\n";
    }
    
    // Show current user info
    $stmt = $pdo->prepare('SELECT id, name, email, role, status FROM users WHERE email = ?');
    $stmt->execute(['viswaapalanisamy@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Current user data:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Name: " . $user['name'] . "\n";
    echo "Email: " . $user['email'] . "\n";  
    echo "Role: " . $user['role'] . "\n";
    echo "Status: " . $user['status'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
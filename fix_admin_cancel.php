<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=queue_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $response = ['fixes' => [], 'status' => 'success'];
    
    // Fix 1: Check and create admin user
    $stmt = $pdo->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetch()['admin_count'];
    
    if ($adminCount == 0) {
        // Create admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
        $stmt->execute(['Admin User', 'admin@smartqueue.com', $adminPassword]);
        $response['fixes'][] = 'Created admin user: admin@smartqueue.com / admin123';
    } else {
        $response['fixes'][] = 'Admin user already exists';
    }
    
    // Fix 2: Check token cancellation issue - ensure tokens table has proper columns
    try {
        $stmt = $pdo->prepare("DESCRIBE tokens");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('cancelled_at', $columns)) {
            $pdo->exec("ALTER TABLE tokens ADD COLUMN cancelled_at DATETIME NULL");
            $response['fixes'][] = 'Added cancelled_at column to tokens table';
        }
        
        if (!in_array('status', $columns)) {
            $pdo->exec("ALTER TABLE tokens ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
            $response['fixes'][] = 'Added status column to tokens table';
        }
        
        // Update existing tokens to have active status if null
        $stmt = $pdo->prepare("UPDATE tokens SET status = 'active' WHERE status IS NULL OR status = ''");
        $stmt->execute();
        $updatedRows = $stmt->rowCount();
        if ($updatedRows > 0) {
            $response['fixes'][] = "Updated {$updatedRows} tokens to active status";
        }
        
    } catch (Exception $e) {
        $response['fixes'][] = 'Token table structure check failed: ' . $e->getMessage();
    }
    
    // Fix 3: Check notifications table
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'notifications'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE TABLE notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_id INT NULL,
                type VARCHAR(20) DEFAULT 'system',
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (token_id) REFERENCES tokens(id) ON DELETE CASCADE
            )");
            $response['fixes'][] = 'Created notifications table';
        }
    } catch (Exception $e) {
        $response['fixes'][] = 'Notifications table check failed: ' . $e->getMessage();
    }
    
    // Fix 4: Verify current user setup
    $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users ORDER BY role, email");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['users'] = $users;
    $response['login_info'] = [
        'customer' => [
            'email' => 'viswaapalanisamy@gmail.com',
            'password' => 'viswaa123'
        ],
        'admin' => [
            'email' => 'admin@smartqueue.com',
            'password' => 'admin123'
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
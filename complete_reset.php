<?php
// Complete database and system reset tool
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Complete Database Reset and Setup</h1>";

// Database connection parameters
$host = '127.0.0.1';
$username = 'root';
$password = '';

try {
    // Step 1: Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Connected to MySQL server</p>";
    
    // Step 2: Drop and recreate database
    $pdo->exec("DROP DATABASE IF EXISTS queue_db");
    echo "<p>✅ Dropped existing queue_db database</p>";
    
    $pdo->exec("CREATE DATABASE queue_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Created fresh queue_db database</p>";
    
    // Step 3: Connect to the new database
    $pdo = new PDO("mysql:host=$host;dbname=queue_db;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Step 4: Create users table with proper structure
    $createUsersTable = "
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
        phone VARCHAR(20) DEFAULT NULL,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status),
        INDEX idx_email_role (email, role)
    ) ENGINE=InnoDB";
    
    $pdo->exec($createUsersTable);
    echo "<p>✅ Created users table with proper structure</p>";
    
    // Step 5: Create other essential tables
    $createFoodItems = "
    CREATE TABLE food_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category VARCHAR(50) DEFAULT NULL,
        image_url VARCHAR(255) DEFAULT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        preparation_time INT DEFAULT 15,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($createFoodItems);
    echo "<p>✅ Created food_items table</p>";
    
    $createTokens = "
    CREATE TABLE tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_number VARCHAR(10) NOT NULL UNIQUE,
        status ENUM('waiting', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'waiting',
        estimated_time INT DEFAULT 30,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    $pdo->exec($createTokens);
    echo "<p>✅ Created tokens table</p>";
    
    // Step 6: Insert default users
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
    
    $insertAdmin = "INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'admin', 'active', NOW())";
    $stmt = $pdo->prepare($insertAdmin);
    $stmt->execute(['Restaurant Admin', 'admin@restaurant.com', $adminPassword]);
    echo "<p>✅ Created default admin user (admin@restaurant.com / admin123)</p>";
    
    $insertCustomer = "INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'customer', 'active', NOW())";
    $stmt = $pdo->prepare($insertCustomer);
    $stmt->execute(['Test Customer', 'customer@example.com', $customerPassword]);
    echo "<p>✅ Created default customer user (customer@example.com / customer123)</p>";
    
    // Step 7: Verify the setup
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "<p>✅ Verified: $userCount users in database</p>";
    
    $stmt = $pdo->query("SELECT * FROM users");
    echo "<h3>Users in Database:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th></tr>";
    while ($user = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td>" . htmlspecialchars($user['status']) . "</td>";
        echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🎉 Database Setup Complete!</h2>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='test_registration_flow.html' target='_blank'><strong>Test Registration and Login Flow</strong></a></li>";
    echo "<li><a href='index.html' target='_blank'>Go to Main Login Page</a></li>";
    echo "<li>Try registering a new user and then logging in</li>";
    echo "</ol>";
    echo "<h3>Default Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@restaurant.com / admin123</li>";
    echo "<li><strong>Customer:</strong> customer@example.com / customer123</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ General error: " . $e->getMessage() . "</p>";
}
?>
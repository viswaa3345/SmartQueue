<?php
// Quick database setup for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Quick Database Setup</h1>";

// Database connection parameters
$host = '127.0.0.1';
$username = 'root';
$password = '';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Connected to MySQL</p>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS queue_db");
    echo "<p>✅ Database 'queue_db' created</p>";
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=queue_db;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
        phone VARCHAR(20),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status)
    )";
    
    $pdo->exec($createUsersTable);
    echo "<p>✅ Users table created</p>";
    
    // Insert default users (only if they don't exist)
    $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $checkAdmin->execute(['admin@restaurant.com']);
    
    if ($checkAdmin->fetchColumn() == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdmin = "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')";
        $stmt = $pdo->prepare($insertAdmin);
        $stmt->execute(['Restaurant Admin', 'admin@restaurant.com', $adminPassword]);
        echo "<p>✅ Default admin user created (admin@restaurant.com / admin123)</p>";
    } else {
        echo "<p>ℹ️ Default admin user already exists</p>";
    }
    
    $checkCustomer = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $checkCustomer->execute(['customer@example.com']);
    
    if ($checkCustomer->fetchColumn() == 0) {
        $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
        $insertCustomer = "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'customer', 'active')";
        $stmt = $pdo->prepare($insertCustomer);
        $stmt->execute(['Test Customer', 'customer@example.com', $customerPassword]);
        echo "<p>✅ Default customer user created (customer@example.com / customer123)</p>";
    } else {
        echo "<p>ℹ️ Default customer user already exists</p>";
    }
    
    // Create other essential tables
    $createFoodItems = "
    CREATE TABLE IF NOT EXISTS food_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category VARCHAR(50),
        image_url VARCHAR(255),
        is_available BOOLEAN DEFAULT TRUE,
        preparation_time INT DEFAULT 15,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createFoodItems);
    echo "<p>✅ Food items table created</p>";
    
    $createTokens = "
    CREATE TABLE IF NOT EXISTS tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_number VARCHAR(10) UNIQUE NOT NULL,
        status ENUM('waiting', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'waiting',
        estimated_time INT DEFAULT 30,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($createTokens);
    echo "<p>✅ Tokens table created</p>";
    
    echo "<h2>Database Setup Complete!</h2>";
    echo "<p>You can now try logging in with:</p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@restaurant.com / admin123</li>";
    echo "<li><strong>Customer:</strong> customer@example.com / customer123</li>";
    echo "</ul>";
    echo "<p><a href='index.html'>Go to Login Page</a></p>";
    echo "<p><a href='test_login.html'>Test Login System</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ General error: " . $e->getMessage() . "</p>";
}
?>
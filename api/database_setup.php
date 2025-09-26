<?php
require_once 'db.php';

// Database setup script for Smart Queue Token Booking System
try {
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(15),
        role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
        name VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE
    )");

    // Create food_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS food_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(8,2) NOT NULL,
        category VARCHAR(50),
        image_url VARCHAR(255),
        preparation_time INT DEFAULT 5, -- in minutes
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Create tokens table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token_number VARCHAR(10) UNIQUE NOT NULL,
        user_id INT,
        food_item_id INT,
        quantity INT DEFAULT 1,
        status ENUM('active', 'called', 'completed', 'cancelled', 'expired') DEFAULT 'active',
        estimated_time INT, -- in minutes
        called_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        cancelled_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE SET NULL
    )");

    // Create locations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        token_id INT,
        latitude DECIMAL(10,8),
        longitude DECIMAL(11,8),
        address TEXT,
        is_within_range BOOLEAN DEFAULT FALSE,
        distance_from_pickup DECIMAL(8,2), -- in meters
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (token_id) REFERENCES tokens(id) ON DELETE CASCADE
    )");

    // Create notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        token_id INT,
        type ENUM('token_ready', 'proximity_alert', 'token_called', 'token_expired', 'system') DEFAULT 'system',
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        sent_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (token_id) REFERENCES tokens(id) ON DELETE CASCADE
    )");

    // Create settings table for system configuration
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Insert default admin user (password: admin123)
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (username, password, role, name, email) VALUES 
        ('admin', '$admin_password', 'admin', 'System Administrator', 'admin@queue.com')");

    // Insert default settings
    $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES 
        ('pickup_latitude', '0.000000', 'Pickup location latitude'),
        ('pickup_longitude', '0.000000', 'Pickup location longitude'),
        ('pickup_radius', '100', 'Pickup radius in meters'),
        ('token_expiry_time', '60', 'Token expiry time in minutes'),
        ('notification_advance_time', '5', 'Notification advance time in minutes'),
        ('average_service_time', '5', 'Average service time per token in minutes')");

    // Insert sample food items
    $pdo->exec("INSERT IGNORE INTO food_items (name, description, price, category, preparation_time) VALUES 
        ('Burger Deluxe', 'Delicious beef burger with cheese and vegetables', 12.99, 'Main Course', 8),
        ('Chicken Wings', 'Spicy chicken wings with dipping sauce', 9.99, 'Appetizers', 12),
        ('Pizza Margherita', 'Classic pizza with tomato sauce and mozzarella', 15.99, 'Main Course', 15),
        ('French Fries', 'Crispy golden french fries', 4.99, 'Sides', 5),
        ('Coca Cola', 'Refreshing soft drink', 2.99, 'Beverages', 1),
        ('Caesar Salad', 'Fresh salad with caesar dressing', 8.99, 'Salads', 5)");

    echo "Database setup completed successfully!<br>";
    echo "Default admin login: admin / admin123<br>";
    echo "You can now use the queue system.";

} catch (PDOException $e) {
    echo "Database setup failed: " . $e->getMessage();
}
?>
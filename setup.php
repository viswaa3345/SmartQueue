<?php
echo "Smart Queue Token Booking System Setup<br><br>";

// Check if XAMPP MySQL is running
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    echo "✓ MySQL connection successful<br>";
    
    // Try to create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS queue_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database 'queue_db' ready<br>";
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=queue_db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to queue_db<br><br>";
    
    echo "<strong>Running table setup...</strong><br>";
    
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
    echo "✓ Users table created<br>";

    // Create food_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS food_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(8,2) NOT NULL,
        category VARCHAR(50),
        image_url VARCHAR(255),
        preparation_time INT DEFAULT 5,
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "✓ Food items table created<br>";

    // Create tokens table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token_number VARCHAR(10) UNIQUE NOT NULL,
        user_id INT,
        food_item_id INT,
        quantity INT DEFAULT 1,
        status ENUM('active', 'called', 'completed', 'cancelled', 'expired') DEFAULT 'active',
        estimated_time INT,
        called_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        cancelled_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE SET NULL
    )");
    echo "✓ Tokens table created<br>";

    // Create locations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        token_id INT,
        latitude DECIMAL(10,8),
        longitude DECIMAL(11,8),
        address TEXT,
        is_within_range BOOLEAN DEFAULT FALSE,
        distance_from_pickup DECIMAL(8,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (token_id) REFERENCES tokens(id) ON DELETE CASCADE
    )");
    echo "✓ Locations table created<br>";

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
    echo "✓ Notifications table created<br>";

    // Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "✓ Settings table created<br><br>";

    echo "<strong>Inserting sample data...</strong><br>";

    // Insert default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role, name, email) VALUES (?, ?, 'admin', 'System Administrator', 'admin@queue.com')");
    $stmt->execute(['admin', $admin_password]);
    echo "✓ Admin user created (admin/admin123)<br>";

    // Insert default settings
    $settings = [
        ['pickup_latitude', '0.000000', 'Pickup location latitude'],
        ['pickup_longitude', '0.000000', 'Pickup location longitude'],
        ['pickup_radius', '100', 'Pickup radius in meters'],
        ['token_expiry_time', '60', 'Token expiry time in minutes'],
        ['notification_advance_time', '5', 'Notification advance time in minutes'],
        ['average_service_time', '5', 'Average service time per token in minutes']
    ];
    
    foreach ($settings as $setting) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        $stmt->execute($setting);
    }
    echo "✓ Default settings inserted<br>";

    // Insert sample food items
    $food_items = [
        ['Burger Deluxe', 'Delicious beef burger with cheese and vegetables', 12.99, 'Main Course', 8],
        ['Chicken Wings', 'Spicy chicken wings with dipping sauce', 9.99, 'Appetizers', 12],
        ['Pizza Margherita', 'Classic pizza with tomato sauce and mozzarella', 15.99, 'Main Course', 15],
        ['French Fries', 'Crispy golden french fries', 4.99, 'Sides', 5],
        ['Coca Cola', 'Refreshing soft drink', 2.99, 'Beverages', 1],
        ['Caesar Salad', 'Fresh salad with caesar dressing', 8.99, 'Salads', 5]
    ];
    
    foreach ($food_items as $item) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO food_items (name, description, price, category, preparation_time) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($item);
    }
    echo "✓ Sample food items inserted<br><br>";

    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
    echo "<strong>✅ Setup completed successfully!</strong><br><br>";
    echo "<strong>Login credentials:</strong><br>";
    echo "Admin: admin / admin123<br>";
    echo "Customer: Register a new account<br><br>";
    echo "<a href='../index.html' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<strong>❌ Setup failed:</strong><br>";
    echo "Error: " . $e->getMessage() . "<br><br>";
    echo "<strong>Please make sure:</strong><br>";
    echo "1. XAMPP is running<br>";
    echo "2. MySQL/Apache services are started<br>";
    echo "3. No other applications are using port 3306<br>";
    echo "</div>";
}
?>
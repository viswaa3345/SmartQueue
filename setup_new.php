<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>Database Setup</title></head><body>";
echo "<h1>Smart Queue Restaurant - Database Setup</h1>";

try {
    // Connect without database name first
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<p>✓ Connected to MySQL server successfully.</p>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `queue_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✓ Database 'queue_db' created or already exists.</p>";
    
    // Switch to the database
    $pdo->exec("USE `queue_db`");
    
    // Create users table
    $createUsers = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `email` VARCHAR(255) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
        `status` ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `last_login` TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createUsers);
    echo "<p>✓ Users table created successfully.</p>";
    
    // Create food_items table
    $createFoodItems = "
    CREATE TABLE IF NOT EXISTS `food_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `price` DECIMAL(10,2) NOT NULL,
        `image` VARCHAR(255) DEFAULT NULL,
        `category` VARCHAR(50) DEFAULT NULL,
        `preparation_time` INT(11) DEFAULT 15,
        `status` ENUM('available', 'unavailable') DEFAULT 'available',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createFoodItems);
    echo "<p>✓ Food items table created successfully.</p>";
    
    // Create tokens table
    $createTokens = "
    CREATE TABLE IF NOT EXISTS `tokens` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `food_item_id` INT(11) NOT NULL,
        `token_number` INT(11) NOT NULL,
        `quantity` INT(11) DEFAULT 1,
        `status` ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
        `estimated_time` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `pickup_code` VARCHAR(10) DEFAULT NULL,
        `customer_location_lat` DECIMAL(10,8) DEFAULT NULL,
        `customer_location_lng` DECIMAL(11,8) DEFAULT NULL,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`food_item_id`) REFERENCES `food_items`(`id`) ON DELETE CASCADE,
        INDEX `idx_status` (`status`),
        INDEX `idx_user_status` (`user_id`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createTokens);
    echo "<p>✓ Tokens table created successfully.</p>";
    
    // Create notifications table
    $createNotifications = "
    CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `token_id` INT(11) DEFAULT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `type` ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        `status` ENUM('unread', 'read') DEFAULT 'unread',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`token_id`) REFERENCES `tokens`(`id`) ON DELETE SET NULL,
        INDEX `idx_user_status` (`user_id`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createNotifications);
    echo "<p>✓ Notifications table created successfully.</p>";
    
    // Create locations table
    $createLocations = "
    CREATE TABLE IF NOT EXISTS `locations` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `address` TEXT NOT NULL,
        `latitude` DECIMAL(10,8) NOT NULL,
        `longitude` DECIMAL(11,8) NOT NULL,
        `pickup_radius` INT(11) DEFAULT 100,
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createLocations);
    echo "<p>✓ Locations table created successfully.</p>";
    
    // Create system_settings table
    $createSettings = "
    CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `setting_key` VARCHAR(100) NOT NULL,
        `setting_value` TEXT NOT NULL,
        `description` TEXT DEFAULT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createSettings);
    echo "<p>✓ System settings table created successfully.</p>";
    
    // Insert sample food items
    $sampleFoods = [
        ['Burger Deluxe', 'Juicy beef burger with cheese, lettuce, tomato', 12.99, 'Fast Food', 10],
        ['Pizza Margherita', 'Classic pizza with tomato sauce, mozzarella, and basil', 16.99, 'Pizza', 20],
        ['Caesar Salad', 'Fresh romaine lettuce with Caesar dressing and croutons', 9.99, 'Salads', 5],
        ['Grilled Chicken', 'Tender grilled chicken breast with herbs', 14.99, 'Main Course', 15],
        ['French Fries', 'Golden crispy french fries', 4.99, 'Sides', 8],
        ['Chocolate Cake', 'Rich chocolate cake with chocolate frosting', 6.99, 'Desserts', 5]
    ];
    
    $insertFood = "INSERT IGNORE INTO food_items (name, description, price, category, preparation_time) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($insertFood);
    
    foreach ($sampleFoods as $food) {
        $stmt->execute($food);
    }
    echo "<p>✓ Sample food items inserted successfully.</p>";
    
    // Insert default admin user (password: admin123)
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $insertAdmin = "INSERT IGNORE INTO users (email, password, name, role) VALUES ('admin@restaurant.com', ?, 'Restaurant Admin', 'admin')";
    $stmt = $pdo->prepare($insertAdmin);
    $stmt->execute([$adminPassword]);
    echo "<p>✓ Default admin user created (email: admin@restaurant.com, password: admin123).</p>";
    
    // Insert sample customer
    $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
    $insertCustomer = "INSERT IGNORE INTO users (email, password, name, role) VALUES ('customer@example.com', ?, 'John Doe', 'customer')";
    $stmt = $pdo->prepare($insertCustomer);
    $stmt->execute([$customerPassword]);
    echo "<p>✓ Sample customer created (email: customer@example.com, password: customer123).</p>";
    
    // Insert default system settings
    $defaultSettings = [
        ['queue_limit', '50', 'Maximum number of tokens in queue'],
        ['default_prep_time', '15', 'Default preparation time in minutes'],
        ['pickup_radius', '100', 'Pickup radius in meters'],
        ['notification_advance_time', '5', 'Minutes before pickup to send notification'],
        ['restaurant_name', 'Smart Queue Restaurant', 'Restaurant name'],
        ['restaurant_address', '123 Main Street, City, State 12345', 'Restaurant address']
    ];
    
    $insertSetting = "INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($insertSetting);
    
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }
    echo "<p>✓ Default system settings inserted successfully.</p>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>Database setup completed successfully!</h2>";
    echo "<h3>Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@restaurant.com / admin123</li>";
    echo "<li><strong>Customer:</strong> customer@example.com / customer123</li>";
    echo "</ul>";
    echo "<h3>Admin Registration Key:</h3>";
    echo "<code>" . ADMIN_KEY . "</code>";
    echo "<br><br>";
    echo "<a href='index.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database setup failed: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Setup failed: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
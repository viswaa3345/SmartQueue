-- SmartQueue Restaurant Database Schema
-- Generated from PHP application analysis
-- Database: queue_db

-- Create database
CREATE DATABASE IF NOT EXISTS `queue_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `queue_db`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `food_items`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `food_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `category` VARCHAR(50) DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `is_available` BOOLEAN DEFAULT TRUE,
    `status` ENUM('available', 'unavailable') DEFAULT 'available',
    `preparation_time` INT(11) DEFAULT 15,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `tokens`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tokens` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `food_item_id` INT(11) DEFAULT NULL,
    `token_number` VARCHAR(10) NOT NULL UNIQUE,
    `quantity` INT(11) DEFAULT 1,
    `status` ENUM('waiting', 'pending', 'preparing', 'ready', 'completed', 'cancelled', 'active') DEFAULT 'waiting',
    `estimated_time` INT(11) DEFAULT 30,
    `pickup_code` VARCHAR(10) DEFAULT NULL,
    `customer_location_lat` DECIMAL(10,8) DEFAULT NULL,
    `customer_location_lng` DECIMAL(11,8) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`food_item_id`) REFERENCES `food_items`(`id`) ON DELETE SET NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_user_status` (`user_id`, `status`),
    INDEX `idx_token_number` (`token_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `token_id` INT(11) DEFAULT NULL,
    `title` VARCHAR(255) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info', 'success', 'warning', 'error', 'system') DEFAULT 'info',
    `status` ENUM('unread', 'read') DEFAULT 'unread',
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`token_id`) REFERENCES `tokens`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_status` (`user_id`, `status`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `locations`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `locations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `token_id` INT(11) DEFAULT NULL,
    `name` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `latitude` DECIMAL(10,8) NOT NULL,
    `longitude` DECIMAL(11,8) NOT NULL,
    `pickup_radius` INT(11) DEFAULT 100,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`token_id`) REFERENCES `tokens`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_token_id` (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `system_settings`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NOT NULL,
    `description` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert sample data
-- --------------------------------------------------------

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`, `status`, `created_at`) VALUES
('Restaurant Admin', 'admin@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NOW()),
('Test Customer', 'customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active', NOW());

-- Insert sample food items
INSERT IGNORE INTO `food_items` (`name`, `description`, `price`, `category`, `preparation_time`, `is_available`, `created_at`) VALUES
('Pizza Margherita', 'Classic pizza with tomato, mozzarella, and basil', 12.99, 'Pizza', 20, TRUE, NOW()),
('Burger Deluxe', 'Beef burger with cheese, lettuce, tomato, and fries', 14.50, 'Burger', 15, TRUE, NOW()),
('Caesar Salad', 'Fresh romaine lettuce with Caesar dressing and croutons', 9.99, 'Salad', 10, TRUE, NOW()),
('Pasta Carbonara', 'Creamy pasta with bacon and parmesan cheese', 13.99, 'Pasta', 18, TRUE, NOW()),
('Fish & Chips', 'Battered fish with crispy chips and mushy peas', 15.99, 'Seafood', 22, TRUE, NOW()),
('Grilled Chicken', 'Tender grilled chicken breast with herbs', 14.99, 'Main Course', 15, TRUE, NOW()),
('French Fries', 'Golden crispy french fries', 4.99, 'Sides', 8, TRUE, NOW()),
('Chocolate Cake', 'Rich chocolate cake with chocolate frosting', 6.99, 'Desserts', 5, TRUE, NOW());

-- Insert default system settings
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('queue_limit', '50', 'Maximum number of tokens in queue'),
('default_prep_time', '15', 'Default preparation time in minutes'),
('pickup_radius', '100', 'Pickup radius in meters'),
('notification_advance_time', '5', 'Minutes before pickup to send notification'),
('restaurant_name', 'Smart Queue Restaurant', 'Restaurant name'),
('restaurant_address', '123 Main Street, City, State 12345', 'Restaurant address'),
('max_tokens_per_user', '3', 'Maximum active tokens per user'),
('queue_auto_advance', '1', 'Automatically advance queue when tokens are completed');

-- Insert sample location (restaurant location)
INSERT IGNORE INTO `locations` (`name`, `address`, `latitude`, `longitude`, `pickup_radius`, `status`) VALUES
('Main Restaurant', '123 Main Street, City, State 12345', 40.7128, -74.0060, 100, 'active');

-- --------------------------------------------------------
-- Create indexes for better performance
-- --------------------------------------------------------

-- Additional indexes for better query performance
ALTER TABLE `tokens` ADD INDEX `idx_created_at` (`created_at`);
ALTER TABLE `notifications` ADD INDEX `idx_created_at` (`created_at`);
ALTER TABLE `users` ADD INDEX `idx_created_at` (`created_at`);

-- --------------------------------------------------------
-- Views for easier data access
-- --------------------------------------------------------

-- View for active queue
CREATE OR REPLACE VIEW `active_queue` AS
SELECT 
    t.id,
    t.token_number,
    t.status,
    t.estimated_time,
    t.created_at,
    u.name as customer_name,
    u.email as customer_email,
    f.name as food_item,
    f.preparation_time,
    t.quantity
FROM tokens t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN food_items f ON t.food_item_id = f.id
WHERE t.status IN ('waiting', 'preparing', 'ready')
ORDER BY t.created_at ASC;

-- View for user notifications
CREATE OR REPLACE VIEW `user_notifications` AS
SELECT 
    n.id,
    n.title,
    n.message,
    n.type,
    n.status,
    n.created_at,
    u.name as user_name,
    u.email as user_email,
    t.token_number
FROM notifications n
LEFT JOIN users u ON n.user_id = u.id
LEFT JOIN tokens t ON n.token_id = t.id
ORDER BY n.created_at DESC;

-- --------------------------------------------------------
-- Stored procedures for common operations
-- --------------------------------------------------------

DELIMITER //

-- Procedure to generate unique token number
CREATE PROCEDURE IF NOT EXISTS GenerateTokenNumber(OUT token_num VARCHAR(10))
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE temp_token VARCHAR(10);
    
    REPEAT
        SET temp_token = CONCAT('T', LPAD(FLOOR(RAND() * 9999) + 1, 4, '0'));
        SELECT COUNT(*) INTO done FROM tokens WHERE token_number = temp_token;
    UNTIL done = 0 END REPEAT;
    
    SET token_num = temp_token;
END //

-- Procedure to update token status and create notification
CREATE PROCEDURE IF NOT EXISTS UpdateTokenStatus(
    IN token_id INT,
    IN new_status ENUM('waiting', 'pending', 'preparing', 'ready', 'completed', 'cancelled', 'active'),
    IN notification_message TEXT
)
BEGIN
    DECLARE user_id_var INT;
    
    -- Update token status
    UPDATE tokens SET status = new_status, updated_at = NOW() WHERE id = token_id;
    
    -- Get user_id for notification
    SELECT user_id INTO user_id_var FROM tokens WHERE id = token_id;
    
    -- Create notification if message provided
    IF notification_message IS NOT NULL AND notification_message != '' THEN
        INSERT INTO notifications (user_id, token_id, message, type, created_at) 
        VALUES (user_id_var, token_id, notification_message, 'system', NOW());
    END IF;
END //

DELIMITER ;

-- --------------------------------------------------------
-- Triggers for automatic operations
-- --------------------------------------------------------

DELIMITER //

-- Trigger to update user's last_login when a token is created
CREATE TRIGGER IF NOT EXISTS update_user_activity 
AFTER INSERT ON tokens
FOR EACH ROW
BEGIN
    UPDATE users SET last_login = NOW() WHERE id = NEW.user_id;
END //

-- Trigger to create notification when token status changes
CREATE TRIGGER IF NOT EXISTS token_status_notification 
AFTER UPDATE ON tokens
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO notifications (user_id, token_id, message, type, created_at)
        VALUES (
            NEW.user_id, 
            NEW.id, 
            CONCAT('Your token ', NEW.token_number, ' status changed to ', NEW.status), 
            'system', 
            NOW()
        );
    END IF;
END //

DELIMITER ;

-- --------------------------------------------------------
-- Set up database permissions and finalization
-- --------------------------------------------------------

-- Grant necessary permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON queue_db.* TO 'queue_user'@'localhost';

-- Analyze tables for better performance
ANALYZE TABLE users, food_items, tokens, notifications, locations, system_settings;

-- Final comments
-- This database schema supports:
-- 1. User management with role-based access (admin/customer)
-- 2. Food item management with categories and availability
-- 3. Token-based queue system with real-time status tracking
-- 4. Location-based services for customer proximity
-- 5. Notification system for real-time updates
-- 6. System settings for configuration management
-- 7. Comprehensive indexing for optimal performance
-- 8. Views and procedures for complex operations

-- Default login credentials:
-- Admin: admin@restaurant.com / admin123
-- Customer: customer@example.com / customer123
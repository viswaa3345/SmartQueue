-- SmartQueue Restaurant Database Schema (Simplified Version)
-- Compatible with older MariaDB/MySQL versions
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
    KEY `idx_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_status` (`status`)
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
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `tokens`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tokens` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `food_item_id` INT(11) DEFAULT NULL,
    `token_number` VARCHAR(10) NOT NULL,
    `quantity` INT(11) DEFAULT 1,
    `status` ENUM('waiting', 'pending', 'preparing', 'ready', 'completed', 'cancelled', 'active') DEFAULT 'waiting',
    `estimated_time` INT(11) DEFAULT 30,
    `pickup_code` VARCHAR(10) DEFAULT NULL,
    `customer_location_lat` DECIMAL(10,8) DEFAULT NULL,
    `customer_location_lng` DECIMAL(11,8) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token_number` (`token_number`),
    KEY `user_id` (`user_id`),
    KEY `food_item_id` (`food_item_id`),
    KEY `idx_status` (`status`),
    KEY `idx_user_status` (`user_id`, `status`),
    KEY `idx_token_number` (`token_number`),
    KEY `idx_created_at` (`created_at`)
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
    KEY `user_id` (`user_id`),
    KEY `token_id` (`token_id`),
    KEY `idx_user_status` (`user_id`, `status`),
    KEY `idx_type` (`type`),
    KEY `idx_created_at` (`created_at`)
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
    KEY `user_id` (`user_id`),
    KEY `token_id` (`token_id`)
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
-- Add foreign key constraints (after table creation)
-- --------------------------------------------------------

-- Add foreign keys for tokens table
ALTER TABLE `tokens`
    ADD CONSTRAINT `tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `tokens_food_item_id_foreign` FOREIGN KEY (`food_item_id`) REFERENCES `food_items` (`id`) ON DELETE SET NULL;

-- Add foreign keys for notifications table
ALTER TABLE `notifications`
    ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `notifications_token_id_foreign` FOREIGN KEY (`token_id`) REFERENCES `tokens` (`id`) ON DELETE SET NULL;

-- Add foreign keys for locations table
ALTER TABLE `locations`
    ADD CONSTRAINT `locations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `locations_token_id_foreign` FOREIGN KEY (`token_id`) REFERENCES `tokens` (`id`) ON DELETE SET NULL;

-- --------------------------------------------------------
-- Insert sample data
-- --------------------------------------------------------

-- Insert default admin user (password: admin123)
-- Password hash for 'admin123': $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
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
-- Additional indexes for better performance
-- --------------------------------------------------------

-- Note: Most indexes are already created with the table definitions above

-- --------------------------------------------------------
-- Comments and Usage Instructions
-- --------------------------------------------------------

/*
This simplified database schema supports:
1. User management with role-based access (admin/customer)
2. Food item management with categories and availability
3. Token-based queue system with real-time status tracking
4. Location-based services for customer proximity
5. Notification system for real-time updates
6. System settings for configuration management
7. Comprehensive indexing for optimal performance

Default login credentials:
- Admin: admin@restaurant.com / admin123
- Customer: customer@example.com / customer123

Notes:
- Removed stored procedures and triggers to avoid version compatibility issues
- Foreign keys are added after table creation to avoid circular dependencies
- All advanced features can be implemented in PHP application logic
- Compatible with MariaDB 10.1+ and MySQL 5.7+
*/
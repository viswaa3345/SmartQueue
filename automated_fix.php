<?php
// Automated System Fix - Resolves all common issues
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Automated System Fix</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.step { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
.success { background: #d4edda; color: #155724; }
.error { background: #f8d7da; color: #721c24; }
.warning { background: #fff3cd; color: #856404; }
.info { background: #cce7ff; color: #004085; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
</style></head><body>";

echo "<h1>🔧 Automated System Fix</h1>";

$fixCount = 0;
$errors = [];

// Step 1: Database Setup
echo "<div class='step'><h2>Step 1: Database Setup</h2>";
try {
    $host = '127.0.0.1';
    $username = 'root';
    $password = '';
    
    // Connect without database first
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ Connected to MySQL</p>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS queue_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p class='success'>✅ Database 'queue_db' created/verified</p>";
    $fixCount++;
    
    // Connect to specific database
    $pdo = new PDO("mysql:host=$host;dbname=queue_db;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database setup failed: " . $e->getMessage() . "</p>";
    $errors[] = "Database setup failed";
}
echo "</div>";

// Step 2: Create Tables
echo "<div class='step'><h2>Step 2: Create Required Tables</h2>";
if (isset($pdo)) {
    try {
        // Users table
        $createUsers = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
            phone VARCHAR(20) DEFAULT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) ENGINE=InnoDB";
        
        $pdo->exec($createUsers);
        echo "<p class='success'>✅ Users table created/verified</p>";
        $fixCount++;
        
        // Food items table
        $createFoodItems = "
        CREATE TABLE IF NOT EXISTS food_items (
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
        echo "<p class='success'>✅ Food items table created/verified</p>";
        $fixCount++;
        
        // Tokens table
        $createTokens = "
        CREATE TABLE IF NOT EXISTS tokens (
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
        echo "<p class='success'>✅ Tokens table created/verified</p>";
        $fixCount++;
        
        // Notifications table
        $createNotifications = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB";
        
        $pdo->exec($createNotifications);
        echo "<p class='success'>✅ Notifications table created/verified</p>";
        $fixCount++;
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Table creation failed: " . $e->getMessage() . "</p>";
        $errors[] = "Table creation failed";
    }
}
echo "</div>";

// Step 3: Create Default Users
echo "<div class='step'><h2>Step 3: Create Default Users</h2>";
if (isset($pdo)) {
    try {
        // Check and create admin user
        $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND role = 'admin'");
        $checkAdmin->execute(['admin@restaurant.com']);
        
        if ($checkAdmin->fetchColumn() == 0) {
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $insertAdmin = "INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'admin', 'active', NOW())";
            $stmt = $pdo->prepare($insertAdmin);
            $stmt->execute(['Restaurant Admin', 'admin@restaurant.com', $adminPassword]);
            echo "<p class='success'>✅ Default admin created (admin@restaurant.com / admin123)</p>";
            $fixCount++;
        } else {
            echo "<p class='info'>ℹ️ Default admin already exists</p>";
        }
        
        // Check and create customer user
        $checkCustomer = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND role = 'customer'");
        $checkCustomer->execute(['customer@example.com']);
        
        if ($checkCustomer->fetchColumn() == 0) {
            $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
            $insertCustomer = "INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'customer', 'active', NOW())";
            $stmt = $pdo->prepare($insertCustomer);
            $stmt->execute(['Test Customer', 'customer@example.com', $customerPassword]);
            echo "<p class='success'>✅ Default customer created (customer@example.com / customer123)</p>";
            $fixCount++;
        } else {
            echo "<p class='info'>ℹ️ Default customer already exists</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Default user creation failed: " . $e->getMessage() . "</p>";
        $errors[] = "Default user creation failed";
    }
}
echo "</div>";

// Step 4: Create Missing Directories
echo "<div class='step'><h2>Step 4: Create Required Directories</h2>";
$requiredDirs = ['logs', 'assets/css', 'assets/js', 'assets/images'];

foreach ($requiredDirs as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    if (!is_dir($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "<p class='success'>✅ Created directory: $dir</p>";
            $fixCount++;
        } else {
            echo "<p class='error'>❌ Failed to create directory: $dir</p>";
            $errors[] = "Failed to create directory: $dir";
        }
    } else {
        echo "<p class='info'>ℹ️ Directory already exists: $dir</p>";
    }
}
echo "</div>";

// Step 5: Insert Sample Food Items
echo "<div class='step'><h2>Step 5: Create Sample Food Items</h2>";
if (isset($pdo)) {
    try {
        $checkFood = $pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
        
        if ($checkFood == 0) {
            $sampleFoods = [
                ['Pizza Margherita', 'Classic pizza with tomato, mozzarella, and basil', 12.99, 'Pizza', 20],
                ['Burger Deluxe', 'Beef burger with cheese, lettuce, tomato, and fries', 14.50, 'Burger', 15],
                ['Caesar Salad', 'Fresh romaine lettuce with Caesar dressing and croutons', 9.99, 'Salad', 10],
                ['Pasta Carbonara', 'Creamy pasta with bacon and parmesan cheese', 13.99, 'Pasta', 18],
                ['Fish & Chips', 'Battered fish with crispy chips and mushy peas', 15.99, 'Seafood', 22]
            ];
            
            $insertFood = $pdo->prepare("INSERT INTO food_items (name, description, price, category, preparation_time, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            
            foreach ($sampleFoods as $food) {
                $insertFood->execute($food);
            }
            
            echo "<p class='success'>✅ Sample food items created (" . count($sampleFoods) . " items)</p>";
            $fixCount++;
        } else {
            echo "<p class='info'>ℹ️ Food items already exist ($checkFood items)</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Sample food creation failed: " . $e->getMessage() . "</p>";
        $errors[] = "Sample food creation failed";
    }
}
echo "</div>";

// Step 6: Fix Configuration Files
echo "<div class='step'><h2>Step 6: Verify Configuration Files</h2>";
$configFiles = [
    'config/database.php' => '<?php
define(\'DB_HOST\', \'127.0.0.1\');
define(\'DB_NAME\', \'queue_db\');
define(\'DB_USER\', \'root\');
define(\'DB_PASS\', \'\');
define(\'DB_CHARSET\', \'utf8mb4\');

function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        throw new Exception("Database connection failed");
    }
}
?>',
    'config/config.php' => '<?php
// Application Configuration
define(\'APP_NAME\', \'Smart Queue Restaurant\');
define(\'APP_VERSION\', \'1.0.0\');
define(\'ADMIN_SECRET_KEY\', \'ADMIN_2024_SECRET_KEY\');

// Security settings
define(\'SESSION_TIMEOUT\', 3600); // 1 hour
define(\'MAX_LOGIN_ATTEMPTS\', 5);

// Application settings
define(\'DEFAULT_PREP_TIME\', 30);
define(\'MAX_QUEUE_SIZE\', 100);

// Time zone
date_default_timezone_set(\'Asia/Kolkata\');
?>'
];

foreach ($configFiles as $file => $content) {
    $fullPath = __DIR__ . '/' . $file;
    $dir = dirname($fullPath);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (!file_exists($fullPath) || filesize($fullPath) < 100) {
        if (file_put_contents($fullPath, $content)) {
            echo "<p class='success'>✅ Created/fixed configuration file: $file</p>";
            $fixCount++;
        } else {
            echo "<p class='error'>❌ Failed to create configuration file: $file</p>";
            $errors[] = "Failed to create config file: $file";
        }
    } else {
        echo "<p class='info'>ℹ️ Configuration file exists: $file</p>";
    }
}
echo "</div>";

// Final Summary
echo "<div class='step'><h2>📊 Fix Summary</h2>";
if (count($errors) == 0) {
    echo "<p class='success'>🎉 System fix completed successfully!</p>";
    echo "<p class='info'>✅ Applied $fixCount fixes</p>";
    echo "<p class='info'>❌ 0 errors encountered</p>";
} else {
    echo "<p class='warning'>⚠️ System fix completed with some issues:</p>";
    echo "<p class='info'>✅ Applied $fixCount fixes</p>";
    echo "<p class='error'>❌ " . count($errors) . " errors encountered:</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

// Show current system status
if (isset($pdo)) {
    try {
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $foodCount = $pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
        $tokenCount = $pdo->query("SELECT COUNT(*) FROM tokens")->fetchColumn();
        
        echo "<h3>📈 Current System Status:</h3>";
        echo "<ul>";
        echo "<li>Users: $userCount</li>";
        echo "<li>Food Items: $foodCount</li>";
        echo "<li>Tokens: $tokenCount</li>";
        echo "</ul>";
    } catch (PDOException $e) {
        echo "<p class='error'>Could not retrieve system status</p>";
    }
}
echo "</div>";

// Navigation Links
echo "<div class='step'><h2>🚀 Next Steps</h2>";
echo "<p><strong>Your system is now ready! Try these links:</strong></p>";
echo "<p><a href='index.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Main Login Page</a></p>";
echo "<p><a href='register.html' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Registration Page</a></p>";
echo "<p><a href='test_registration_flow.html' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Test System</a></p>";
echo "<p><a href='complete_diagnosis.php' style='background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Run Diagnosis Again</a></p>";

echo "<h3>Default Login Credentials:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@restaurant.com / admin123</li>";
echo "<li><strong>Customer:</strong> customer@example.com / customer123</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>
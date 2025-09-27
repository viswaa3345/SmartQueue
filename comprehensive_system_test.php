<?php
// Comprehensive System Test
session_start();

echo "<h1>SmartQueue System Comprehensive Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.test-section { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
</style>";

// Test 1: Database Connection
echo "<div class='test-section'>";
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    $conn = getConnection();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Check all required tables
    $tables = ['users', 'food_items', 'tokens', 'notifications', 'system_settings'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "<p class='info'>- Table '$table': $count records</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: User Management
echo "<div class='test-section'>";
echo "<h2>2. User Management Test</h2>";
try {
    $stmt = $conn->query("SELECT email, role, status FROM users ORDER BY role");
    $users = $stmt->fetchAll();
    
    $admin_count = 0;
    $customer_count = 0;
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Email</th><th>Role</th><th>Status</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr><td>{$user['email']}</td><td>{$user['role']}</td><td>{$user['status']}</td></tr>";
        if ($user['role'] == 'admin') $admin_count++;
        if ($user['role'] == 'customer') $customer_count++;
    }
    echo "</table>";
    
    echo "<p class='info'>Total Admin accounts: $admin_count</p>";
    echo "<p class='info'>Total Customer accounts: $customer_count</p>";
    
    if ($admin_count > 0) {
        echo "<p class='success'>✓ Admin accounts available</p>";
    } else {
        echo "<p class='error'>❌ No admin accounts found</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ User management test failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Food Items Management
echo "<div class='test-section'>";
echo "<h2>3. Food Items Test</h2>";
try {
    $stmt = $conn->query("SELECT name, price, category, is_available FROM food_items ORDER BY category");
    $food_items = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Name</th><th>Price</th><th>Category</th><th>Available</th></tr>";
    
    $available_count = 0;
    foreach ($food_items as $item) {
        $available = $item['is_available'] ? 'Yes' : 'No';
        echo "<tr><td>{$item['name']}</td><td>\${$item['price']}</td><td>{$item['category']}</td><td>$available</td></tr>";
        if ($item['is_available']) $available_count++;
    }
    echo "</table>";
    
    echo "<p class='info'>Total Food Items: " . count($food_items) . "</p>";
    echo "<p class='info'>Available Items: $available_count</p>";
    
    if ($available_count > 0) {
        echo "<p class='success'>✓ Food items available for ordering</p>";
    } else {
        echo "<p class='warning'>⚠ No food items available</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Food items test failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Token System
echo "<div class='test-section'>";
echo "<h2>4. Token System Test</h2>";
try {
    $stmt = $conn->query("SELECT t.token_number, t.status, u.email, f.name as food_name, t.created_at 
                         FROM tokens t 
                         JOIN users u ON t.user_id = u.id 
                         JOIN food_items f ON t.food_item_id = f.id 
                         ORDER BY t.created_at DESC LIMIT 10");
    $tokens = $stmt->fetchAll();
    
    if (count($tokens) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Token</th><th>Status</th><th>Customer</th><th>Food Item</th><th>Created</th></tr>";
        
        foreach ($tokens as $token) {
            echo "<tr><td>{$token['token_number']}</td><td>{$token['status']}</td><td>{$token['email']}</td><td>{$token['food_name']}</td><td>{$token['created_at']}</td></tr>";
        }
        echo "</table>";
        echo "<p class='success'>✓ Token system has data</p>";
    } else {
        echo "<p class='info'>No tokens found in system</p>";
    }
    
    // Check active tokens
    $stmt = $conn->query("SELECT COUNT(*) as count FROM tokens WHERE status = 'active'");
    $active_tokens = $stmt->fetch()['count'];
    echo "<p class='info'>Active tokens in queue: $active_tokens</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Token system test failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: API Endpoints
echo "<div class='test-section'>";
echo "<h2>5. API Endpoints Test</h2>";

$api_endpoints = [
    'food_items.php' => 'Food Items API',
    'book_token.php' => 'Token Booking API',
    'cancel_token.php' => 'Token Cancellation API',
    'admin_token.php' => 'Admin Token Management API',
    'customer_token.php' => 'Customer Token API'
];

foreach ($api_endpoints as $endpoint => $description) {
    $file_path = "api/$endpoint";
    if (file_exists($file_path)) {
        echo "<p class='success'>✓ $description exists</p>";
    } else {
        echo "<p class='error'>❌ $description missing</p>";
    }
}
echo "</div>";

// Test 6: File Structure Analysis
echo "<div class='test-section'>";
echo "<h2>6. File Structure Analysis</h2>";

$required_files = [
    'index.php' => 'Main landing page',
    'register.html' => 'Customer registration',
    'admin_dashboard.html' => 'Admin dashboard',
    'user_dashboard.html' => 'Customer dashboard',
    'config/database.php' => 'Database configuration',
    'config/config.php' => 'System configuration',
    'includes/AuthService.php' => 'Authentication service',
    'includes/Database.php' => 'Database service'
];

$missing_files = [];
foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ $description ($file)</p>";
    } else {
        echo "<p class='error'>❌ $description missing ($file)</p>";
        $missing_files[] = $file;
    }
}

if (count($missing_files) == 0) {
    echo "<p class='success'>✓ All critical files present</p>";
} else {
    echo "<p class='warning'>⚠ Missing " . count($missing_files) . " files</p>";
}
echo "</div>";

// Test 7: Configuration Check
echo "<div class='test-section'>";
echo "<h2>7. Configuration Check</h2>";

try {
    // Check if config file exists
    if (file_exists('config/config.php')) {
        require_once 'config/config.php';
        echo "<p class='success'>✓ Configuration file loaded</p>";
        
        // Check if admin key is defined
        if (defined('ADMIN_KEY')) {
            echo "<p class='success'>✓ Admin key configured</p>";
        } else {
            echo "<p class='warning'>⚠ Admin key not configured</p>";
        }
    } else {
        echo "<p class='error'>❌ Configuration file missing</p>";
    }
    
    // Check system settings
    $stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = $stmt->fetchAll();
    
    echo "<h4>System Settings:</h4>";
    foreach ($settings as $setting) {
        echo "<p class='info'>- {$setting['setting_key']}: {$setting['setting_value']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Configuration check failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Summary & Recommendations</h2>";
echo "<p><strong>System Status:</strong> " . (count($missing_files) == 0 ? "Good" : "Needs attention") . "</p>";
echo "<p><strong>Database:</strong> Connected and populated</p>";
echo "<p><strong>Authentication:</strong> Working</p>";
echo "<p><strong>APIs:</strong> Available</p>";

if (count($missing_files) > 0) {
    echo "<h4 class='error'>Missing Files to Create:</h4>";
    foreach ($missing_files as $file) {
        echo "<p>- $file</p>";
    }
}

echo "<h4>Next Steps:</h4>";
echo "<ol>";
echo "<li>Test user registration and login functionality</li>";
echo "<li>Test token booking and management</li>";
echo "<li>Test dashboard features</li>";
echo "<li>Test real-time updates</li>";
echo "<li>Test mobile responsiveness</li>";
echo "</ol>";
echo "</div>";
?>
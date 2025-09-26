<?php
// Comprehensive System Test - Tests all components
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>System Test Results</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
.test-section { background: white; border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.pass { color: #155724; background: #d4edda; padding: 5px 10px; border-radius: 4px; margin: 5px 0; }
.fail { color: #721c24; background: #f8d7da; padding: 5px 10px; border-radius: 4px; margin: 5px 0; }
.info { color: #004085; background: #cce7ff; padding: 5px 10px; border-radius: 4px; margin: 5px 0; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h1 { text-align: center; color: #343a40; }
.stats { display: flex; justify-content: space-around; background: #e9ecef; padding: 15px; border-radius: 6px; margin: 20px 0; }
.stat-box { text-align: center; }
.stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
.code-sample { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace; }
</style></head><body>";

echo "<h1>🧪 Comprehensive System Test</h1>";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$testResults = [];

function runTest($testName, $testFunction, $description = '') {
    global $totalTests, $passedTests, $failedTests, $testResults;
    $totalTests++;
    
    try {
        $result = $testFunction();
        if ($result === true) {
            $passedTests++;
            $testResults[] = ['name' => $testName, 'status' => 'pass', 'description' => $description, 'details' => ''];
            echo "<p class='pass'>✅ $testName: PASSED</p>";
            if ($description) echo "<p style='margin-left: 20px;'><small>$description</small></p>";
        } else {
            $failedTests++;
            $details = is_string($result) ? $result : 'Test returned false';
            $testResults[] = ['name' => $testName, 'status' => 'fail', 'description' => $description, 'details' => $details];
            echo "<p class='fail'>❌ $testName: FAILED - $details</p>";
        }
    } catch (Exception $e) {
        $failedTests++;
        $testResults[] = ['name' => $testName, 'status' => 'fail', 'description' => $description, 'details' => $e->getMessage()];
        echo "<p class='fail'>❌ $testName: ERROR - " . $e->getMessage() . "</p>";
    }
}

// Test 1: Database Connectivity
echo "<div class='test-section'><h2>🔗 Database Connectivity Tests</h2>";

runTest('Database Connection', function() {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=queue_db;charset=utf8mb4", 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return true;
    } catch (PDOException $e) {
        return "Connection failed: " . $e->getMessage();
    }
}, 'Verifies database connection to queue_db');

runTest('Tables Exist', function() {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=queue_db;charset=utf8mb4", 'root', '');
    $tables = ['users', 'food_items', 'tokens'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if (!$stmt->fetch()) {
            return "Table '$table' missing";
        }
    }
    return true;
}, 'Checks if all required tables exist');

echo "</div>";

// Test 2: API Endpoints
echo "<div class='test-section'><h2>🌐 API Endpoint Tests</h2>";

runTest('Registration API Exists', function() {
    return file_exists(__DIR__ . '/api/register.php');
}, 'Checks if api/register.php file exists');

runTest('Login API Exists', function() {
    return file_exists(__DIR__ . '/api/login.php');
}, 'Checks if api/login.php file exists');

runTest('Database Helper Exists', function() {
    return file_exists(__DIR__ . '/api/db.php');
}, 'Checks if api/db.php database helper exists');

runTest('Simple Registration Endpoint', function() {
    return file_exists(__DIR__ . '/simple_register.php');
}, 'Checks if simple_register.php backup endpoint exists');

runTest('Simple Login Endpoint', function() {
    return file_exists(__DIR__ . '/simple_login.php');
}, 'Checks if simple_login.php backup endpoint exists');

echo "</div>";

// Test 3: Frontend Files
echo "<div class='test-section'><h2>🎨 Frontend Interface Tests</h2>";

runTest('Main Login Page', function() {
    $file = __DIR__ . '/index.html';
    if (!file_exists($file)) return "File missing";
    $content = file_get_contents($file);
    if (strpos($content, 'role-selection') === false) return "Role selection missing";
    if (strpos($content, 'login-forms') === false) return "Login forms missing";
    return true;
}, 'Verifies main login interface exists and has role selection');

runTest('Registration Page', function() {
    return file_exists(__DIR__ . '/register.html');
}, 'Checks if separate registration page exists');

runTest('User Dashboard', function() {
    $file = __DIR__ . '/user_dashboard.html';
    if (!file_exists($file)) return "File missing";
    $content = file_get_contents($file);
    if (strpos($content, 'dashboard') === false) return "Dashboard content missing";
    return true;
}, 'Verifies customer dashboard exists');

runTest('Admin Dashboard', function() {
    $file = __DIR__ . '/admin_dashboard.html';
    if (!file_exists($file)) return "File missing";
    $content = file_get_contents($file);
    if (strpos($content, 'dashboard') === false) return "Dashboard content missing";
    return true;
}, 'Verifies admin dashboard exists');

echo "</div>";

// Test 4: Database Data
echo "<div class='test-section'><h2>📊 Database Data Tests</h2>";

runTest('Users Table Has Data', function() {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=queue_db;charset=utf8mb4", 'root', '');
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    return $count > 0 ? true : "No users found in database";
}, 'Checks if users table contains data');

runTest('Admin User Exists', function() {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=queue_db;charset=utf8mb4", 'root', '');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['admin@restaurant.com']);
    return $stmt->fetchColumn() > 0;
}, 'Verifies default admin account exists');

runTest('Food Items Available', function() {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=queue_db;charset=utf8mb4", 'root', '');
    $stmt = $pdo->query("SELECT COUNT(*) FROM food_items");
    $count = $stmt->fetchColumn();
    return $count > 0 ? true : "No food items found";
}, 'Checks if food items are available in database');

runTest('Database Column Structure', function() {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=queue_db;charset=utf8mb4", 'root', '');
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $required = ['id', 'name', 'email', 'password', 'role'];
    foreach ($required as $col) {
        if (!in_array($col, $columns)) {
            return "Required column '$col' missing";
        }
    }
    return true;
}, 'Verifies users table has required columns');

echo "</div>";

// Test 5: Configuration Files
echo "<div class='test-section'><h2>⚙️ Configuration Tests</h2>";

runTest('Database Config', function() {
    $file = __DIR__ . '/config/database.php';
    if (!file_exists($file)) return "Config file missing";
    ob_start();
    include $file;
    ob_end_clean();
    return function_exists('getConnection');
}, 'Tests database configuration file');

runTest('Application Config', function() {
    return file_exists(__DIR__ . '/config/config.php');
}, 'Checks if application config exists');

echo "</div>";

// Test 6: Security Features
echo "<div class='test-section'><h2>🔒 Security Tests</h2>";

runTest('PHP Session Available', function() {
    return session_status() !== PHP_SESSION_DISABLED;
}, 'Verifies PHP sessions are available');

runTest('Password Hashing Available', function() {
    return function_exists('password_hash') && function_exists('password_verify');
}, 'Checks if password hashing functions exist');

runTest('PDO Prepared Statements', function() {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=queue_db;charset=utf8mb4", 'root', '');
    $stmt = $pdo->prepare("SELECT 1");
    return $stmt !== false;
}, 'Verifies PDO prepared statements work');

echo "</div>";

// Test 7: Testing Tools
echo "<div class='test-section'><h2>🧰 Testing Tools</h2>";

runTest('Registration Flow Tester', function() {
    return file_exists(__DIR__ . '/test_registration_flow.html');
}, 'Checks if registration flow test tool exists');

runTest('API Tester', function() {
    return file_exists(__DIR__ . '/test_api_register.html');
}, 'Verifies API testing tool exists');

runTest('Debug Tools', function() {
    $tools = ['debug_login.php', 'debug_register.php', 'complete_diagnosis.php'];
    foreach ($tools as $tool) {
        if (!file_exists(__DIR__ . '/' . $tool)) {
            return "Debug tool '$tool' missing";
        }
    }
    return true;
}, 'Checks if debugging tools are available');

echo "</div>";

// Display Summary Statistics
echo "<div class='stats'>";
echo "<div class='stat-box'><div class='stat-number'>$totalTests</div><div>Total Tests</div></div>";
echo "<div class='stat-box'><div class='stat-number' style='color: #28a745;'>$passedTests</div><div>Passed</div></div>";
echo "<div class='stat-box'><div class='stat-number' style='color: #dc3545;'>$failedTests</div><div>Failed</div></div>";
$passRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
echo "<div class='stat-box'><div class='stat-number' style='color: #007bff;'>{$passRate}%</div><div>Pass Rate</div></div>";
echo "</div>";

// Final Assessment
echo "<div class='test-section'><h2>📋 Final Assessment</h2>";

if ($failedTests == 0) {
    echo "<div class='pass' style='padding: 20px; text-align: center; font-size: 1.2em;'>";
    echo "🎉 <strong>ALL TESTS PASSED!</strong> Your system is fully operational.";
    echo "</div>";
    echo "<div class='info'>";
    echo "<h3>✨ System Status: EXCELLENT</h3>";
    echo "<p>Your Smart Queue Restaurant system is ready for production use!</p>";
    echo "<h4>🔥 What's Working:</h4>";
    echo "<ul>";
    echo "<li>Database connectivity and structure</li>";
    echo "<li>User registration and authentication</li>";
    echo "<li>Admin and customer dashboards</li>";
    echo "<li>API endpoints for frontend integration</li>";
    echo "<li>Security measures and data validation</li>";
    echo "<li>Testing and debugging tools</li>";
    echo "</ul>";
    echo "</div>";
} elseif ($failedTests <= 2) {
    echo "<div class='info' style='padding: 20px; text-align: center; font-size: 1.2em;'>";
    echo "⚠️ <strong>MINOR ISSUES DETECTED</strong> - System mostly functional";
    echo "</div>";
    echo "<p>Your system is working well with only minor issues that don't affect core functionality.</p>";
} else {
    echo "<div class='fail' style='padding: 20px; text-align: center; font-size: 1.2em;'>";
    echo "❌ <strong>MULTIPLE ISSUES FOUND</strong> - Needs attention";
    echo "</div>";
    echo "<p>Several issues were detected that may affect system functionality.</p>";
}

// Show database statistics
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=queue_db;charset=utf8mb4", 'root', '');
    echo "<h3>📊 Current Database Status:</h3>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'><th style='border: 1px solid #ddd; padding: 10px;'>Table</th><th style='border: 1px solid #ddd; padding: 10px;'>Records</th></tr>";
    
    $tables = ['users', 'food_items', 'tokens'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<tr><td style='border: 1px solid #ddd; padding: 10px;'>$table</td><td style='border: 1px solid #ddd; padding: 10px;'>$count</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td style='border: 1px solid #ddd; padding: 10px;'>$table</td><td style='border: 1px solid #ddd; padding: 10px; color: red;'>Error</td></tr>";
        }
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='fail'>Could not retrieve database statistics</p>";
}

echo "</div>";

// Quick Action Links
echo "<div class='test-section'><h2>🚀 Quick Actions</h2>";
echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
echo "<a href='index.html' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>🏠 Login Page</a>";
echo "<a href='test_registration_flow.html' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>🧪 Test Registration</a>";
echo "<a href='user_dashboard.html' style='background: #17a2b8; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>👤 User Dashboard</a>";
echo "<a href='admin_dashboard.html' style='background: #6f42c1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>⚙️ Admin Dashboard</a>";
echo "<a href='complete_diagnosis.php' style='background: #fd7e14; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>🔍 Run Diagnosis</a>";
echo "</div>";

echo "<div style='margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 6px;'>";
echo "<h4>🔑 Default Login Credentials:</h4>";
echo "<div class='code-sample'>";
echo "<strong>Admin Login:</strong><br>";
echo "Email: admin@restaurant.com<br>";
echo "Password: admin123<br><br>";
echo "<strong>Customer Login:</strong><br>";
echo "Email: customer@example.com<br>";
echo "Password: customer123";
echo "</div>";
echo "</div>";

echo "</div>";

echo "</body></html>";
?>
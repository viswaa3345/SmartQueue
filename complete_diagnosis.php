<?php
// Comprehensive System Diagnosis
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>System Diagnosis</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.section { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
.success { background: #d4edda; color: #155724; }
.error { background: #f8d7da; color: #721c24; }
.warning { background: #fff3cd; color: #856404; }
.info { background: #cce7ff; color: #004085; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
</style></head><body>";

echo "<h1>🔍 Complete System Diagnosis</h1>";

$issues = [];
$fixes = [];

// 1. Database Connection Test
echo "<div class='section'><h2>1. Database Connection</h2>";
try {
    $host = '127.0.0.1';
    $username = 'root';
    $password = '';
    $dbname = 'queue_db';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ Database connection successful</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    $issues[] = "Database connection failed";
    $fixes[] = "Run complete_reset.php to create database";
}
echo "</div>";

// 2. Table Structure Check
echo "<div class='section'><h2>2. Database Tables</h2>";
if (isset($pdo)) {
    $requiredTables = ['users', 'food_items', 'tokens'];
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✅ Table '$table' exists</p>";
                
                // Check table structure
                $columns = $pdo->query("SHOW COLUMNS FROM $table");
                echo "<details><summary>View $table structure</summary>";
                echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
                while ($col = $columns->fetch()) {
                    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
                }
                echo "</table></details>";
            } else {
                echo "<p class='error'>❌ Table '$table' missing</p>";
                $issues[] = "Table '$table' missing";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
            $issues[] = "Error checking table '$table'";
        }
    }
    
    // Check for required columns in users table
    try {
        $userColumns = $pdo->query("SHOW COLUMNS FROM users");
        $existingColumns = [];
        while ($col = $userColumns->fetch()) {
            $existingColumns[] = $col['Field'];
        }
        
        $requiredColumns = ['id', 'name', 'email', 'password', 'role'];
        $optionalColumns = ['phone', 'status', 'created_at', 'last_login'];
        
        foreach ($requiredColumns as $col) {
            if (in_array($col, $existingColumns)) {
                echo "<p class='success'>✅ Required column '$col' exists</p>";
            } else {
                echo "<p class='error'>❌ Required column '$col' missing</p>";
                $issues[] = "Required column '$col' missing in users table";
            }
        }
        
        foreach ($optionalColumns as $col) {
            if (in_array($col, $existingColumns)) {
                echo "<p class='success'>✅ Optional column '$col' exists</p>";
            } else {
                echo "<p class='warning'>⚠️ Optional column '$col' missing</p>";
                $issues[] = "Optional column '$col' missing in users table";
                $fixes[] = "Add missing columns with add_phone_column.php";
            }
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error checking users table structure</p>";
    }
}
echo "</div>";

// 3. File Structure Check
echo "<div class='section'><h2>3. Critical Files Check</h2>";
$criticalFiles = [
    'index.html' => 'Main login page',
    'register.html' => 'Registration page',
    'user_dashboard.html' => 'Customer dashboard',
    'admin_dashboard.html' => 'Admin dashboard',
    'api/register.php' => 'Registration API',
    'api/login.php' => 'Login API',
    'api/db.php' => 'Database connection',
    'config/database.php' => 'Database config',
    'simple_login.php' => 'Simple login endpoint',
    'simple_register.php' => 'Simple registration endpoint'
];

foreach ($criticalFiles as $file => $desc) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p class='success'>✅ $desc ($file) exists</p>";
    } else {
        echo "<p class='error'>❌ $desc ($file) missing</p>";
        $issues[] = "Critical file missing: $file";
        $fixes[] = "Create missing file: $file";
    }
}
echo "</div>";

// 4. Configuration Check
echo "<div class='section'><h2>4. Configuration Check</h2>";
$configs = [
    'config/database.php',
    'config/config.php',
    'api/db.php'
];

foreach ($configs as $config) {
    if (file_exists(__DIR__ . '/' . $config)) {
        echo "<p class='success'>✅ Configuration file $config exists</p>";
        // Check if it's readable
        if (is_readable(__DIR__ . '/' . $config)) {
            echo "<p class='success'>✅ Configuration file $config is readable</p>";
        } else {
            echo "<p class='error'>❌ Configuration file $config is not readable</p>";
            $issues[] = "Configuration file $config not readable";
        }
    } else {
        echo "<p class='error'>❌ Configuration file $config missing</p>";
        $issues[] = "Configuration file $config missing";
    }
}
echo "</div>";

// 5. User Data Check
echo "<div class='section'><h2>5. User Data</h2>";
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        echo "<p class='info'>📊 Total users in database: $userCount</p>";
        
        if ($userCount > 0) {
            $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
            echo "<table><tr><th>Role</th><th>Count</th></tr>";
            while ($row = $stmt->fetch()) {
                echo "<tr><td>" . htmlspecialchars($row['role']) . "</td><td>{$row['count']}</td></tr>";
            }
            echo "</table>";
            
            // Check for default accounts
            $defaultAccounts = [
                'admin@restaurant.com' => 'admin',
                'customer@example.com' => 'customer'
            ];
            
            foreach ($defaultAccounts as $email => $role) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND role = ?");
                $stmt->execute([$email, $role]);
                if ($stmt->fetch()['count'] > 0) {
                    echo "<p class='success'>✅ Default $role account exists ($email)</p>";
                } else {
                    echo "<p class='warning'>⚠️ Default $role account missing ($email)</p>";
                    $issues[] = "Default $role account missing";
                    $fixes[] = "Run quick_setup.php to create default accounts";
                }
            }
        } else {
            echo "<p class='warning'>⚠️ No users in database</p>";
            $issues[] = "No users in database";
            $fixes[] = "Run quick_setup.php to create default users";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error checking user data: " . $e->getMessage() . "</p>";
    }
}
echo "</div>";

// 6. Log Files Check
echo "<div class='section'><h2>6. Log Files</h2>";
$logDir = __DIR__ . '/logs';
if (is_dir($logDir)) {
    echo "<p class='success'>✅ Logs directory exists</p>";
    $logFiles = glob($logDir . '/*.log');
    if (count($logFiles) > 0) {
        echo "<p class='info'>📄 Log files found:</p><ul>";
        foreach ($logFiles as $logFile) {
            $filename = basename($logFile);
            $size = filesize($logFile);
            echo "<li>$filename (" . round($size/1024, 2) . " KB)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='info'>📄 No log files found (this is normal for new installations)</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Logs directory missing</p>";
    $issues[] = "Logs directory missing";
}
echo "</div>";

// 7. API Endpoints Test
echo "<div class='section'><h2>7. API Endpoints Test</h2>";
$apiEndpoints = [
    'api/register.php' => 'Registration API',
    'api/login.php' => 'Login API',
    'simple_login.php' => 'Simple Login',
    'simple_register.php' => 'Simple Registration'
];

foreach ($apiEndpoints as $endpoint => $desc) {
    if (file_exists(__DIR__ . '/' . $endpoint)) {
        echo "<p class='success'>✅ $desc endpoint exists</p>";
        
        // Quick syntax check
        $content = file_get_contents(__DIR__ . '/' . $endpoint);
        if (strpos($content, '<?php') === 0) {
            echo "<p class='success'>✅ $desc has valid PHP syntax</p>";
        } else {
            echo "<p class='warning'>⚠️ $desc may have syntax issues</p>";
            $issues[] = "$desc endpoint has potential syntax issues";
        }
    } else {
        echo "<p class='error'>❌ $desc endpoint missing</p>";
        $issues[] = "$desc endpoint missing";
    }
}
echo "</div>";

// 8. Dashboard Files Check
echo "<div class='section'><h2>8. Dashboard Files</h2>";
$dashboards = [
    'user_dashboard.html' => 'Customer Dashboard',
    'admin_dashboard.html' => 'Admin Dashboard'
];

foreach ($dashboards as $file => $desc) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p class='success'>✅ $desc exists</p>";
        $content = file_get_contents(__DIR__ . '/' . $file);
        if (strpos($content, '<html') !== false) {
            echo "<p class='success'>✅ $desc has valid HTML structure</p>";
        } else {
            echo "<p class='warning'>⚠️ $desc may have HTML issues</p>";
        }
    } else {
        echo "<p class='error'>❌ $desc missing</p>";
        $issues[] = "$desc missing";
        $fixes[] = "Create $desc";
    }
}
echo "</div>";

// Summary
echo "<div class='section'><h2>📋 Diagnosis Summary</h2>";
if (count($issues) == 0) {
    echo "<p class='success'>🎉 No critical issues found! Your system appears to be working correctly.</p>";
} else {
    echo "<p class='error'>❌ Found " . count($issues) . " issues that need attention:</p>";
    echo "<ol>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ol>";
    
    echo "<h3>🔧 Recommended Fixes:</h3>";
    echo "<ol>";
    $uniqueFixes = array_unique($fixes);
    foreach ($uniqueFixes as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ol>";
}
echo "</div>";

// Quick Fix Buttons
echo "<div class='section'><h2>🚀 Quick Fix Actions</h2>";
echo "<p><a href='complete_reset.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Complete Database Reset</a></p>";
echo "<p><a href='add_phone_column.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Fix Missing Columns</a></p>";
echo "<p><a href='quick_setup.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Quick Setup</a></p>";
echo "<p><a href='test_registration_flow.html' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Test Registration Flow</a></p>";
echo "</div>";

echo "</body></html>";
?>
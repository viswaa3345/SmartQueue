<?php
// Comprehensive system diagnosis tool
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Smart Queue System Diagnosis</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 3px; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 3px; }
    .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 3px; }
    .info { background: #cce7ff; color: #004085; padding: 10px; border-radius: 3px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f8f9fa; }
    .fix-btn { background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; }
</style></head><body>";

echo "<h1>🔍 Smart Queue System Complete Diagnosis</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>";

$issues = [];
$fixes = [];

// 1. Database Connection Test
echo "<div class='section'>";
echo "<h2>1. Database Connection & Structure</h2>";

try {
    $host = '127.0.0.1';
    $username = 'root';
    $password = '';
    $dbname = 'queue_db';
    
    // Test MySQL connection
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ MySQL connection successful</div>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'queue_db'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ Database 'queue_db' exists</div>";
    } else {
        echo "<div class='error'>❌ Database 'queue_db' does not exist</div>";
        $issues[] = "Database 'queue_db' missing";
        $fixes[] = "Create database 'queue_db'";
    }
    
    // Connect to specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check tables
    $expectedTables = ['users', 'food_items', 'tokens'];
    echo "<h3>Database Tables:</h3>";
    echo "<table><tr><th>Table</th><th>Status</th><th>Records</th></tr>";
    
    foreach ($expectedTables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $countStmt->fetch()['count'];
                echo "<tr><td>$table</td><td><span class='success'>✅ Exists</span></td><td>$count</td></tr>";
            } else {
                echo "<tr><td>$table</td><td><span class='error'>❌ Missing</span></td><td>N/A</td></tr>";
                $issues[] = "Table '$table' missing";
                $fixes[] = "Create table '$table'";
            }
        } catch (PDOException $e) {
            echo "<tr><td>$table</td><td><span class='error'>❌ Error</span></td><td>Error: " . $e->getMessage() . "</td></tr>";
        }
    }
    echo "</table>";
    
    // Check users table structure
    if ($pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0) {
        echo "<h3>Users Table Structure:</h3>";
        $columns = $pdo->query("SHOW COLUMNS FROM users");
        echo "<table><tr><th>Field</th><th>Type</th><th>Status</th></tr>";
        
        $requiredColumns = ['id', 'name', 'email', 'password', 'role', 'phone', 'status', 'created_at'];
        $existingColumns = [];
        
        while ($column = $columns->fetch()) {
            $existingColumns[] = $column['Field'];
            echo "<tr><td>" . $column['Field'] . "</td><td>" . $column['Type'] . "</td><td><span class='success'>✅</span></td></tr>";
        }
        
        foreach ($requiredColumns as $reqCol) {
            if (!in_array($reqCol, $existingColumns)) {
                echo "<tr><td>$reqCol</td><td>Missing</td><td><span class='error'>❌ Missing</span></td></tr>";
                $issues[] = "Column '$reqCol' missing from users table";
                $fixes[] = "Add column '$reqCol' to users table";
            }
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    $issues[] = "Database connection failed";
    $fixes[] = "Check MySQL service and configuration";
}
echo "</div>";

// 2. File Structure Check
echo "<div class='section'>";
echo "<h2>2. File Structure Analysis</h2>";

$requiredFiles = [
    'index.html' => 'Main login page',
    'register.html' => 'Registration page',
    'user_dashboard.html' => 'Customer dashboard',
    'admin_dashboard.html' => 'Admin dashboard',
    'api/register.php' => 'Registration API',
    'api/login.php' => 'Login API',
    'api/db.php' => 'Database connection',
    'simple_register.php' => 'Simple registration endpoint',
    'simple_login.php' => 'Simple login endpoint',
    'debug_login.php' => 'Debug login endpoint',
    'enhanced_register.php' => 'Enhanced registration endpoint'
];

echo "<table><tr><th>File</th><th>Description</th><th>Status</th><th>Size</th></tr>";

foreach ($requiredFiles as $file => $desc) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        $status = $size > 0 ? "<span class='success'>✅ OK</span>" : "<span class='warning'>⚠️ Empty</span>";
        echo "<tr><td>$file</td><td>$desc</td><td>$status</td><td>" . number_format($size) . " bytes</td></tr>";
        
        if ($size == 0) {
            $issues[] = "File '$file' is empty";
            $fixes[] = "Restore content for '$file'";
        }
    } else {
        echo "<tr><td>$file</td><td>$desc</td><td><span class='error'>❌ Missing</span></td><td>N/A</td></tr>";
        $issues[] = "File '$file' missing";
        $fixes[] = "Create file '$file'";
    }
}
echo "</table>";
echo "</div>";

// 3. PHP Syntax Check
echo "<div class='section'>";
echo "<h2>3. PHP Files Syntax Check</h2>";

$phpFiles = glob(__DIR__ . '/*.php');
$phpFiles = array_merge($phpFiles, glob(__DIR__ . '/api/*.php'));

echo "<table><tr><th>File</th><th>Syntax Status</th><th>Issues</th></tr>";

foreach ($phpFiles as $phpFile) {
    $fileName = basename($phpFile);
    $output = [];
    $return_var = 0;
    
    exec("php -l \"$phpFile\" 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        echo "<tr><td>$fileName</td><td><span class='success'>✅ Valid</span></td><td>None</td></tr>";
    } else {
        $error = implode(', ', $output);
        echo "<tr><td>$fileName</td><td><span class='error'>❌ Error</span></td><td>$error</td></tr>";
        $issues[] = "PHP syntax error in '$fileName'";
        $fixes[] = "Fix syntax in '$fileName'";
    }
}
echo "</table>";
echo "</div>";

// 4. Configuration Check
echo "<div class='section'>";
echo "<h2>4. Configuration Analysis</h2>";

// Check config files
$configFiles = ['config/config.php', 'config/database.php', 'api/db.php'];
echo "<h3>Configuration Files:</h3>";
echo "<table><tr><th>Config File</th><th>Status</th><th>Issues</th></tr>";

foreach ($configFiles as $configFile) {
    $fullPath = __DIR__ . '/' . $configFile;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        $hasDbConfig = (strpos($content, 'DB_HOST') !== false || strpos($content, '$host') !== false);
        
        if ($hasDbConfig) {
            echo "<tr><td>$configFile</td><td><span class='success'>✅ Valid</span></td><td>Database config found</td></tr>";
        } else {
            echo "<tr><td>$configFile</td><td><span class='warning'>⚠️ Incomplete</span></td><td>No database configuration</td></tr>";
            $issues[] = "Configuration incomplete in '$configFile'";
        }
    } else {
        echo "<tr><td>$configFile</td><td><span class='error'>❌ Missing</span></td><td>File not found</td></tr>";
        $issues[] = "Configuration file '$configFile' missing";
    }
}
echo "</table>";
echo "</div>";

// 5. Endpoint Testing
echo "<div class='section'>";
echo "<h2>5. API Endpoints Testing</h2>";

$endpoints = [
    'simple_register.php' => 'Simple Registration',
    'simple_login.php' => 'Simple Login', 
    'debug_login.php' => 'Debug Login',
    'enhanced_register.php' => 'Enhanced Registration',
    'api/register.php' => 'API Registration',
    'api/login.php' => 'API Login'
];

echo "<table><tr><th>Endpoint</th><th>Description</th><th>Response Status</th><th>Issues</th></tr>";

foreach ($endpoints as $endpoint => $desc) {
    $fullPath = __DIR__ . '/' . $endpoint;
    if (file_exists($fullPath)) {
        // Test by making a minimal request
        $url = "http://localhost/queue_app/$endpoint";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response !== false) {
            echo "<tr><td>$endpoint</td><td>$desc</td><td><span class='success'>✅ Responding</span></td><td>None</td></tr>";
        } else {
            echo "<tr><td>$endpoint</td><td>$desc</td><td><span class='warning'>⚠️ No Response</span></td><td>Check web server</td></tr>";
            $issues[] = "Endpoint '$endpoint' not responding";
        }
    } else {
        echo "<tr><td>$endpoint</td><td>$desc</td><td><span class='error'>❌ Missing</span></td><td>File not found</td></tr>";
    }
}
echo "</table>";
echo "</div>";

// 6. Security Analysis
echo "<div class='section'>";
echo "<h2>6. Security Analysis</h2>";

$securityChecks = [
    'Password hashing' => 'password_hash usage',
    'SQL injection protection' => 'PDO prepared statements',
    'Session management' => 'session_start usage',
    'Input validation' => 'filter_var usage',
    'Error handling' => 'try-catch blocks'
];

echo "<table><tr><th>Security Aspect</th><th>Status</th><th>Files Checked</th></tr>";

// Check for security implementations in PHP files
foreach ($securityChecks as $check => $pattern) {
    $foundFiles = [];
    foreach ($phpFiles as $phpFile) {
        $content = file_get_contents($phpFile);
        if (strpos($content, $pattern) !== false) {
            $foundFiles[] = basename($phpFile);
        }
    }
    
    if (!empty($foundFiles)) {
        echo "<tr><td>$check</td><td><span class='success'>✅ Implemented</span></td><td>" . implode(', ', $foundFiles) . "</td></tr>";
    } else {
        echo "<tr><td>$check</td><td><span class='warning'>⚠️ Not Found</span></td><td>None</td></tr>";
        $issues[] = "Security feature '$check' not implemented";
    }
}
echo "</table>";
echo "</div>";

// Summary
echo "<div class='section'>";
echo "<h2>📋 Diagnosis Summary</h2>";

if (empty($issues)) {
    echo "<div class='success'>";
    echo "<h3>🎉 System Status: HEALTHY</h3>";
    echo "<p>No critical issues found. Your Smart Queue system appears to be functioning correctly.</p>";
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Issues Found: " . count($issues) . "</h3>";
    echo "<ol>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔧 Recommended Fixes:</h3>";
    echo "<ol>";
    foreach ($fixes as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ol>";
    echo "</div>";
}

echo "<h3>Quick Actions:</h3>";
echo "<a href='complete_diagnosis.php' class='fix-btn'>🔄 Re-run Diagnosis</a> ";
echo "<a href='complete_reset.php' class='fix-btn'>🗄️ Complete Database Reset</a> ";
echo "<a href='fix_database_structure.php' class='fix-btn'>🔧 Fix Database Structure</a> ";
echo "<a href='test_registration_flow.html' class='fix-btn'>🧪 Test Registration Flow</a>";

echo "</div>";

echo "</body></html>";
?>

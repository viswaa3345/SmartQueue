<?php
// Add missing phone column to users table
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Add Phone Column to Users Table</h1>";

try {
    $host = '127.0.0.1';
    $username = 'root';
    $password = '';
    $dbname = 'queue_db';
    
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database</p>";
    
    // Check current table structure
    echo "<h2>Current Users Table Structure:</h2>";
    $columns = $pdo->query("SHOW COLUMNS FROM users");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasPhone = false;
    $hasStatus = false;
    while ($column = $columns->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
        
        if ($column['Field'] == 'phone') {
            $hasPhone = true;
        }
        if ($column['Field'] == 'status') {
            $hasStatus = true;
        }
    }
    echo "</table>";
    
    // Add phone column if missing
    if (!$hasPhone) {
        echo "<h2>Adding Missing Phone Column:</h2>";
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER role");
        echo "<p>✅ Added phone column to users table</p>";
    } else {
        echo "<p>✅ Phone column already exists</p>";
    }
    
    // Add status column if missing
    if (!$hasStatus) {
        echo "<h2>Adding Missing Status Column:</h2>";
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER phone");
        echo "<p>✅ Added status column to users table</p>";
        
        // Update existing users to have active status
        $pdo->exec("UPDATE users SET status = 'active' WHERE status IS NULL");
        echo "<p>✅ Updated existing users to active status</p>";
    } else {
        echo "<p>✅ Status column already exists</p>";
    }
    
    // Verify the fix
    echo "<h2>Updated Table Structure:</h2>";
    $columns = $pdo->query("SHOW COLUMNS FROM users");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($column = $columns->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🎉 Database Structure Updated!</h2>";
    echo "<p>The users table now has both phone and status columns.</p>";
    echo "<p><a href='test_api_register.html'>Test API Registration</a></p>";
    echo "<p><a href='index.html'>Go to Main Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<p><strong>Database doesn't exist. Please run:</strong></p>";
        echo "<p><a href='complete_reset.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Complete Database Reset</a></p>";
    }
}
?>
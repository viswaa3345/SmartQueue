<?php
// Quick fix for database column issues
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Structure Fix</h1>";

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
        
        if ($column['Field'] == 'status') {
            $hasStatus = true;
        }
    }
    echo "</table>";
    
    // Add status column if missing
    if (!$hasStatus) {
        echo "<h2>Adding Missing Status Column:</h2>";
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER role");
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
    
    echo "<h2>🎉 Database Structure Fixed!</h2>";
    echo "<p>You can now try registering again.</p>";
    echo "<p><a href='test_registration_flow.html'>Go back to registration test</a></p>";
    echo "<p><a href='register.html'>Go to registration page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    
    // If database doesn't exist, suggest running complete reset
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<p><strong>The database doesn't exist. Please run:</strong></p>";
        echo "<p><a href='complete_reset.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Complete Database Reset</a></p>";
    }
}
?>
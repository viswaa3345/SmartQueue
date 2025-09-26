<?php
echo "<h2>XAMPP MySQL Status Check</h2>";

// Test basic MySQL connection
$host = '127.0.0.1';
$username = 'root';
$password = '';

echo "<h3>Testing MySQL Connection:</h3>";
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ MySQL server is running and accessible<br>";
    
    // Check MySQL version
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "MySQL Version: " . $version['version'] . "<br>";
    
    // List databases
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h4>Available Databases:</h4>";
    echo "<ul>";
    foreach($databases as $db) {
        echo "<li>$db</li>";
    }
    echo "</ul>";
    
    // Check if queue_db exists
    if (in_array('queue_db', $databases)) {
        echo "✅ queue_db database exists<br>";
        
        // Connect to queue_db and check tables
        $pdo = new PDO("mysql:host=$host;dbname=queue_db;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h4>Tables in queue_db:</h4>";
        if (count($tables) > 0) {
            echo "<ul>";
            foreach($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
            
            // Check users table specifically
            if (in_array('users', $tables)) {
                echo "✅ users table exists<br>";
                
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch();
                echo "Current users count: " . $result['count'] . "<br>";
                
                // Show table structure
                echo "<h4>Users table structure:</h4>";
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll();
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                foreach($columns as $col) {
                    echo "<tr>";
                    echo "<td>{$col['Field']}</td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>{$col['Key']}</td>";
                    echo "<td>{$col['Default']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
            } else {
                echo "❌ users table does not exist<br>";
                echo "<strong>Action needed:</strong> <a href='setup_new.php'>Run Database Setup</a><br>";
            }
        } else {
            echo "❌ No tables found in queue_db<br>";
            echo "<strong>Action needed:</strong> <a href='setup_new.php'>Run Database Setup</a><br>";
        }
        
    } else {
        echo "❌ queue_db database does not exist<br>";
        echo "<strong>Action needed:</strong> <a href='setup_new.php'>Run Database Setup</a><br>";
    }
    
} catch (PDOException $e) {
    echo "❌ MySQL connection failed: " . $e->getMessage() . "<br>";
    echo "<strong>Possible issues:</strong><br>";
    echo "<ul>";
    echo "<li>XAMPP MySQL service is not running</li>";
    echo "<li>MySQL is running on a different port</li>";
    echo "<li>MySQL credentials are incorrect</li>";
    echo "</ul>";
    echo "<strong>Solutions:</strong><br>";
    echo "<ul>";
    echo "<li>Start XAMPP Control Panel and start MySQL service</li>";
    echo "<li>Check if MySQL is running on port 3306</li>";
    echo "<li>Verify MySQL root password (should be empty for XAMPP)</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<a href='setup_new.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; margin: 5px;'>Setup Database</a> ";
echo "<a href='register.html' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; margin: 5px;'>Go to Registration</a> ";
echo "<a href='test_registration_direct.html' style='background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; margin: 5px;'>Test Registration</a>";
?>
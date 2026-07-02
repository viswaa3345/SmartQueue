<?php
// Check database connection and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Database Connection Test</h2>";

// Database connection parameters
$host = '127.0.0.1';
$username = 'root';
$password = '';

echo "<h3>Step 1: Testing MySQL Connection</h3>";
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ MySQL connection successful<br>";
} catch (PDOException $e) {
    echo "❌ MySQL connection failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Step 2: Checking if queue_db database exists</h3>";
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE 'queue_db'");
    $result = $stmt->fetch();
    if ($result) {
        echo "✅ Database 'queue_db' exists<br>";
    } else {
        echo "❌ Database 'queue_db' does not exist<br>";
        echo "<p>Creating database...</p>";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS queue_db");
        echo "✅ Database 'queue_db' created<br>";
    }
} catch (PDOException $e) {
    echo "❌ Database check failed: " . $e->getMessage() . "<br>";
}

echo "<h3>Step 3: Connecting to queue_db</h3>";
try {
    $pdo = new PDO("mysql:host=$host;dbname=queue_db;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to queue_db successfully<br>";
} catch (PDOException $e) {
    echo "❌ Connection to queue_db failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Step 4: Checking if users table exists</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $result = $stmt->fetch();
    if ($result) {
        echo "✅ Table 'users' exists<br>";

        $columns = [];
        $columnStmt = $pdo->query("SHOW COLUMNS FROM users");
        while ($column = $columnStmt->fetch()) {
            $columns[] = $column['Field'];
        }
        
        // Check if there are any users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "📊 Number of users in database: $count<br>";
        
        if ($count > 0) {
            echo "<h4>Sample users:</h4>";
            $selectFields = ['id'];
            foreach (['email', 'username', 'name', 'role'] as $field) {
                if (in_array($field, $columns, true)) {
                    $selectFields[] = $field;
                }
            }
            if (in_array('status', $columns, true)) {
                $selectFields[] = 'status';
            } elseif (in_array('is_active', $columns, true)) {
                $selectFields[] = 'is_active';
            }

            $stmt = $pdo->query("SELECT " . implode(', ', $selectFields) . " FROM users LIMIT 5");
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr>";
            foreach ($selectFields as $field) {
                echo "<th>" . htmlspecialchars(strtoupper($field)) . "</th>";
            }
            echo "</tr>";
            while ($user = $stmt->fetch()) {
                echo "<tr>";
                foreach ($selectFields as $field) {
                    echo "<td>" . htmlspecialchars((string)($user[$field] ?? '')) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "❌ Table 'users' does not exist<br>";
        echo "<p>You need to run the database setup. <a href='setup_new.php'>Click here to set up the database</a></p>";
    }
} catch (PDOException $e) {
    echo "❌ Users table check failed: " . $e->getMessage() . "<br>";
}

echo "<h3>Step 5: Testing a simple query</h3>";
try {
    $stmt = $pdo->query("SELECT NOW() as server_time");
    $result = $stmt->fetch();
    echo "✅ Database query successful. Current time: " . $result['server_time'] . "<br>";
} catch (PDOException $e) {
    echo "❌ Query test failed: " . $e->getMessage() . "<br>";
}
?>
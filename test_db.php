<?php
echo "<h2>Database Connection Test</h2>";

// Test direct connection
$host = '127.0.0.1';
$dbname = 'queue_db';
$username = 'root';
$password = '';

echo "<h3>Testing direct connection...</h3>";
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ MySQL connection successful<br>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database '$dbname' exists<br>";
    } else {
        echo "✗ Database '$dbname' does not exist. Creating...<br>";
        $pdo->exec("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database '$dbname' created<br>";
    }
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database '$dbname'<br>";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Users table exists<br>";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Users table structure:</h4><ul>";
        foreach ($columns as $col) {
            echo "<li>{$col['Field']} - {$col['Type']} - {$col['Null']} - {$col['Key']}</li>";
        }
        echo "</ul>";
        
        // Count existing users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "Current user count: " . $result['count'] . "<br>";
        
    } else {
        echo "✗ Users table does not exist<br>";
        echo "<a href='setup_new.php'>Run database setup</a><br>";
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h3>Testing registration function...</h3>";
if (isset($_POST['test_register'])) {
    try {
        // Test registration
        $email = 'test' . time() . '@example.com';
        $password = password_hash('test123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$email, $password, 'Test User', 'customer']);
        
        if ($result) {
            echo "✓ Test user created successfully with email: $email<br>";
        } else {
            echo "✗ Failed to create test user<br>";
        }
        
    } catch (Exception $e) {
        echo "✗ Registration test failed: " . $e->getMessage() . "<br>";
    }
}
?>

<form method="POST">
    <button type="submit" name="test_register">Test User Registration</button>
</form>

<hr>
<h3>Quick Actions:</h3>
<a href="setup_new.php">Run Database Setup</a> | 
<a href="register.html">Go to Registration Page</a> | 
<a href="index.html">Go to Login Page</a>
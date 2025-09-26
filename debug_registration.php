<?php
echo "<h2>Registration Debug Test</h2>";

// Test 1: Check file paths
echo "<h3>1. File Path Tests:</h3>";
echo "Current directory: " . __DIR__ . "<br>";
echo "AuthService path: " . __DIR__ . '/../includes/AuthService.php' . "<br>";
echo "AuthService exists: " . (file_exists(__DIR__ . '/../includes/AuthService.php') ? 'YES' : 'NO') . "<br>";
echo "Config path: " . __DIR__ . '/../config/config.php' . "<br>";
echo "Config exists: " . (file_exists(__DIR__ . '/../config/config.php') ? 'YES' : 'NO') . "<br>";
echo "Database path: " . __DIR__ . '/../config/database.php' . "<br>";
echo "Database config exists: " . (file_exists(__DIR__ . '/../config/database.php') ? 'YES' : 'NO') . "<br>";

echo "<hr>";

// Test 2: Try to include files
echo "<h3>2. Include Tests:</h3>";
try {
    require_once __DIR__ . '/../config/config.php';
    echo "✓ Config loaded successfully<br>";
    echo "Site name: " . SITE_NAME . "<br>";
    echo "Admin key: " . ADMIN_KEY . "<br>";
} catch (Exception $e) {
    echo "✗ Config error: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/../config/database.php';
    echo "✓ Database config loaded<br>";
    echo "DB Host: " . DB_HOST . "<br>";
    echo "DB Name: " . DB_NAME . "<br>";
} catch (Exception $e) {
    echo "✗ Database config error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 3: Database connection
echo "<h3>3. Database Connection Test:</h3>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✓ Database connection successful<br>";
    
    // Test if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Users table exists<br>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll();
        echo "Users table columns:<br>";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
    } else {
        echo "✗ Users table does not exist<br>";
    }
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 4: AuthService class
echo "<h3>4. AuthService Test:</h3>";
try {
    require_once __DIR__ . '/../includes/Database.php';
    require_once __DIR__ . '/../includes/AuthService.php';
    
    $auth = new AuthService();
    echo "✓ AuthService created successfully<br>";
    
    // Test registration with sample data
    echo "<h4>Testing Registration:</h4>";
    $result = $auth->register(
        'test@example.com',
        'test123',
        'Test User',
        'customer',
        ''
    );
    
    echo "Registration result: <pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "✗ AuthService error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
<?php
// Simple database connection test
echo "<h2>Quick Database Connection Test</h2>";

try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=queue_db;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
    $result = $stmt->fetch();
    
    echo "<p>Total users in database: " . $result['user_count'] . "</p>";
    echo "<p style='color: green;'>✓ Database is ready for registration!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}
?>
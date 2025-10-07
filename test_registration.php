<?php
// Test registration functionality
echo "<h2>Registration Test</h2>";

// Simulate a POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'name' => 'Test User',
    'email' => 'testuser@example.com',
    'password' => 'test123',
    'role' => 'customer'
];

// Capture the output
ob_start();
include 'enhanced_register.php';
$output = ob_get_clean();

echo "<h3>Registration Result:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Also check if the user was created
echo "<h3>Database Check:</h3>";
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=queue_db;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT email, name, role, status FROM users WHERE email = ?");
    $stmt->execute(['testuser@example.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p style='color: green;'>✓ User found in database:</p>";
        echo "<pre>" . print_r($user, true) . "</pre>";
    } else {
        echo "<p style='color: orange;'>User not found in database (may be expected if registration failed)</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database check error: " . $e->getMessage() . "</p>";
}
?>
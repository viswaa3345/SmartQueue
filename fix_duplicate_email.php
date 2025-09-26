<?php
// Fix duplicate email constraint issue
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Duplicate Email Constraint</h1>";

try {
    $host = '127.0.0.1';
    $username = 'root';
    $password = '';
    $dbname = 'queue_db';
    
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database</p>";
    
    // Check current indexes on users table
    echo "<h2>Current Indexes on Users Table:</h2>";
    $indexes = $pdo->query("SHOW INDEX FROM users");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Key Name</th><th>Column</th><th>Unique</th><th>Type</th></tr>";
    
    $emailIndexFound = false;
    while ($index = $indexes->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Column_name']) . "</td>";
        echo "<td>" . ($index['Non_unique'] == 0 ? 'YES' : 'NO') . "</td>";
        echo "<td>" . htmlspecialchars($index['Index_type']) . "</td>";
        echo "</tr>";
        
        if ($index['Column_name'] == 'email' && $index['Non_unique'] == 0) {
            $emailIndexFound = $index['Key_name'];
        }
    }
    echo "</table>";
    
    // Remove unique constraint on email if it exists
    if ($emailIndexFound) {
        echo "<h2>Removing Unique Constraint on Email:</h2>";
        echo "<p>Found unique index on email: $emailIndexFound</p>";
        
        // Drop the unique constraint
        $pdo->exec("ALTER TABLE users DROP INDEX $emailIndexFound");
        echo "<p>✅ Removed unique constraint on email column</p>";
        
        // Add a regular index instead (optional, for performance)
        $pdo->exec("ALTER TABLE users ADD INDEX idx_email_nonunique (email)");
        echo "<p>✅ Added non-unique index on email for performance</p>";
    } else {
        echo "<p>✅ No unique constraint found on email column</p>";
    }
    
    // Show updated indexes
    echo "<h2>Updated Indexes:</h2>";
    $indexes = $pdo->query("SHOW INDEX FROM users");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Key Name</th><th>Column</th><th>Unique</th><th>Type</th></tr>";
    while ($index = $indexes->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Column_name']) . "</td>";
        echo "<td>" . ($index['Non_unique'] == 0 ? 'YES' : 'NO') . "</td>";
        echo "<td>" . htmlspecialchars($index['Index_type']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test duplicate email registration
    echo "<h2>Test: Can We Register Duplicate Emails Now?</h2>";
    
    // Check if the problematic email exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->execute(['viswaapalanisamy@gmail.com']);
    $count = $stmt->fetch()['count'];
    echo "<p>Current accounts with email 'viswaapalanisamy@gmail.com': $count</p>";
    
    if ($count > 0) {
        echo "<p>✅ Email already exists, duplicates should now be allowed</p>";
    }
    
    echo "<h2>🎉 Duplicate Email Constraint Fixed!</h2>";
    echo "<p>You can now register multiple accounts with the same email address.</p>";
    echo "<p><a href='test_registration_flow.html'>Go back to registration test</a></p>";
    echo "<p><a href='register.html'>Go to registration page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    
    // Additional help for common issues
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<p><strong>Database doesn't exist. Please run:</strong></p>";
        echo "<p><a href='complete_reset.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Complete Database Reset</a></p>";
    }
}
?>
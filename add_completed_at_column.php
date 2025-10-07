<?php
// Add completed_at column to tokens table

require_once 'api/db.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column already exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM tokens LIKE 'completed_at'");
    
    if ($checkColumn->rowCount() == 0) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE tokens ADD COLUMN completed_at DATETIME DEFAULT NULL";
        $pdo->exec($sql);
        echo "✅ Successfully added 'completed_at' column to tokens table!<br>";
        
        // Also check if we need other timestamp columns that might be missing
        $columns = [
            'called_at' => 'DATETIME DEFAULT NULL',
            'cancelled_at' => 'DATETIME DEFAULT NULL'
        ];
        
        foreach ($columns as $column => $definition) {
            $checkCol = $pdo->query("SHOW COLUMNS FROM tokens LIKE '$column'");
            if ($checkCol->rowCount() == 0) {
                $sql = "ALTER TABLE tokens ADD COLUMN $column $definition";
                $pdo->exec($sql);
                echo "✅ Successfully added '$column' column to tokens table!<br>";
            } else {
                echo "ℹ️ Column '$column' already exists.<br>";
            }
        }
        
    } else {
        echo "ℹ️ Column 'completed_at' already exists in tokens table.<br>";
    }
    
    // Show current table structure
    echo "<br><h3>Current tokens table structure:</h3>";
    $columns = $pdo->query("DESCRIBE tokens");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Please make sure your database connection is working and try again.";
}
?>

<br><br>
<a href="admin_dashboard.html">← Back to Admin Dashboard</a>

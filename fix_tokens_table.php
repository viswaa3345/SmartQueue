<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=queue_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Fixing tokens table...\n";
    
    // Check if food_item_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM tokens LIKE 'food_item_id'");
    if ($stmt->rowCount() == 0) {
        echo "Adding food_item_id column...\n";
        $pdo->exec("ALTER TABLE tokens ADD COLUMN food_item_id INT NULL AFTER user_id");
        echo "✅ food_item_id column added\n";
    } else {
        echo "✅ food_item_id column already exists\n";
    }
    
    // Check if quantity column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM tokens LIKE 'quantity'");
    if ($stmt->rowCount() == 0) {
        echo "Adding quantity column...\n";
        $pdo->exec("ALTER TABLE tokens ADD COLUMN quantity INT DEFAULT 1 AFTER food_item_id");
        echo "✅ quantity column added\n";
    } else {
        echo "✅ quantity column already exists\n";
    }
    
    // Update status enum to match book_token.php expectations
    echo "Updating status enum...\n";
    $pdo->exec("ALTER TABLE tokens MODIFY COLUMN status ENUM('active', 'called', 'completed', 'cancelled') DEFAULT 'active'");
    echo "✅ status enum updated\n";
    
    // Create notifications table if it doesn't exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() == 0) {
        echo "Creating notifications table...\n";
        $pdo->exec("
            CREATE TABLE notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_id INT NULL,
                type VARCHAR(50) DEFAULT 'system',
                message TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "✅ notifications table created\n";
    } else {
        echo "✅ notifications table already exists\n";
    }
    
    // Create locations table if it doesn't exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'locations'");
    if ($stmt->rowCount() == 0) {
        echo "Creating locations table...\n";
        $pdo->exec("
            CREATE TABLE locations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_id INT NULL,
                latitude DECIMAL(10, 8) NULL,
                longitude DECIMAL(11, 8) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "✅ locations table created\n";
    } else {
        echo "✅ locations table already exists\n";
    }
    
    echo "\nDatabase structure fixed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
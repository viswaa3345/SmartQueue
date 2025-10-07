<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=queue_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Clear existing food items
    $stmt = $pdo->prepare('DELETE FROM food_items');
    $stmt->execute();
    
    echo "Food items cleared successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
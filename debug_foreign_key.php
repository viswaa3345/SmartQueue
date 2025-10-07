<?php
session_start();

echo "=== SESSION DEBUG ===\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'not set') . "\n";
echo "Session user_email: " . ($_SESSION['user_email'] ?? 'not set') . "\n";
echo "Session user_role: " . ($_SESSION['user_role'] ?? 'not set') . "\n";
echo "Session logged_in: " . ($_SESSION['logged_in'] ?? 'not set') . "\n";

echo "\n=== DATABASE USERS ===\n";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=queue_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Email: {$user['email']}, Name: {$user['name']}, Role: {$user['role']}\n";
    }
    
    echo "\n=== SESSION VS DATABASE MATCH ===\n";
    if (isset($_SESSION['user_id'])) {
        $sessionUserId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$sessionUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ Session user ID {$sessionUserId} exists in database\n";
            echo "User: {$user['name']} ({$user['email']})\n";
        } else {
            echo "❌ Session user ID {$sessionUserId} NOT found in database\n";
        }
    } else {
        echo "❌ No user_id in session\n";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>

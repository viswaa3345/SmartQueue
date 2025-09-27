<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // default XAMPP password is empty
$charset = 'utf8mb4';

try {
    // Connect without specifying database to create it
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS queue_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database 'queue_db' created successfully or already exists.<br>";
    
    // Now connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=queue_db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to queue_db successfully!<br>";
    
    // Now run the table creation and data insertion from database_setup.php
    include 'database_setup.php';
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<?php
// Enhanced debug registration endpoint
header('Content-Type: application/json');

// Enable error reporting and logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Detailed logging function
function logError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] REGISTRATION: $message" . PHP_EOL;
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    file_put_contents(__DIR__ . '/logs/registration_debug.log', $logMessage, FILE_APPEND | LOCK_EX);
}

logError("Registration attempt started");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logError("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Database connection
    $host = '127.0.0.1';
    $dbname = 'queue_db';
    $username = 'root';
    $password = '';
    
    logError("Attempting database connection to $host/$dbname");
    
    // First check if database exists
    try {
        $pdoTest = new PDO("mysql:host=$host", $username, $password);
        $pdoTest->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdoTest->exec("CREATE DATABASE IF NOT EXISTS queue_db");
        logError("Database queue_db ensured to exist");
        
    } catch (PDOException $e) {
        logError("Failed to create database: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database creation failed: ' . $e->getMessage()]);
        exit;
    }
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logError("Database connection successful");
    
    // Ensure users table exists with proper structure
    $createTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
        phone VARCHAR(20),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status)
    )";
    
    $pdo->exec($createTable);
    logError("Users table ensured to exist");
    
    // Check if status column exists, if not add it
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
        if ($checkColumn->rowCount() == 0) {
            logError("Status column missing, adding it");
            $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER role");
            logError("Status column added successfully");
        }
    } catch (PDOException $e) {
        logError("Error checking/adding status column: " . $e->getMessage());
    }
    
    // Remove unique constraint on email if it exists to allow duplicates
    try {
        $indexes = $pdo->query("SHOW INDEX FROM users WHERE Column_name = 'email' AND Non_unique = 0");
        if ($indexes->rowCount() > 0) {
            $index = $indexes->fetch();
            $indexName = $index['Key_name'];
            logError("Found unique constraint on email: $indexName, removing it");
            $pdo->exec("ALTER TABLE users DROP INDEX $indexName");
            logError("Unique constraint on email removed successfully");
        }
    } catch (PDOException $e) {
        logError("Error checking/removing email unique constraint: " . $e->getMessage());
    }
    
    // Get POST data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $adminKey = $_POST['adminKey'] ?? '';
    
    logError("Registration data - Name: $name, Email: $email, Role: $role, Password length: " . strlen($pass));
    
    // Clean email
    $email = preg_replace('/[^\x20-\x7E]/', '', $email);
    $email = str_replace(["\r", "\n", "\t"], '', $email);
    $email = trim($email);
    
    logError("Cleaned email: $email");
    
    // Basic validation
    if (empty($name) || empty($email) || empty($pass)) {
        logError("Validation failed - missing required fields");
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logError("Email validation failed: $email");
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    if (strlen($pass) < 6) {
        logError("Password too short: " . strlen($pass));
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }
    
    // Check admin key for admin registration
    if ($role === 'admin' && $adminKey !== 'ADMIN_2024_SECRET_KEY') {
        logError("Invalid admin key provided: $adminKey");
        echo json_encode(['success' => false, 'message' => 'Invalid admin key']);
        exit;
    }
    
    logError("Validation passed");
    
    // Hash password
    $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
    logError("Password hashed successfully");
    
    // Insert user (allowing duplicate emails as requested)
    // First try with status column, fallback without it if it doesn't exist
    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
        $result = $stmt->execute([$email, $hashedPassword, $name, $role]);
        logError("Insert with status column successful");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Unknown column 'status'") !== false) {
            logError("Status column not found, trying without it");
            $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$email, $hashedPassword, $name, $role]);
            logError("Insert without status column successful");
        } else {
            throw $e;
        }
    }
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        logError("Registration successful - User ID: $userId");
        
        // Verify the user was actually inserted
        $verifyStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $verifyStmt->execute([$userId]);
        $newUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($newUser) {
            logError("User verified in database: " . json_encode($newUser));
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! You can now login.',
                'user_id' => $userId,
                'debug_info' => [
                    'inserted_email' => $newUser['email'],
                    'inserted_role' => $newUser['role'],
                    'inserted_status' => $newUser['status']
                ]
            ]);
        } else {
            logError("User not found after insertion - possible database issue");
            echo json_encode(['success' => false, 'message' => 'Registration completed but user verification failed']);
        }
    } else {
        logError("Insert statement failed");
        echo json_encode(['success' => false, 'message' => 'Registration failed - insert error']);
    }
    
} catch (PDOException $e) {
    logError("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    logError("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
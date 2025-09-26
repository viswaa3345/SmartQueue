<?php
// Enhanced login with detailed error logging
header('Content-Type: application/json');

// Enable error reporting and logging
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Detailed logging function
function logError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(__DIR__ . '/logs/login_debug.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

logError("Login attempt started");

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
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logError("Database connection successful");
    
    // Get POST data
    $email = trim($_POST['email'] ?? $_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    logError("Login data - Email: $email, Role: $role, Password length: " . strlen($pass));
    
    // Basic validation
    if (empty($email) || empty($pass) || empty($role)) {
        logError("Validation failed - missing fields");
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logError("Email validation failed: $email");
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    logError("Validation passed, querying database");
    
    // Get user from database - handle missing status column gracefully
    try {
        // First try with status column
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? AND status = 'active'");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        logError("Query with status column successful");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Unknown column 'status'") !== false) {
            logError("Status column not found, querying without it");
            // Try without status column
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
            $stmt->execute([$email, $role]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            logError("Query without status column successful");
        } else {
            throw $e;
        }
    }
    
    if (!$user) {
        logError("User not found - Email: $email, Role: $role");
        
        // Check if user exists with different role
        $stmtCheck = $pdo->prepare("SELECT role FROM users WHERE email = ?");
        $stmtCheck->execute([$email]);
        $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($existingUser) {
            logError("User exists but with different role: " . $existingUser['role']);
            echo json_encode(['success' => false, 'message' => "User exists but not as $role. Found role: " . $existingUser['role']]);
        } else {
            logError("User does not exist at all");
            echo json_encode(['success' => false, 'message' => 'User not found. Please register first.']);
        }
        exit;
    }
    
    logError("User found - ID: " . $user['id'] . ", Name: " . $user['name']);
    
    // Verify password
    if (!password_verify($pass, $user['password'])) {
        logError("Password verification failed");
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit;
    }
    
    logError("Password verification successful");
    
    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    logError("Last login updated");
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['login_time'] = time();
    
    logError("Session variables set");
    
    $response = [
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ]
    ];
    
    logError("Login successful for user: " . $user['email']);
    echo json_encode($response);
    
} catch (PDOException $e) {
    logError("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
} catch (Exception $e) {
    logError("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
}
?>
<?php
// Simple registration endpoint with enhanced debugging
header('Content-Type: application/json');

// Enable error reporting
ini_set('display_errors', 0); // Don't show errors to user
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Create logs directory and log function
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

function logDebug($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] SIMPLE_REG: $message" . PHP_EOL;
    file_put_contents(__DIR__ . '/logs/registration_debug.log', $logMessage, FILE_APPEND | LOCK_EX);
}

logDebug("Registration request started");

// Database connection
$host = '127.0.0.1';
$port = '3307';
$dbname = 'queue_db';
$username = 'root';
$password = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug("Invalid method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get POST data and clean email
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $adminKey = $_POST['adminKey'] ?? '';
    
    logDebug("Registration data - Name: '$name', Email: '$email', Role: '$role', Password length: " . strlen($pass));
    
    // Clean email more thoroughly
    $email = preg_replace('/[^\x20-\x7E]/', '', $email);
    $email = str_replace(["\r", "\n", "\t"], '', $email);
    $email = trim($email);
    
    logDebug("Cleaned email: '$email'");
    
    // Basic validation
    if (empty($name) || empty($email) || empty($pass)) {
        logDebug("Validation failed - missing fields");
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    // More permissive email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        logDebug("Email validation failed: '$email'");
        echo json_encode(['success' => false, 'message' => 'Invalid email format: "' . $email . '"']);
        exit;
    }
    
    if (strlen($pass) < 6) {
        logDebug("Password too short: " . strlen($pass));
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }
    
    // Check admin key for admin registration
    if ($role === 'admin' && $adminKey !== 'ADMIN_2024_SECRET_KEY') {
        logDebug("Invalid admin key: '$adminKey'");
        echo json_encode(['success' => false, 'message' => 'Invalid admin key']);
        exit;
    }
    
    logDebug("Validation passed, connecting to database");
    
    // Connect to database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logDebug("Database connection successful");
    
    // Removed duplicate email check as requested - allow multiple accounts with same email
    
    // Hash password
    $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
    logDebug("Password hashed successfully");
    
    // Insert user
    // First try with status column, fallback without it if it doesn't exist
    try {
        logDebug("Attempting insert with status column");
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
        $result = $stmt->execute([$email, $hashedPassword, $name, $role]);
        logDebug("Insert with status column successful");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Unknown column 'status'") !== false) {
            logDebug("Status column not found, trying without it");
            $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$email, $hashedPassword, $name, $role]);
            logDebug("Insert without status column successful");
        } else {
            logDebug("Insert failed with error: " . $e->getMessage());
            throw $e;
        }
    }
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        logDebug("Registration successful - User ID: $userId");
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! You can now login.',
            'user_id' => $userId
        ]);
    } else {
        logDebug("Insert result was false - registration failed");
        echo json_encode(['success' => false, 'message' => 'Registration failed - database insert returned false']);
    }
    
} catch (PDOException $e) {
    logDebug("Database error: " . $e->getMessage());
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    logDebug("General error: " . $e->getMessage());
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
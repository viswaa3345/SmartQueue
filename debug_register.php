<?php
// Debug registration endpoint
header('Content-Type: application/json');

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function for debugging
function debugLog($message) {
    error_log("[DEBUG] " . $message);
    // Also write to a debug array we'll return
    global $debugInfo;
    $debugInfo[] = $message;
}

$debugInfo = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed', 'debug' => ['Method: ' . $_SERVER['REQUEST_METHOD']]]);
    exit;
}

debugLog("Registration attempt started");
debugLog("POST data: " . json_encode($_POST));

// Database connection
$host = '127.0.0.1';
$dbname = 'queue_db';
$username = 'root';
$password = '';

try {
    // Get POST data and clean it thoroughly
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $adminKey = $_POST['adminKey'] ?? '';
    
    // Remove any potential hidden characters from email
    $email = preg_replace('/[^\x20-\x7E]/', '', $email); // Remove non-printable ASCII
    $email = str_replace(["\r", "\n", "\t"], '', $email); // Remove whitespace chars
    $email = trim($email);
    
    debugLog("Raw email value: '" . ($_POST['email'] ?? 'NOT SET') . "'");
    debugLog("Cleaned email value: '" . $email . "'");
    debugLog("Email length: " . strlen($email));
    debugLog("Email bytes: " . bin2hex($email));
    debugLog("Parsed data - Name: '$name', Email: '$email', Role: '$role', Password length: " . strlen($pass));
    
    // Basic validation
    if (empty($name) || empty($email) || empty($pass)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required', 'debug' => $debugInfo]);
        exit;
    }
    
    // Multiple email validation methods
    $filterValid = filter_var($email, FILTER_VALIDATE_EMAIL);
    $regexValid = preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email);
    
    debugLog("filter_var validation: " . ($filterValid ? 'VALID (' . $filterValid . ')' : 'INVALID'));
    debugLog("regex validation: " . ($regexValid ? 'VALID' : 'INVALID'));
    
    if (!$filterValid && !$regexValid) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format - Email: "' . $email . '" (length: ' . strlen($email) . ')', 'debug' => $debugInfo]);
        exit;
    }
    
    // Use the more permissive validation if filter_var fails but regex passes
    if (!$filterValid && $regexValid) {
        debugLog("Using regex validation as filter_var failed but email looks valid");
    }
    
    if (strlen($pass) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters', 'debug' => $debugInfo]);
        exit;
    }
    
    // Check admin key for admin registration
    if ($role === 'admin' && $adminKey !== 'ADMIN_2024_SECRET_KEY') {
        echo json_encode(['success' => false, 'message' => 'Invalid admin key', 'debug' => $debugInfo]);
        exit;
    }
    
    debugLog("Validation passed, attempting database connection");
    
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    debugLog("Database connection successful");
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Users table does not exist. Please run setup first.");
    }
    
    debugLog("Users table exists");
    
    // Check table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    debugLog("Table columns: " . implode(', ', $columnNames));
    
    // Removed duplicate email check as requested - allow multiple accounts with same email
    
    debugLog("Skipping duplicate email check, proceeding with registration");
    
    // Hash password
    $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
    debugLog("Password hashed successfully");
    
    // Insert user - check which columns exist and use appropriate query
    if (in_array('status', $columnNames)) {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
        debugLog("Using query with status column");
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        debugLog("Using query without status column");
    }
    
    $result = $stmt->execute([$email, $hashedPassword, $name, $role]);
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        debugLog("User created successfully with ID: $userId");
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! You can now login.',
            'user_id' => $userId,
            'debug' => $debugInfo
        ]);
    } else {
        debugLog("Insert query returned false");
        echo json_encode(['success' => false, 'message' => 'Registration failed - insert returned false', 'debug' => $debugInfo]);
    }
    
} catch (PDOException $e) {
    $errorMsg = "Database error: " . $e->getMessage();
    debugLog($errorMsg);
    error_log($errorMsg);
    echo json_encode(['success' => false, 'message' => $errorMsg, 'debug' => $debugInfo]);
} catch (Exception $e) {
    $errorMsg = "General error: " . $e->getMessage();
    debugLog($errorMsg);
    error_log($errorMsg);
    echo json_encode(['success' => false, 'message' => $errorMsg, 'debug' => $debugInfo]);
}
?>
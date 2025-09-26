<?php
session_start();
require_once 'headers.php';
require_once 'db.php';

// Enable error logging
ini_set('log_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Handle both JSON and form data
$input = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    // Handle form data (from your current registration forms)
    $input = $_POST;
}

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

// Extract data - support both old format (username) and new format (email)
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'customer';
$adminKey = $input['adminKey'] ?? '';
$phone = $input['phone'] ?? '';

// Use email if provided, otherwise use username as email
if (empty($email) && !empty($username)) {
    // If username looks like an email, use it as email
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $email = $username;
    } else {
        // Convert username to a basic email format for compatibility
        $email = $username . '@example.com';
    }
} elseif (empty($email) && empty($username)) {
    echo json_encode(['success' => false, 'error' => 'Email or username is required']);
    exit;
}

// Basic validation
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Name, email, and password are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long']);
    exit;
}

// Check admin key for admin registration
if ($role === 'admin' && $adminKey !== 'ADMIN_2024_SECRET_KEY') {
    echo json_encode(['success' => false, 'error' => 'Invalid admin key']);
    exit;
}

try {
    // Ensure users table has proper structure
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
        phone VARCHAR(20),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    )");

    // Check if phone column exists, if not add it
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
        if ($checkColumn->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER role");
        }
    } catch (PDOException $e) {
        // Phone column check failed, we'll insert without it
    }

    // Hash password and insert new user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Try with all columns first, then fallback step by step
    try {
        // Try with phone and status columns
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([$name, $email, $hashed_password, $role, $phone]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Unknown column 'phone'") !== false) {
            // Try without phone column but with status
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                $stmt->execute([$name, $email, $hashed_password, $role]);
            } catch (PDOException $e2) {
                if (strpos($e2->getMessage(), "Unknown column 'status'") !== false) {
                    // Try without both phone and status columns
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $email, $hashed_password, $role]);
                } else {
                    throw $e2;
                }
            }
        } elseif (strpos($e->getMessage(), "Unknown column 'status'") !== false) {
            // Try without status column but with phone
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $hashed_password, $role, $phone]);
            } catch (PDOException $e3) {
                if (strpos($e3->getMessage(), "Unknown column 'phone'") !== false) {
                    // Try without both phone and status columns
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $email, $hashed_password, $role]);
                } else {
                    throw $e3;
                }
            }
        } else {
            throw $e;
        }
    }

    $user_id = $pdo->lastInsertId();

    // Set session variables for auto-login
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = $role;
    $_SESSION['login_time'] = time();

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'user' => [
            'id' => $user_id,
            'email' => $email,
            'role' => $role,
            'name' => $name
        ],
        'redirect' => $role === 'admin' ? 'admin_dashboard.html' : 'user_dashboard.html'
    ]);

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()]);
}
?>
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
    $input = $_POST;
}

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

// Extract and validate data
$username = trim($input['username'] ?? '');
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';
$role = $input['role'] ?? 'customer';
$adminKey = $input['adminKey'] ?? '';

// Comprehensive validation
$errors = [];

// Username validation
if (empty($username)) {
    $errors[] = 'Username is required';
} elseif (strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters long';
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = 'Username can only contain letters, numbers, and underscores';
}

// Name validation
if (empty($name)) {
    $errors[] = 'Full name is required';
} elseif (strlen($name) < 2) {
    $errors[] = 'Name must be at least 2 characters long';
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
    $errors[] = 'Name can only contain letters and spaces';
}

// Email validation
if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

// Phone validation
if (empty($phone)) {
    $errors[] = 'Phone number is required';
} elseif (!preg_match('/^[\+]?[1-9][\d]{0,15}$/', preg_replace('/[\s\-\(\)]/', '', $phone))) {
    $errors[] = 'Invalid phone number format';
}

// Password validation
if (empty($password)) {
    $errors[] = 'Password is required';
} elseif (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long';
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password)) {
    $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character';
}

// Confirm password validation
if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match';
}

// Role validation
if (!in_array($role, ['customer', 'admin'])) {
    $errors[] = 'Invalid role selected';
}

// Admin key validation
if ($role === 'admin' && $adminKey !== 'ADMIN_2024_SECRET_KEY') {
    $errors[] = 'Invalid admin key for admin registration';
}

// Return validation errors
if (!empty($errors)) {
    echo json_encode([
        'success' => false, 
        'message' => implode('. ', $errors),
        'errors' => $errors
    ]);
    exit;
}

try {
    // Check for existing username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Username already exists. Please choose a different username.'
        ]);
        exit;
    }
    
    // Check for existing email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Email address already registered. Please use a different email or try logging in.'
        ]);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, name, email, phone, password, role, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([$username, $name, $email, $phone, $hashed_password, $role])) {
        $user_id = $pdo->lastInsertId();
        
        // Set session variables for auto-login
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        $_SESSION['login_time'] = time();
        
        // Log successful registration
        error_log("User registered successfully: ID=$user_id, Username=$username, Email=$email, Role=$role");
        
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully! Welcome to SmartQueue.',
            'user' => [
                'id' => $user_id,
                'username' => $username,
                'email' => $email,
                'name' => $name,
                'role' => $role
            ],
            'redirect' => $role === 'admin' ? 'admin_dashboard.html' : 'user_dashboard.html'
        ]);
        
    } else {
        throw new Exception('Failed to create user account');
    }
    
} catch (PDOException $e) {
    error_log("Registration database error: " . $e->getMessage());
    
    // Handle specific database errors
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        if (strpos($e->getMessage(), 'username') !== false) {
            $message = 'Username already exists. Please choose a different username.';
        } elseif (strpos($e->getMessage(), 'email') !== false) {
            $message = 'Email address already registered. Please use a different email.';
        } else {
            $message = 'Account with this information already exists.';
        }
    } else {
        $message = 'Registration failed due to a database error. Please try again later.';
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'error_code' => 'DATABASE_ERROR'
    ]);
    
} catch (Exception $e) {
    error_log("Registration general error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed. Please try again later.',
        'error_code' => 'GENERAL_ERROR'
    ]);
}
?>
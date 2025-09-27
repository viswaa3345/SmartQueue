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

// Extract data
$login = trim($input['login'] ?? $input['email'] ?? $input['username'] ?? '');
$password = $input['password'] ?? '';
$remember = isset($input['remember']) && $input['remember'];

// Validation
if (empty($login)) {
    echo json_encode(['success' => false, 'message' => 'Username or email is required']);
    exit;
}

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

try {
    // Try to find user by username or email
    $stmt = $pdo->prepare("
        SELECT id, username, name, email, phone, password, role, created_at 
        FROM users 
        WHERE username = ? OR email = ?
        LIMIT 1
    ");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Log failed login attempt
        error_log("Login failed - User not found: $login");
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username/email or password'
        ]);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Log failed login attempt
        error_log("Login failed - Wrong password for user: " . $user['username']);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username/email or password'
        ]);
        exit;
    }
    
    // Successful login - set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['login_time'] = time();
    
    // Set remember me cookie if requested
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store remember token in database (you might want to create a separate table for this)
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET remember_token = ?, remember_expires = FROM_UNIXTIME(?) 
                WHERE id = ?
            ");
            $stmt->execute([$token, $expires, $user['id']]);
            
            // Set cookie
            setcookie('remember_token', $token, $expires, '/', '', false, true);
        } catch (Exception $e) {
            // Remember me failed, but don't fail the login
            error_log("Remember me failed: " . $e->getMessage());
        }
    }
    
    // Update last login time
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
    } catch (Exception $e) {
        error_log("Failed to update last login: " . $e->getMessage());
    }
    
    // Log successful login
    error_log("User logged in successfully: ID=" . $user['id'] . ", Username=" . $user['username']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful! Welcome back, ' . $user['name'] . '.',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ],
        'redirect' => $user['role'] === 'admin' ? 'admin_dashboard.html' : 'user_dashboard.html'
    ]);
    
} catch (PDOException $e) {
    error_log("Login database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Login failed due to a system error. Please try again later.',
        'error_code' => 'DATABASE_ERROR'
    ]);
    
} catch (Exception $e) {
    error_log("Login general error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Login failed. Please try again later.',
        'error_code' => 'GENERAL_ERROR'
    ]);
}
?>
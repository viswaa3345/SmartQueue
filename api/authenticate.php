<?php
session_start();
require_once 'headers.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username']) || !isset($input['password'])) {
    echo json_encode(['success' => false, 'error' => 'Username and password required']);
    exit;
}

$username = trim($input['username']);
$password = $input['password'];
$role = isset($input['role']) ? $input['role'] : null;

try {
    // Prepare query based on role requirement
    if ($role) {
        $stmt = $pdo->prepare("SELECT id, username, password, role, name, email FROM users WHERE username = ? AND role = ? AND is_active = 1");
        $stmt->execute([$username, $role]);
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role, name, email FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
    }
    
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['logged_in'] = true;

        // Update last login (optional)
        $update_stmt = $pdo->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update_stmt->execute([$user['id']]);

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'name' => $user['name']
            ],
            'redirect' => $user['role'] === 'admin' ? 'admin_dashboard.html' : 'user_dashboard.html'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>
<?php
require_once 'headers.php';

// Handle POST requests for session restoration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'restore_session') {
        // Restore session from provided user data
        $user_data = $input['user_data'];
        
        if ($user_data && isset($user_data['id'])) {
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['user_role'] = $user_data['role'];
            $_SESSION['user_email'] = $user_data['email'];
            $_SESSION['user_name'] = $user_data['name'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Session restored',
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email'],
                    'role' => $_SESSION['user_role'],
                    'name' => $_SESSION['user_name']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid user data',
                'authenticated' => false
            ]);
        }
        exit;
    }
}

// Check if user is logged in (compatible with AuthService)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    echo json_encode([
        'authenticated' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

// Map database role to frontend role
$dbRole = $_SESSION['user_role'] ?? 'user';
$displayRole = ($dbRole === 'user') ? 'customer' : $dbRole;

echo json_encode([
    'authenticated' => true,
    'user' => [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['user_name'] ?? $_SESSION['user_email'],
        'email' => $_SESSION['user_email'],
        'role' => $displayRole, // Send 'customer' for frontend compatibility
        'name' => $_SESSION['user_name'] ?? $_SESSION['user_email']
    ]
]);
?>

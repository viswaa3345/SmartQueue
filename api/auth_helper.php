<?php
// auth_helper.php is included after headers.php which already starts session

function requireAuth($required_role = null) {
    // Check if user is authenticated using our current session system
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        http_response_code(401);
        throw new Exception('Authentication required');
    }
    
    // Check role if specified
    if ($required_role && $_SESSION['user_role'] !== $required_role) {
        http_response_code(403);
        throw new Exception('Access denied. Required role: ' . $required_role . ', Current role: ' . $_SESSION['user_role']);
    }
    
    return true;
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['user_name'] ?? $_SESSION['user_email'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'name' => $_SESSION['user_name'] ?? $_SESSION['user_email']
    ];
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isCustomer() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
}
?>
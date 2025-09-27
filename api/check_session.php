<?php
session_start();
require_once 'headers.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode([
        'authenticated' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'user' => [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'name' => $_SESSION['name']
    ]
]);
?>

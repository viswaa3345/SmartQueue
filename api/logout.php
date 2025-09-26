<?php
session_start();
require_once 'headers.php';

// Clear all session variables
session_unset();
session_destroy();

// Start a new session and confirm logout
session_start();

echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
?>
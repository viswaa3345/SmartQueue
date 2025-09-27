<?php
// System Configuration
define('SITE_NAME', 'Smart Queue Restaurant');
define('SITE_URL', 'http://localhost/queue_app');
define('ADMIN_KEY', 'ADMIN_2024_SECRET_KEY'); // Required for admin registration

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes

// Queue settings
define('MAX_QUEUE_SIZE', 50);
define('DEFAULT_PREP_TIME', 15);
define('PICKUP_RADIUS', 100); // meters
define('NOTIFICATION_ADVANCE_TIME', 5); // minutes

// File upload settings
define('MAX_FILE_SIZE', 2097152); // 2MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Email settings (if needed)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 for development
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Set timezone
date_default_timezone_set('America/New_York');

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.use_strict_mode', 1);
    session_start();
}
?>
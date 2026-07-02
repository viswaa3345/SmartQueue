<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307'); // XAMPP MySQL port
define('DB_NAME', 'queue_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Create PDO connection with a fallback to the default MySQL port.
function getConnection() {
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $ports = [DB_PORT, '3306'];
        $lastError = null;

        foreach (array_unique($ports) as $port) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                return new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                $lastError = $e;
            }
        }

        throw $lastError ?: new PDOException('Database connection failed');
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

// Global PDO instance
$pdo = getConnection();
?>
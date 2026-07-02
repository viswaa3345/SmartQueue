<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Database.php';

class AuthService {
    private $db;
    
    public function __construct() {
        try {
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Ensure config files are loaded
            if (!defined('ADMIN_KEY')) {
                require_once __DIR__ . '/../config/config.php';
            }
            if (!defined('DB_HOST')) {
                require_once __DIR__ . '/../config/database.php';
            }
            
            $this->db = Database::getInstance();
            
        } catch (Exception $e) {
            error_log("AuthService constructor error: " . $e->getMessage());
            throw new Exception("Authentication service initialization failed: " . $e->getMessage());
        }
    }
    
    public function register($email, $password, $name, $role, $adminKey = null) {
        // Validate input
        if (empty($email) || empty($password) || empty($name) || empty($role)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        if (!in_array($role, ['customer', 'admin'])) {
            return ['success' => false, 'message' => 'Invalid role'];
        }
        
        // Check admin key for admin registration
        if ($role === 'admin' && $adminKey !== ADMIN_KEY) {
            return ['success' => false, 'message' => 'Invalid admin key'];
        }
        
        try {
            // Check if user already exists
            $existing = $this->db->fetch(
                "SELECT id FROM users WHERE email = ? OR username = ?", 
                [$email, $email]
            );
            
            if ($existing) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $userData = [
                'username' => $email,
                'email' => $email,
                'password' => $hashedPassword,
                'name' => $name,
                'role' => $role,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $userId = $this->db->insert('users', $userData);
            
            return [
                'success' => true, 
                'message' => 'Registration successful',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    public function login($email, $password, $role) {
        // Validate input
        if (empty($email) || empty($password) || empty($role)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (!in_array($role, ['customer', 'admin'])) {
            return ['success' => false, 'message' => 'Invalid role'];
        }
        
        try {
            $columns = array_map(
                static fn ($column) => $column['Field'],
                $this->db->fetchAll('SHOW COLUMNS FROM users')
            );

            $hasUsername = in_array('username', $columns, true);
            $hasIsActive = in_array('is_active', $columns, true);
            $hasStatus = in_array('status', $columns, true);

            $roleCandidates = [$role];
            if ($role === 'customer') {
                $roleCandidates[] = 'user';
            }

            $user = null;

            foreach ($roleCandidates as $roleCandidate) {
                $whereParts = ['email = ?'];
                $params = [$email];

                if ($hasUsername) {
                    $whereParts[0] = '(email = ? OR username = ?)';
                    $params[] = $email;
                }

                $whereParts[] = 'role = ?';
                $params[] = $roleCandidate;

                if ($hasIsActive) {
                    $whereParts[] = 'is_active = 1';
                } elseif ($hasStatus) {
                    $whereParts[] = "status = 'active'";
                }

                $user = $this->db->fetch(
                    'SELECT * FROM users WHERE ' . implode(' AND ', $whereParts),
                    $params
                );

                if ($user) {
                    break;
                }
            }
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Update last login - handle missing column gracefully
            try {
                if (in_array('last_login', $columns, true)) {
                    $this->db->update(
                        'users', 
                        ['last_login' => date('Y-m-d H:i:s')],
                        'id = ?',
                        [$user['id']]
                    );
                } elseif (in_array('updated_at', $columns, true)) {
                    $this->db->update(
                        'users', 
                        ['updated_at' => date('Y-m-d H:i:s')],
                        'id = ?',
                        [$user['id']]
                    );
                }
            } catch (Exception $e) {
                // Ignore if last_login column doesn't exist
                if (strpos($e->getMessage(), "Unknown column 'last_login'") === false) {
                    throw $e;
                }
            }
            
            // Set session
            $this->setUserSession($user);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            error_log("Login Error Stack: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }
    
    public function requireAuth($requiredRole = null) {
        if (!$this->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit;
        }
        
        if ($requiredRole && $_SESSION['user_role'] !== $requiredRole) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        return true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            return $this->db->fetch(
                "SELECT id, email, name, role, created_at FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            );
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Generate CSRF token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    public function validateCSRF($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function getCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
?>
<?php
// Test authentication system
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem; }
        .test-card { background: white; border-radius: 15px; padding: 2rem; margin: 1rem 0; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-white text-center mb-4">Authentication System Test</h1>
        
        <div class="test-card">
            <h3>Step 1: Database Connection Test</h3>
            <?php
            try {
                // Test the updated configuration
                require_once 'config/database.php';
                $conn = getConnection();
                echo "<p class='success'>✓ Database connection successful using config/database.php</p>";
                
                // Test if we can query the users table
                $stmt = $conn->query("SELECT COUNT(*) as user_count FROM users");
                $result = $stmt->fetch();
                echo "<p class='success'>✓ Users table accessible. Total users: " . $result['user_count'] . "</p>";
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="test-card">
            <h3>Step 2: AuthService Test</h3>
            <?php
            try {
                require_once 'includes/AuthService.php';
                $auth = new AuthService();
                echo "<p class='success'>✓ AuthService initialized successfully</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ AuthService failed: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="test-card">
            <h3>Step 3: Test Login Form</h3>
            <form method="POST" id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="test@example.com">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" value="password123">
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-control" id="role" name="role">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="test_login" class="btn btn-primary">Test Login</button>
            </form>
            
            <?php
            if (isset($_POST['test_login'])) {
                echo "<hr><h4>Login Test Results:</h4>";
                try {
                    $email = $_POST['email'];
                    $password = $_POST['password'];
                    $role = $_POST['role'];
                    
                    if (isset($auth)) {
                        $result = $auth->login($email, $password, $role);
                        if ($result['success']) {
                            echo "<p class='success'>✓ Login successful!</p>";
                            echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
                        } else {
                            echo "<p class='error'>❌ Login failed: " . $result['message'] . "</p>";
                        }
                    } else {
                        echo "<p class='error'>❌ AuthService not available</p>";
                    }
                } catch (Exception $e) {
                    echo "<p class='error'>❌ Login test error: " . $e->getMessage() . "</p>";
                }
            }
            ?>
        </div>
        
        <div class="test-card">
            <h3>Available Test Users</h3>
            <?php
            if (isset($conn)) {
                try {
                    $stmt = $conn->query("SELECT email, role, status FROM users LIMIT 5");
                    $users = $stmt->fetchAll();
                    
                    if ($users) {
                        echo "<table class='table table-striped'>";
                        echo "<tr><th>Email</th><th>Role</th><th>Status</th></tr>";
                        foreach ($users as $user) {
                            echo "<tr><td>{$user['email']}</td><td>{$user['role']}</td><td>{$user['status']}</td></tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p>No users found in database</p>";
                    }
                } catch (Exception $e) {
                    echo "<p class='error'>❌ Could not fetch users: " . $e->getMessage() . "</p>";
                }
            }
            ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
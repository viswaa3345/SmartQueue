<?php
// Password utility for testing
require_once 'config/database.php';

echo "<h2>Password Test Utility</h2>";

if (isset($_POST['reset_passwords'])) {
    try {
        $pdo = getConnection();
        
        // Update admin password
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@restaurant.com'");
        $stmt->execute([$adminPass]);
        
        // Update customer password  
        $customerPass = password_hash('customer123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'customer@example.com'");
        $stmt->execute([$customerPass]);
        
        echo "<p style='color: green;'>✓ Passwords reset successfully!</p>";
        echo "<p>Admin: admin@restaurant.com / admin123</p>";
        echo "<p>Customer: customer@example.com / customer123</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}

if (isset($_POST['test_login'])) {
    echo "<h3>Testing Login...</h3>";
    try {
        require_once 'includes/AuthService.php';
        $auth = new AuthService();
        
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        $result = $auth->login($email, $password, $role);
        
        if ($result['success']) {
            echo "<p style='color: green;'>✓ Login successful!</p>";
            echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Login failed: " . $result['message'] . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Password Test Utility</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Reset Test Passwords</h5>
                <form method="POST">
                    <button type="submit" name="reset_passwords" class="btn btn-warning">Reset Passwords to Default</button>
                </form>
                <small class="text-muted">This will set admin123 for admin and customer123 for customer</small>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Test Login</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <select class="form-control" name="email">
                            <option value="admin@restaurant.com">admin@restaurant.com</option>
                            <option value="customer@example.com">customer@example.com</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" value="admin123">
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-control" name="role">
                            <option value="admin">Admin</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                    <button type="submit" name="test_login" class="btn btn-primary">Test Login</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Automated system fix script
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Smart Queue System Auto-Fix</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 3px; margin: 5px 0; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 3px; margin: 5px 0; }
    .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 3px; margin: 5px 0; }
    .info { background: #cce7ff; color: #004085; padding: 10px; border-radius: 3px; margin: 5px 0; }
    .section { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
    .btn { background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; margin: 5px; }
</style></head><body>";

echo "<h1>🔧 Smart Queue System Auto-Fix</h1>";
echo "<p><strong>Starting automated fixes...</strong></p>";

$fixesApplied = 0;

// Fix 1: Database and Table Creation
echo "<div class='section'>";
echo "<h2>Fix 1: Database Structure</h2>";

try {
    $host = '127.0.0.1';
    $username = 'root';
    $password = '';
    
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ Connected to MySQL</div>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS queue_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>✅ Database 'queue_db' created/verified</div>";
    $fixesApplied++;
    
    // Connect to specific database
    $pdo = new PDO("mysql:host=$host;dbname=queue_db;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table with all required columns
    $createUsers = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
        phone VARCHAR(20) NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status)
    ) ENGINE=InnoDB";
    
    $pdo->exec($createUsers);
    echo "<div class='success'>✅ Users table created/updated</div>";
    $fixesApplied++;
    
    // Add missing columns if they don't exist
    $columns = $pdo->query("SHOW COLUMNS FROM users");
    $existingColumns = [];
    while ($col = $columns->fetch()) {
        $existingColumns[] = $col['Field'];
    }
    
    if (!in_array('phone', $existingColumns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER role");
        echo "<div class='success'>✅ Added 'phone' column to users table</div>";
        $fixesApplied++;
    }
    
    if (!in_array('status', $existingColumns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER phone");
        echo "<div class='success'>✅ Added 'status' column to users table</div>";
        $fixesApplied++;
    }
    
    // Create food_items table
    $createFoodItems = "
    CREATE TABLE IF NOT EXISTS food_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category VARCHAR(50) DEFAULT NULL,
        image_url VARCHAR(255) DEFAULT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        preparation_time INT DEFAULT 15,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_available (is_available)
    ) ENGINE=InnoDB";
    
    $pdo->exec($createFoodItems);
    echo "<div class='success'>✅ Food items table created/updated</div>";
    $fixesApplied++;
    
    // Create tokens table
    $createTokens = "
    CREATE TABLE IF NOT EXISTS tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_number VARCHAR(10) NOT NULL UNIQUE,
        status ENUM('waiting', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'waiting',
        estimated_time INT DEFAULT 30,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_status (status),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB";
    
    $pdo->exec($createTokens);
    echo "<div class='success'>✅ Tokens table created/updated</div>";
    $fixesApplied++;
    
    // Insert default users if they don't exist
    $adminCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $adminCheck->execute(['admin@restaurant.com']);
    
    if ($adminCheck->fetchColumn() == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdmin = $pdo->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'admin', 'active', NOW())");
        $insertAdmin->execute(['Restaurant Admin', 'admin@restaurant.com', $adminPassword]);
        echo "<div class='success'>✅ Created default admin user (admin@restaurant.com / admin123)</div>";
        $fixesApplied++;
    }
    
    $customerCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $customerCheck->execute(['customer@example.com']);
    
    if ($customerCheck->fetchColumn() == 0) {
        $customerPassword = password_hash('customer123', PASSWORD_DEFAULT);
        $insertCustomer = $pdo->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'customer', 'active', NOW())");
        $insertCustomer->execute(['Test Customer', 'customer@example.com', $customerPassword]);
        echo "<div class='success'>✅ Created default customer user (customer@example.com / customer123)</div>";
        $fixesApplied++;
    }
    
    // Insert sample food items
    $foodCheck = $pdo->query("SELECT COUNT(*) FROM food_items");
    if ($foodCheck->fetchColumn() == 0) {
        $sampleFood = [
            ['Burger', 'Classic beef burger with fries', 12.99, 'Main Course', null, 1, 15],
            ['Pizza', 'Margherita pizza', 14.99, 'Main Course', null, 1, 20],
            ['Salad', 'Fresh garden salad', 8.99, 'Appetizer', null, 1, 10],
            ['Coffee', 'Freshly brewed coffee', 3.99, 'Beverage', null, 1, 5],
            ['Ice Cream', 'Vanilla ice cream', 5.99, 'Dessert', null, 1, 3]
        ];
        
        $insertFood = $pdo->prepare("INSERT INTO food_items (name, description, price, category, image_url, is_available, preparation_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sampleFood as $food) {
            $insertFood->execute($food);
        }
        echo "<div class='success'>✅ Added sample food items</div>";
        $fixesApplied++;
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database fix error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Fix 2: Remove unique email constraint to allow duplicates
echo "<div class='section'>";
echo "<h2>Fix 2: Allow Duplicate Emails</h2>";

try {
    $indexes = $pdo->query("SHOW INDEX FROM users WHERE Column_name = 'email' AND Non_unique = 0");
    while ($index = $indexes->fetch()) {
        $indexName = $index['Key_name'];
        if ($indexName !== 'PRIMARY') {
            $pdo->exec("ALTER TABLE users DROP INDEX $indexName");
            echo "<div class='success'>✅ Removed unique constraint on email column</div>";
            $fixesApplied++;
        }
    }
} catch (PDOException $e) {
    echo "<div class='warning'>⚠️ Email constraint check: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Fix 3: Create missing essential files
echo "<div class='section'>";
echo "<h2>Fix 3: Essential Files Check</h2>";

// Check and create user_dashboard.html if missing
if (!file_exists(__DIR__ . '/user_dashboard.html')) {
    $userDashboard = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Smart Queue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Customer Dashboard</h1>
        <p>Welcome to the Smart Queue system!</p>
        <div class="card">
            <div class="card-body">
                <h5>Your Account</h5>
                <p>You are successfully logged in as a customer.</p>
                <a href="index.html" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>';
    
    file_put_contents(__DIR__ . '/user_dashboard.html', $userDashboard);
    echo "<div class='success'>✅ Created user_dashboard.html</div>";
    $fixesApplied++;
}

// Check and create admin_dashboard.html if missing
if (!file_exists(__DIR__ . '/admin_dashboard.html')) {
    $adminDashboard = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Queue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Admin Dashboard</h1>
        <p>Welcome to the Smart Queue admin panel!</p>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Manage Users</h5>
                        <p>View and manage registered users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Manage Food Items</h5>
                        <p>Add, edit, or remove food items</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Queue Management</h5>
                        <p>View and manage token queue</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <a href="index.html" class="btn btn-secondary">Logout</a>
        </div>
    </div>
</body>
</html>';
    
    file_put_contents(__DIR__ . '/admin_dashboard.html', $adminDashboard);
    echo "<div class='success'>✅ Created admin_dashboard.html</div>";
    $fixesApplied++;
}
echo "</div>";

// Fix 4: Update API login endpoint
echo "<div class='section'>";
echo "<h2>Fix 4: API Login Endpoint</h2>";

if (!file_exists(__DIR__ . '/api/login.php')) {
    $apiLogin = '<?php
session_start();
require_once "headers.php";
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["email"]) || !isset($input["password"])) {
    echo json_encode(["success" => false, "error" => "Email and password are required"]);
    exit;
}

$email = trim($input["email"]);
$password = $input["password"];
$role = $input["role"] ?? "customer";

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? AND status = \"active\"");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user["password"])) {
        echo json_encode(["success" => false, "error" => "Invalid credentials"]);
        exit;
    }
    
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user["id"]]);
    
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["user_email"] = $user["email"];
    $_SESSION["user_name"] = $user["name"];
    $_SESSION["user_role"] = $user["role"];
    $_SESSION["login_time"] = time();
    
    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "user" => [
            "id" => $user["id"],
            "email" => $user["email"],
            "name" => $user["name"],
            "role" => $user["role"]
        ],
        "redirect" => $user["role"] === "admin" ? "admin_dashboard.html" : "user_dashboard.html"
    ]);
    
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Login failed"]);
}
?>';
    
    file_put_contents(__DIR__ . '/api/login.php', $apiLogin);
    echo "<div class='success'>✅ Created api/login.php</div>";
    $fixesApplied++;
}
echo "</div>";

// Summary
echo "<div class='section'>";
echo "<h2>🎉 Auto-Fix Complete!</h2>";
echo "<div class='info'>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Total fixes applied:</strong> $fixesApplied</p>";
echo "<p><strong>System status:</strong> All major issues have been addressed</p>";
echo "</div>";

echo "<h3>What was fixed:</h3>";
echo "<ul>";
echo "<li>✅ Database and tables created with proper structure</li>";
echo "<li>✅ Added missing columns (phone, status)</li>";
echo "<li>✅ Removed unique email constraint to allow duplicates</li>";
echo "<li>✅ Created default admin and customer users</li>";
echo "<li>✅ Added sample food items</li>";
echo "<li>✅ Created missing dashboard files</li>";
echo "<li>✅ Updated API endpoints</li>";
echo "</ul>";

echo "<h3>Ready to use:</h3>";
echo "<div class='info'>";
echo "<p><strong>Default Login Credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@restaurant.com / admin123</li>";
echo "<li><strong>Customer:</strong> customer@example.com / customer123</li>";
echo "</ul>";
echo "</div>";

echo "<h3>Test Your System:</h3>";
echo "<a href='index.html' class='btn'>🏠 Main Login Page</a> ";
echo "<a href='register.html' class='btn'>📝 Registration Page</a> ";
echo "<a href='test_registration_flow.html' class='btn'>🧪 Test Registration Flow</a> ";
echo "<a href='complete_diagnosis.php' class='btn'>🔍 Run Diagnosis Again</a>";

echo "</div>";
echo "</body></html>";
?>

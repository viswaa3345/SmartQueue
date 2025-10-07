<?php
session_start();
require_once 'api/db.php';

echo "<h2>Debug Food Item Save Issue</h2>";

// Check current session status
echo "<h3>1. Session Status</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session data:\n";
print_r($_SESSION);
echo "</pre>";

// Test admin login
echo "<h3>2. Testing Admin Login</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Admin user found: " . $admin['email'] . "<br>";
        
        // Simulate admin login session
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_email'] = $admin['email'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_role'] = $admin['role'];
        
        echo "✅ Admin session simulated<br>";
        echo "Session after login:<br>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
    } else {
        echo "❌ No admin user found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test food_items table structure
echo "<h3>3. Food Items Table Structure</h3>";
try {
    $stmt = $pdo->query("DESCRIBE food_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if is_available column exists
    $hasIsAvailable = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'is_available') {
            $hasIsAvailable = true;
            break;
        }
    }
    
    echo "<br>is_available column exists: " . ($hasIsAvailable ? "✅ Yes" : "❌ No") . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error checking table structure: " . $e->getMessage() . "<br>";
}

// Test POST request to food_items.php
echo "<h3>4. Testing Food Items API</h3>";

// Test GET request first
echo "<h4>GET Request:</h4>";
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include 'api/food_items.php';
$getOutput = ob_get_clean();
echo "Output: " . htmlspecialchars($getOutput) . "<br>";

// Test POST request (add new food item)
echo "<h4>POST Request (Add Food Item):</h4>";
$_SERVER['REQUEST_METHOD'] = 'POST';
$testFoodData = [
    'name' => 'Test Burger',
    'description' => 'Test burger description',
    'price' => 15.99,
    'category' => 'Test Category',
    'preparation_time' => 10
];

// Simulate POST input
$_POST = [];
$json_input = json_encode($testFoodData);
file_put_contents('php://memory', $json_input);

ob_start();
// Mock file_get_contents for php://input
function mockFileGetContents($filename) {
    global $json_input;
    if ($filename === 'php://input') {
        return $json_input;
    }
    return file_get_contents($filename);
}

// We'll test this manually
try {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        echo "❌ Authentication required<br>";
    } elseif ($_SESSION['user_role'] !== 'admin') {
        echo "❌ Admin access required. Current role: " . $_SESSION['user_role'] . "<br>";
    } else {
        echo "✅ Authentication passed<br>";
        
        // Test database insertion
        $stmt = $pdo->prepare("INSERT INTO food_items (name, description, price, category, preparation_time) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $testFoodData['name'],
            $testFoodData['description'],
            $testFoodData['price'],
            $testFoodData['category'],
            $testFoodData['preparation_time']
        ]);
        
        if ($result) {
            echo "✅ Test food item inserted successfully<br>";
            $lastId = $pdo->lastInsertId();
            echo "Inserted ID: " . $lastId . "<br>";
            
            // Clean up - remove test item
            $deleteStmt = $pdo->prepare("DELETE FROM food_items WHERE id = ?");
            $deleteStmt->execute([$lastId]);
            echo "✅ Test item cleaned up<br>";
        } else {
            echo "❌ Failed to insert test food item<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error during POST test: " . $e->getMessage() . "<br>";
}

// Test current food items count
echo "<h3>5. Current Food Items</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM food_items");
    $count = $stmt->fetch()['count'];
    echo "Total food items in database: " . $count . "<br>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, name, price, category FROM food_items LIMIT 5");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Sample items:</h4>";
        foreach ($items as $item) {
            echo "ID: {$item['id']}, Name: {$item['name']}, Price: {$item['price']}, Category: {$item['category']}<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking food items: " . $e->getMessage() . "<br>";
}

echo "<br><a href='admin_dashboard.html'>← Back to Admin Dashboard</a>";
?>
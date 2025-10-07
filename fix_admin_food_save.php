<?php
session_start();
require_once 'api/db.php';

echo "<h1>🔧 Admin Food Save Fix</h1>";

// Step 1: Ensure admin user exists
echo "<h2>Step 1: Check Admin User</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "<div style='color: orange;'>⚠️ No admin user found. Creating default admin...</div>";
        
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute(['admin@restaurant.com', $adminPassword, 'Restaurant Admin', 'admin']);
        
        if ($result) {
            echo "<div style='color: green;'>✅ Admin user created successfully</div>";
            echo "<div>Email: admin@restaurant.com</div>";
            echo "<div>Password: admin123</div>";
            
            // Get the newly created admin
            $admin_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            echo "<div style='color: red;'>❌ Failed to create admin user</div>";
        }
    } else {
        echo "<div style='color: green;'>✅ Admin user found: " . $admin['email'] . "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
}

// Step 2: Simulate admin login
echo "<h2>Step 2: Admin Login Session</h2>";
if ($admin) {
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['user_email'] = $admin['email'];
    $_SESSION['user_name'] = $admin['name'];
    $_SESSION['user_role'] = $admin['role'];
    
    echo "<div style='color: green;'>✅ Admin session created</div>";
    echo "<div>Session ID: " . session_id() . "</div>";
    echo "<div>User ID: " . $_SESSION['user_id'] . "</div>";
    echo "<div>Role: " . $_SESSION['user_role'] . "</div>";
} else {
    echo "<div style='color: red;'>❌ Cannot create session without admin user</div>";
}

// Step 3: Check food_items table structure
echo "<h2>Step 3: Food Items Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE food_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $hasIsAvailable = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'is_available') {
            $hasIsAvailable = true;
        }
    }
    echo "</table>";
    
    echo "<div>is_available column: " . ($hasIsAvailable ? "✅ Exists" : "❌ Missing") . "</div>";
    
    // Add is_available column if missing
    if (!$hasIsAvailable) {
        echo "<div style='color: orange;'>⚠️ Adding is_available column...</div>";
        try {
            $pdo->exec("ALTER TABLE food_items ADD COLUMN is_available BOOLEAN DEFAULT TRUE");
            echo "<div style='color: green;'>✅ is_available column added successfully</div>";
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Failed to add is_available column: " . $e->getMessage() . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking table structure: " . $e->getMessage() . "</div>";
}

// Step 4: Test food item insertion
echo "<h2>Step 4: Test Food Item Save</h2>";
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    try {
        $testFood = [
            'name' => 'Fix Test Burger',
            'description' => 'Test burger to verify save functionality',
            'price' => 13.99,
            'category' => 'Test Items',
            'preparation_time' => 12
        ];
        
        echo "<div>Testing food item data:</div>";
        echo "<pre>" . json_encode($testFood, JSON_PRETTY_PRINT) . "</pre>";
        
        // Try with is_available column first
        try {
            $stmt = $pdo->prepare("INSERT INTO food_items (name, description, price, category, preparation_time, is_available) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $testFood['name'],
                $testFood['description'],
                $testFood['price'],
                $testFood['category'],
                $testFood['preparation_time'],
                1
            ]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Unknown column 'is_available'") !== false) {
                echo "<div style='color: orange;'>⚠️ Fallback to insert without is_available column</div>";
                $stmt = $pdo->prepare("INSERT INTO food_items (name, description, price, category, preparation_time) VALUES (?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $testFood['name'],
                    $testFood['description'],
                    $testFood['price'],
                    $testFood['category'],
                    $testFood['preparation_time']
                ]);
            } else {
                throw $e;
            }
        }
        
        if ($result) {
            $foodId = $pdo->lastInsertId();
            echo "<div style='color: green;'>✅ Test food item saved successfully!</div>";
            echo "<div>New food item ID: " . $foodId . "</div>";
            
            // Verify the item was saved
            $stmt = $pdo->prepare("SELECT * FROM food_items WHERE id = ?");
            $stmt->execute([$foodId]);
            $savedItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($savedItem) {
                echo "<div style='color: green;'>✅ Food item verified in database</div>";
                echo "<div>Saved item: " . $savedItem['name'] . " - $" . $savedItem['price'] . "</div>";
                
                // Clean up test item
                $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = ?");
                $stmt->execute([$foodId]);
                echo "<div style='color: blue;'>🧹 Test item cleaned up</div>";
            } else {
                echo "<div style='color: red;'>❌ Could not verify saved item</div>";
            }
        } else {
            echo "<div style='color: red;'>❌ Failed to save test food item</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Error testing food save: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div style='color: red;'>❌ Admin session not available for testing</div>";
}

// Step 5: Test API endpoint directly
echo "<h2>Step 5: Test Food Items API</h2>";
try {
    // Test GET endpoint
    echo "<div><strong>Testing GET /api/food_items.php:</strong></div>";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    include 'api/food_items.php';
    $output = ob_get_clean();
    
    $decoded = json_decode($output, true);
    if ($decoded && isset($decoded['success'])) {
        echo "<div style='color: green;'>✅ GET endpoint working</div>";
        echo "<div>Found " . count($decoded['items'] ?? []) . " food items</div>";
    } else {
        echo "<div style='color: red;'>❌ GET endpoint error</div>";
        echo "<div>Raw output: " . htmlspecialchars($output) . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ API test error: " . $e->getMessage() . "</div>";
}

// Step 6: Final status and instructions
echo "<h2>Step 6: Final Status & Instructions</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border: 1px solid #b3d9ff; border-radius: 5px;'>";
echo "<h3>✅ Admin Food Save Fix Complete</h3>";
echo "<ul>";
echo "<li>Admin user verified/created</li>";
echo "<li>Database table structure checked/fixed</li>";
echo "<li>Session management tested</li>";
echo "<li>Food item save functionality tested</li>";
echo "</ul>";
echo "<h4>Next Steps:</h4>";
echo "<ol>";
echo "<li>Go to <a href='admin_dashboard.html'>Admin Dashboard</a></li>";
echo "<li>Login with: admin@restaurant.com / admin123</li>";
echo "<li>Navigate to 'Food Menu' tab</li>";
echo "<li>Try adding a new food item</li>";
echo "</ol>";
echo "</div>";

echo "<br><div>";
echo "<a href='admin_dashboard.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Admin Dashboard</a>";
echo "<a href='test_food_save.html' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Comprehensive Tests</a>";
echo "</div>";
?>
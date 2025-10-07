<?php
// Suppress PHP error display and log them instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any unwanted output
ob_start();

try {
    require_once 'headers.php';
    require_once 'db.php';
    require_once 'auth_helper.php';
    
    // Clear any unwanted output
    $buffer = ob_get_clean();
    if (!empty($buffer)) {
        error_log("Unwanted output in food_items.php: " . $buffer);
    }
    
    // Ensure we're outputting JSON
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Initialization error: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all food items
    try {
        // Check if food_items table has records, if not create sample data
        $checkStmt = $pdo->query("SELECT COUNT(*) FROM food_items");
        $count = $checkStmt->fetchColumn();
        
        if ($count == 0) {
            // Create sample food items with Indian Rupee prices
            $sampleItems = [
                ['Pizza Margherita', 'Fresh tomatoes, mozzarella, basil', 299, 'Main Course', 15],
                ['Chicken Burger', 'Grilled chicken, lettuce, tomato', 249, 'Main Course', 12],
                ['Caesar Salad', 'Romaine lettuce, parmesan, croutons', 199, 'Salads', 8],
                ['Pasta Carbonara', 'Creamy pasta with bacon and parmesan', 329, 'Main Course', 18],
                ['French Fries', 'Crispy golden fries', 99, 'Sides', 5],
                ['Chocolate Cake', 'Rich chocolate cake with frosting', 149, 'Desserts', 3]
            ];
            
            // Try to insert with is_available column, fallback without it
            try {
                $insertStmt = $pdo->prepare("INSERT INTO food_items (name, description, price, category, preparation_time, is_available) VALUES (?, ?, ?, ?, ?, 1)");
                foreach ($sampleItems as $item) {
                    $insertStmt->execute($item);
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "Unknown column 'is_available'") !== false) {
                    $insertStmt = $pdo->prepare("INSERT INTO food_items (name, description, price, category, preparation_time) VALUES (?, ?, ?, ?, ?)");
                    foreach ($sampleItems as $item) {
                        $insertStmt->execute($item);
                    }
                } else {
                    throw $e;
                }
            }
        }
        
        // Try with is_available column first, fallback without it
        try {
            $stmt = $pdo->query("SELECT * FROM food_items WHERE is_available = 1 ORDER BY category, name");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Unknown column 'is_available'") !== false) {
                $stmt = $pdo->query("SELECT * FROM food_items ORDER BY category, name");
            } else {
                throw $e;
            }
        }
        $food_items = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'items' => $food_items]);
    } catch (PDOException $e) {
        error_log("Food items error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new food item (admin only)
    requireAuth('admin');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['name']) || !isset($input['price'])) {
        echo json_encode(['success' => false, 'error' => 'Name and price are required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO food_items (name, description, price, category, preparation_time) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['name'],
            $input['description'] ?? '',
            $input['price'],
            $input['category'] ?? '',
            $input['preparation_time'] ?? 5
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Food item added successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update food item (admin only)
    requireAuth('admin');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        echo json_encode(['success' => false, 'error' => 'Item ID is required']);
        exit;
    }
    
    try {
        // Try to update with is_available column first, fallback without it
        try {
            $stmt = $pdo->prepare("UPDATE food_items SET name = ?, description = ?, price = ?, category = ?, preparation_time = ?, is_available = ? WHERE id = ?");
            $stmt->execute([
                $input['name'],
                $input['description'] ?? '',
                $input['price'],
                $input['category'] ?? '',
                $input['preparation_time'] ?? 5,
                $input['is_available'] ?? 1,
                $input['id']
            ]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Unknown column 'is_available'") !== false) {
                $stmt = $pdo->prepare("UPDATE food_items SET name = ?, description = ?, price = ?, category = ?, preparation_time = ? WHERE id = ?");
                $stmt->execute([
                    $input['name'],
                    $input['description'] ?? '',
                    $input['price'],
                    $input['category'] ?? '',
                    $input['preparation_time'] ?? 5,
                    $input['id']
                ]);
            } else {
                throw $e;
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Food item updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Delete food item (admin only)
    requireAuth('admin');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        echo json_encode(['success' => false, 'error' => 'Item ID is required']);
        exit;
    }
    
    try {
        // Try to soft delete with is_available column first, fallback to hard delete
        try {
            $stmt = $pdo->prepare("UPDATE food_items SET is_available = 0 WHERE id = ?");
            $stmt->execute([$input['id']]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Unknown column 'is_available'") !== false) {
                // If is_available column doesn't exist, do hard delete
                $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = ?");
                $stmt->execute([$input['id']]);
            } else {
                throw $e;
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Food item removed successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>

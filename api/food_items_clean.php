<?php
// Clean food_items.php API - prevents HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Capture any unwanted output
ob_start();

$response = null;

try {
    // Include required files
    require_once 'headers.php';
    require_once 'db.php';
    require_once 'auth_helper.php';
    
    // Clear any captured output
    ob_clean();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get all food items
        try {
            // Check if food_items table has records, if not create sample data
            $checkStmt = $pdo->query("SELECT COUNT(*) FROM food_items");
            $count = $checkStmt->fetchColumn();
            
            if ($count == 0) {
                // Create sample food items
                $sampleItems = [
                    ['Pizza Margherita', 'Fresh tomatoes, mozzarella, basil', 299, 'Main Course', 15],
                    ['Chicken Burger', 'Grilled chicken, lettuce, tomato', 249, 'Main Course', 12],
                    ['Caesar Salad', 'Romaine lettuce, parmesan, croutons', 199, 'Salads', 8],
                    ['Pasta Carbonara', 'Creamy pasta with bacon and parmesan', 329, 'Main Course', 18],
                    ['French Fries', 'Crispy golden fries', 99, 'Sides', 5],
                    ['Chocolate Cake', 'Rich chocolate cake with frosting', 149, 'Desserts', 3]
                ];
                
                $insertStmt = $pdo->prepare("INSERT INTO food_items (name, description, price, category, preparation_time) VALUES (?, ?, ?, ?, ?)");
                foreach ($sampleItems as $item) {
                    $insertStmt->execute($item);
                }
            }
            
            $stmt = $pdo->query("SELECT * FROM food_items ORDER BY category, name");
            $food_items = $stmt->fetchAll();
            
            $response = ['success' => true, 'items' => $food_items];
            
        } catch (PDOException $e) {
            error_log("Food items GET error: " . $e->getMessage());
            $response = ['success' => false, 'error' => 'Database error'];
        }
        
    } elseif ($method === 'POST') {
        // Add new food item (admin only)
        try {
            requireAuth('admin');
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['name']) || !isset($input['price'])) {
                $response = ['success' => false, 'error' => 'Name and price are required'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO food_items (name, description, price, category, preparation_time) VALUES (?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $input['name'],
                    $input['description'] ?? '',
                    $input['price'],
                    $input['category'] ?? '',
                    $input['preparation_time'] ?? 5
                ]);
                
                $response = ['success' => true, 'message' => 'Food item added successfully'];
            }
        } catch (Exception $e) {
            error_log("Food items POST error: " . $e->getMessage());
            if (strpos($e->getMessage(), 'Authentication required') !== false) {
                $response = ['success' => false, 'error' => 'Authentication required'];
            } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
                $response = ['success' => false, 'error' => 'Admin access required'];
            } else {
                $response = ['success' => false, 'error' => 'Database error'];
            }
        }
        
    } elseif ($method === 'PUT') {
        // Update food item (admin only)
        try {
            requireAuth('admin');
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                $response = ['success' => false, 'error' => 'Item ID is required'];
            } else {
                $stmt = $pdo->prepare("UPDATE food_items SET name = ?, description = ?, price = ?, category = ?, preparation_time = ? WHERE id = ?");
                $result = $stmt->execute([
                    $input['name'],
                    $input['description'] ?? '',
                    $input['price'],
                    $input['category'] ?? '',
                    $input['preparation_time'] ?? 5,
                    $input['id']
                ]);
                
                $response = ['success' => true, 'message' => 'Food item updated successfully'];
            }
        } catch (Exception $e) {
            error_log("Food items PUT error: " . $e->getMessage());
            if (strpos($e->getMessage(), 'Authentication required') !== false) {
                $response = ['success' => false, 'error' => 'Authentication required'];
            } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
                $response = ['success' => false, 'error' => 'Admin access required'];
            } else {
                $response = ['success' => false, 'error' => 'Database error'];
            }
        }
        
    } elseif ($method === 'DELETE') {
        // Delete food item (admin only)
        try {
            requireAuth('admin');
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                $response = ['success' => false, 'error' => 'Item ID is required'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = ?");
                $result = $stmt->execute([$input['id']]);
                
                $response = ['success' => true, 'message' => 'Food item removed successfully'];
            }
        } catch (Exception $e) {
            error_log("Food items DELETE error: " . $e->getMessage());
            if (strpos($e->getMessage(), 'Authentication required') !== false) {
                $response = ['success' => false, 'error' => 'Authentication required'];
            } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
                $response = ['success' => false, 'error' => 'Admin access required'];
            } else {
                $response = ['success' => false, 'error' => 'Database error'];
            }
        }
        
    } else {
        $response = ['success' => false, 'error' => 'Method not allowed'];
    }
    
} catch (Exception $e) {
    error_log("Food items API critical error: " . $e->getMessage());
    $response = ['success' => false, 'error' => 'Internal server error'];
}

// Clear any remaining output buffer
ob_clean();

// Send clean JSON response
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;
?>
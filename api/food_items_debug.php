<?php
// Debug version of food_items.php to identify JSON parsing issues
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any stray output
ob_start();

try {
    require_once 'headers.php';
    require_once 'db.php';
    require_once 'auth_helper.php';
    
    // Clear any output buffer that might have HTML
    $unwanted_output = ob_get_clean();
    if (!empty($unwanted_output)) {
        error_log("Unwanted output before API: " . $unwanted_output);
    }
    
    // Start fresh output buffer
    ob_start();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = null;
    
    // Get input data for POST, PUT, DELETE
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
        $raw_input = file_get_contents('php://input');
        $input = json_decode($raw_input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE && !empty($raw_input)) {
            throw new Exception('Invalid JSON input: ' . json_last_error_msg());
        }
    }
    
    if ($method === 'GET') {
        // Get all food items
        try {
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
            
            if (!$input || !isset($input['name']) || !isset($input['price'])) {
                throw new Exception('Name and price are required');
            }
            
            $stmt = $pdo->prepare("INSERT INTO food_items (name, description, price, category, preparation_time) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $input['name'],
                $input['description'] ?? '',
                $input['price'],
                $input['category'] ?? '',
                $input['preparation_time'] ?? 5
            ]);
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Food item added successfully'];
            } else {
                throw new Exception('Failed to insert food item');
            }
            
        } catch (Exception $e) {
            error_log("Food items POST error: " . $e->getMessage());
            $response = ['success' => false, 'error' => $e->getMessage()];
        }
        
    } elseif ($method === 'PUT') {
        // Update food item (admin only)
        try {
            requireAuth('admin');
            
            if (!$input || !isset($input['id'])) {
                throw new Exception('Item ID is required');
            }
            
            $stmt = $pdo->prepare("UPDATE food_items SET name = ?, description = ?, price = ?, category = ?, preparation_time = ? WHERE id = ?");
            $result = $stmt->execute([
                $input['name'],
                $input['description'] ?? '',
                $input['price'],
                $input['category'] ?? '',
                $input['preparation_time'] ?? 5,
                $input['id']
            ]);
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Food item updated successfully'];
            } else {
                throw new Exception('Failed to update food item');
            }
            
        } catch (Exception $e) {
            error_log("Food items PUT error: " . $e->getMessage());
            $response = ['success' => false, 'error' => $e->getMessage()];
        }
        
    } elseif ($method === 'DELETE') {
        // Delete food item (admin only)
        try {
            requireAuth('admin');
            
            if (!$input || !isset($input['id'])) {
                throw new Exception('Item ID is required');
            }
            
            $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = ?");
            $result = $stmt->execute([$input['id']]);
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Food item removed successfully'];
            } else {
                throw new Exception('Failed to delete food item');
            }
            
        } catch (Exception $e) {
            error_log("Food items DELETE error: " . $e->getMessage());
            $response = ['success' => false, 'error' => $e->getMessage()];
        }
        
    } else {
        $response = ['success' => false, 'error' => 'Method not allowed'];
    }
    
} catch (Exception $e) {
    error_log("Food items API critical error: " . $e->getMessage());
    $response = ['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()];
}

// Clear any remaining output buffer
$unwanted_output2 = ob_get_clean();
if (!empty($unwanted_output2)) {
    error_log("Unwanted output during API: " . $unwanted_output2);
}

// Ensure clean JSON output
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
<?php
require_once 'headers.php';
require_once 'db.php';
require_once 'auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all food items
    try {
        $stmt = $pdo->query("SELECT * FROM food_items WHERE is_available = 1 ORDER BY category, name");
        $food_items = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'items' => $food_items]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
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
        
        echo json_encode(['success' => true, 'message' => 'Food item updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
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
        $stmt = $pdo->prepare("UPDATE food_items SET is_available = 0 WHERE id = ?");
        $stmt->execute([$input['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Food item removed successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}
?>

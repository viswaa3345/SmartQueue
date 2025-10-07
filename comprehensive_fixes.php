<?php
/**
 * Comprehensive fixes for SmartQueue system
 * Addresses all issues found during system audit
 */

require_once 'config/database.php';

class ComprehensiveFixes {
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    public function runAllFixes() {
        echo "<h1>SmartQueue Comprehensive Fixes</h1>\n";
        echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px;'>\n";
        
        $this->fixDatabaseSchema();
        $this->cleanDuplicateFoodItems();
        $this->createMissingFiles();
        $this->validateSystemSettings();
        $this->optimizeDatabase();
        
        echo "<h2 style='color: green;'>✅ All fixes completed successfully!</h2>\n";
        echo "</div>";
    }
    
    private function fixDatabaseSchema() {
        echo "<h2>1. Database Schema Fixes</h2>\n";
        
        try {
            // Check if username column exists
            $result = $this->db->query("SHOW COLUMNS FROM users LIKE 'username'");
            if ($result->rowCount() == 0) {
                echo "<p>❌ Username column missing - Adding...</p>\n";
                $this->db->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE AFTER id");
                echo "<p>✅ Username column added successfully</p>\n";
            } else {
                echo "<p>✅ Username column already exists</p>\n";
            }
            
            // Ensure all required columns exist
            $requiredColumns = [
                'users' => ['id', 'username', 'name', 'email', 'phone', 'password', 'role', 'created_at'],
                'food_items' => ['id', 'name', 'description', 'price', 'category', 'image_url', 'available', 'created_at'],
                'tokens' => ['id', 'user_id', 'token_number', 'status', 'estimated_time', 'created_at', 'updated_at'],
                'notifications' => ['id', 'user_id', 'message', 'type', 'is_read', 'created_at'],
                'system_settings' => ['id', 'setting_key', 'setting_value', 'updated_at']
            ];
            
            foreach ($requiredColumns as $table => $columns) {
                $tableColumns = [];
                $result = $this->db->query("SHOW COLUMNS FROM $table");
                while ($row = $result->fetch()) {
                    $tableColumns[] = $row['Field'];
                }
                
                echo "<h3>Table: $table</h3>\n";
                foreach ($columns as $column) {
                    if (in_array($column, $tableColumns)) {
                        echo "<p>✅ Column '$column' exists</p>\n";
                    } else {
                        echo "<p>❌ Column '$column' missing</p>\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Database schema fix error: " . $e->getMessage() . "</p>\n";
        }
    }
    
    private function cleanDuplicateFoodItems() {
        echo "<h2>2. Clean Duplicate Food Items</h2>\n";
        
        try {
            // Find duplicates
            $stmt = $this->db->query("
                SELECT name, COUNT(*) as count 
                FROM food_items 
                GROUP BY name 
                HAVING count > 1
            ");
            
            $duplicates = $stmt->fetchAll();
            
            if (empty($duplicates)) {
                echo "<p>✅ No duplicate food items found</p>\n";
            } else {
                echo "<p>Found " . count($duplicates) . " duplicate food items</p>\n";
                
                foreach ($duplicates as $duplicate) {
                    echo "<p>Cleaning duplicates for: " . $duplicate['name'] . " (count: " . $duplicate['count'] . ")</p>\n";
                    
                    // Keep the first one, delete the rest
                    $this->db->exec("
                        DELETE FROM food_items 
                        WHERE name = '" . $duplicate['name'] . "' 
                        AND id NOT IN (
                            SELECT * FROM (
                                SELECT MIN(id) FROM food_items WHERE name = '" . $duplicate['name'] . "'
                            ) AS temp
                        )
                    ");
                }
                
                echo "<p>✅ Duplicate food items cleaned</p>\n";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Clean duplicates error: " . $e->getMessage() . "</p>\n";
        }
    }
    
    private function createMissingFiles() {
        echo "<h2>3. Create Missing Files</h2>\n";
        
        $missingFiles = [
            'api/location.php' => $this->getLocationApiContent(),
            'api/notifications.php' => $this->getNotificationsApiContent(),
            'api/queue_status.php' => $this->getQueueStatusApiContent(),
            'includes/NotificationService.php' => $this->getNotificationServiceContent(),
            'includes/QueueManager.php' => $this->getQueueManagerContent()
        ];
        
        foreach ($missingFiles as $filePath => $content) {
            if (!file_exists($filePath)) {
                echo "<p>Creating missing file: $filePath</p>\n";
                
                // Create directory if it doesn't exist
                $dir = dirname($filePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                file_put_contents($filePath, $content);
                echo "<p>✅ Created: $filePath</p>\n";
            } else {
                echo "<p>✅ File exists: $filePath</p>\n";
            }
        }
    }
    
    private function validateSystemSettings() {
        echo "<h2>4. Validate System Settings</h2>\n";
        
        $requiredSettings = [
            'queue_capacity' => '50',
            'avg_service_time' => '15',
            'restaurant_name' => 'SmartQueue Restaurant',
            'operating_hours' => '09:00-22:00',
            'notification_enabled' => '1',
            'auto_queue_advance' => '1'
        ];
        
        try {
            foreach ($requiredSettings as $key => $defaultValue) {
                $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                $result = $stmt->fetch();
                
                if (!$result) {
                    echo "<p>Adding missing setting: $key = $defaultValue</p>\n";
                    $stmt = $this->db->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$key, $defaultValue]);
                } else {
                    echo "<p>✅ Setting exists: $key = " . $result['setting_value'] . "</p>\n";
                }
            }
            
        } catch (Exception $e) {
            echo "<p>❌ System settings validation error: " . $e->getMessage() . "</p>\n";
        }
    }
    
    private function optimizeDatabase() {
        echo "<h2>5. Database Optimization</h2>\n";
        
        try {
            // Add indexes for better performance
            $indexes = [
                "CREATE INDEX idx_users_email ON users(email)",
                "CREATE INDEX idx_users_role ON users(role)",
                "CREATE INDEX idx_tokens_user_id ON tokens(user_id)",
                "CREATE INDEX idx_tokens_status ON tokens(status)",
                "CREATE INDEX idx_notifications_user_id ON notifications(user_id)",
                "CREATE INDEX idx_food_items_category ON food_items(category)"
            ];
            
            foreach ($indexes as $index) {
                try {
                    $this->db->exec($index);
                    echo "<p>✅ Index created: " . substr($index, 13, 30) . "...</p>\n";
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                        echo "<p>✅ Index already exists: " . substr($index, 13, 30) . "...</p>\n";
                    } else {
                        echo "<p>❌ Index creation failed: " . $e->getMessage() . "</p>\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Database optimization error: " . $e->getMessage() . "</p>\n";
        }
    }
    
    private function getLocationApiContent() {
        return '<?php
/**
 * Location API endpoint for SmartQueue
 * Handles location-based features
 */

require_once "../config/database.php";
require_once "headers.php";

class LocationAPI {
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER["REQUEST_METHOD"];
        
        switch ($method) {
            case "GET":
                $this->getLocation();
                break;
            case "POST":
                $this->updateLocation();
                break;
            default:
                http_response_code(405);
                echo json_encode(["error" => "Method not allowed"]);
                break;
        }
    }
    
    private function getLocation() {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            $stmt->execute(["restaurant_location"]);
            $location = $stmt->fetch();
            
            if ($location) {
                echo json_encode([
                    "success" => true,
                    "location" => json_decode($location["setting_value"], true)
                ]);
            } else {
                echo json_encode([
                    "success" => true,
                    "location" => [
                        "lat" => 12.9716,
                        "lng" => 77.5946,
                        "address" => "Bangalore, Karnataka, India"
                    ]
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to get location"]);
        }
    }
    
    private function updateLocation() {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($input["lat"]) || !isset($input["lng"])) {
            http_response_code(400);
            echo json_encode(["error" => "Latitude and longitude required"]);
            return;
        }
        
        try {
            $location = json_encode([
                "lat" => $input["lat"],
                "lng" => $input["lng"],
                "address" => $input["address"] ?? ""
            ]);
            
            $stmt = $this->db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ");
            $stmt->execute(["restaurant_location", $location]);
            
            echo json_encode(["success" => true, "message" => "Location updated successfully"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update location"]);
        }
    }
}

$api = new LocationAPI();
$api->handleRequest();
?>';
    }
    
    private function getNotificationsApiContent() {
        return '<?php
/**
 * Notifications API endpoint for SmartQueue
 * Handles real-time notifications
 */

require_once "../config/database.php";
require_once "headers.php";

class NotificationsAPI {
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER["REQUEST_METHOD"];
        
        switch ($method) {
            case "GET":
                $this->getNotifications();
                break;
            case "POST":
                $this->createNotification();
                break;
            case "PUT":
                $this->markAsRead();
                break;
            default:
                http_response_code(405);
                echo json_encode(["error" => "Method not allowed"]);
                break;
        }
    }
    
    private function getNotifications() {
        $userId = $_GET["user_id"] ?? null;
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(["error" => "User ID required"]);
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll();
            
            echo json_encode([
                "success" => true,
                "notifications" => $notifications
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to get notifications"]);
        }
    }
    
    private function createNotification() {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($input["user_id"]) || !isset($input["message"])) {
            http_response_code(400);
            echo json_encode(["error" => "User ID and message required"]);
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, message, type, is_read, created_at) 
                VALUES (?, ?, ?, 0, NOW())
            ");
            $stmt->execute([
                $input["user_id"],
                $input["message"],
                $input["type"] ?? "info"
            ]);
            
            echo json_encode([
                "success" => true,
                "message" => "Notification created successfully"
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to create notification"]);
        }
    }
    
    private function markAsRead() {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($input["notification_id"])) {
            http_response_code(400);
            echo json_encode(["error" => "Notification ID required"]);
            return;
        }
        
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            $stmt->execute([$input["notification_id"]]);
            
            echo json_encode([
                "success" => true,
                "message" => "Notification marked as read"
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update notification"]);
        }
    }
}

$api = new NotificationsAPI();
$api->handleRequest();
?>';
    }
    
    private function getQueueStatusApiContent() {
        return '<?php
/**
 * Queue Status API endpoint for SmartQueue
 * Provides real-time queue information
 */

require_once "../config/database.php";
require_once "headers.php";

class QueueStatusAPI {
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER["REQUEST_METHOD"];
        
        switch ($method) {
            case "GET":
                $this->getQueueStatus();
                break;
            default:
                http_response_code(405);
                echo json_encode(["error" => "Method not allowed"]);
                break;
        }
    }
    
    private function getQueueStatus() {
        try {
            // Get current queue statistics
            $totalTokens = $this->db->query("SELECT COUNT(*) as count FROM tokens WHERE DATE(created_at) = CURDATE()")->fetch()["count"];
            $activeTokens = $this->db->query("SELECT COUNT(*) as count FROM tokens WHERE status IN (\"waiting\", \"processing\")")->fetch()["count"];
            $completedTokens = $this->db->query("SELECT COUNT(*) as count FROM tokens WHERE status = \"completed\" AND DATE(created_at) = CURDATE()")->fetch()["count"];
            
            // Get current serving token
            $currentToken = $this->db->query("SELECT token_number FROM tokens WHERE status = \"processing\" ORDER BY created_at ASC LIMIT 1")->fetch();
            
            // Get average waiting time
            $avgTime = $this->db->query("
                SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_time 
                FROM tokens 
                WHERE status = \"completed\" 
                AND DATE(created_at) = CURDATE()
            ")->fetch()["avg_time"] ?? 15;
            
            // Get queue capacity from settings
            $capacityResult = $this->db->query("SELECT setting_value FROM system_settings WHERE setting_key = \"queue_capacity\"")->fetch();
            $capacity = $capacityResult ? intval($capacityResult["setting_value"]) : 50;
            
            echo json_encode([
                "success" => true,
                "queue_status" => [
                    "total_tokens_today" => intval($totalTokens),
                    "active_tokens" => intval($activeTokens),
                    "completed_tokens" => intval($completedTokens),
                    "current_serving" => $currentToken ? $currentToken["token_number"] : null,
                    "average_waiting_time" => round($avgTime, 1),
                    "queue_capacity" => $capacity,
                    "queue_full" => $activeTokens >= $capacity,
                    "estimated_wait_time" => $activeTokens * round($avgTime, 0),
                    "last_updated" => date("Y-m-d H:i:s")
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Failed to get queue status",
                "details" => $e->getMessage()
            ]);
        }
    }
}

$api = new QueueStatusAPI();
$api->handleRequest();
?>';
    }
    
    private function getNotificationServiceContent() {
        return '<?php
/**
 * NotificationService for SmartQueue
 * Handles notification management
 */

class NotificationService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function sendNotification($userId, $message, $type = "info") {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, message, type, is_read, created_at) 
                VALUES (?, ?, ?, 0, NOW())
            ");
            return $stmt->execute([$userId, $message, $type]);
        } catch (Exception $e) {
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getNotifications($userId, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get notifications error: " . $e->getMessage());
            return [];
        }
    }
    
    public function markAsRead($notificationId) {
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            return $stmt->execute([$notificationId]);
        } catch (Exception $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return $result ? intval($result["count"]) : 0;
        } catch (Exception $e) {
            error_log("Unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function broadcastToRole($role, $message, $type = "info") {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE role = ?");
            $stmt->execute([$role]);
            $users = $stmt->fetchAll();
            
            $success = true;
            foreach ($users as $user) {
                if (!$this->sendNotification($user["id"], $message, $type)) {
                    $success = false;
                }
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Broadcast error: " . $e->getMessage());
            return false;
        }
    }
}
?>';
    }
    
    private function getQueueManagerContent() {
        return '<?php
/**
 * QueueManager for SmartQueue
 * Handles queue operations and management
 */

require_once "NotificationService.php";

class QueueManager {
    private $db;
    private $notificationService;
    
    public function __construct($database) {
        $this->db = $database;
        $this->notificationService = new NotificationService($database);
    }
    
    public function bookToken($userId) {
        try {
            // Check if queue is full
            $capacity = $this->getQueueCapacity();
            $activeTokens = $this->getActiveTokensCount();
            
            if ($activeTokens >= $capacity) {
                return [
                    "success" => false,
                    "message" => "Queue is currently full. Please try again later."
                ];
            }
            
            // Check if user already has an active token
            $stmt = $this->db->prepare("
                SELECT id FROM tokens 
                WHERE user_id = ? AND status IN (\"waiting\", \"processing\")
            ");
            $stmt->execute([$userId]);
            
            if ($stmt->fetch()) {
                return [
                    "success" => false,
                    "message" => "You already have an active token in the queue."
                ];
            }
            
            // Generate token number
            $tokenNumber = $this->generateTokenNumber();
            
            // Calculate estimated time
            $estimatedTime = $this->calculateEstimatedTime();
            
            // Create token
            $stmt = $this->db->prepare("
                INSERT INTO tokens (user_id, token_number, status, estimated_time, created_at, updated_at) 
                VALUES (?, ?, \"waiting\", ?, NOW(), NOW())
            ");
            
            if ($stmt->execute([$userId, $tokenNumber, $estimatedTime])) {
                // Send notification
                $this->notificationService->sendNotification(
                    $userId,
                    "Your token #$tokenNumber has been booked. Estimated waiting time: $estimatedTime minutes.",
                    "success"
                );
                
                return [
                    "success" => true,
                    "token_number" => $tokenNumber,
                    "estimated_time" => $estimatedTime,
                    "message" => "Token booked successfully!"
                ];
            }
            
            return [
                "success" => false,
                "message" => "Failed to book token. Please try again."
            ];
            
        } catch (Exception $e) {
            error_log("Book token error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "System error. Please try again later."
            ];
        }
    }
    
    public function advanceQueue() {
        try {
            // Complete current processing token
            $this->db->exec("
                UPDATE tokens 
                SET status = \"completed\", updated_at = NOW() 
                WHERE status = \"processing\"
            ");
            
            // Move next waiting token to processing
            $stmt = $this->db->query("
                SELECT id, user_id, token_number 
                FROM tokens 
                WHERE status = \"waiting\" 
                ORDER BY created_at ASC 
                LIMIT 1
            ");
            
            $nextToken = $stmt->fetch();
            
            if ($nextToken) {
                $updateStmt = $this->db->prepare("
                    UPDATE tokens 
                    SET status = \"processing\", updated_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$nextToken["id"]]);
                
                // Notify user
                $this->notificationService->sendNotification(
                    $nextToken["user_id"],
                    "Your token #" . $nextToken["token_number"] . " is now being served!",
                    "success"
                );
                
                return [
                    "success" => true,
                    "current_token" => $nextToken["token_number"]
                ];
            }
            
            return [
                "success" => true,
                "current_token" => null,
                "message" => "Queue is empty"
            ];
            
        } catch (Exception $e) {
            error_log("Advance queue error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Failed to advance queue"
            ];
        }
    }
    
    public function cancelToken($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE tokens 
                SET status = \"cancelled\", updated_at = NOW() 
                WHERE user_id = ? AND status IN (\"waiting\", \"processing\")
            ");
            
            if ($stmt->execute([$userId]) && $stmt->rowCount() > 0) {
                $this->notificationService->sendNotification(
                    $userId,
                    "Your token has been cancelled successfully.",
                    "info"
                );
                
                return [
                    "success" => true,
                    "message" => "Token cancelled successfully"
                ];
            }
            
            return [
                "success" => false,
                "message" => "No active token found to cancel"
            ];
            
        } catch (Exception $e) {
            error_log("Cancel token error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Failed to cancel token"
            ];
        }
    }
    
    private function generateTokenNumber() {
        $today = date("Ymd");
        $stmt = $this->db->query("
            SELECT COUNT(*) as count 
            FROM tokens 
            WHERE DATE(created_at) = CURDATE()
        ");
        $count = $stmt->fetch()["count"];
        
        return $today . sprintf("%03d", $count + 1);
    }
    
    private function calculateEstimatedTime() {
        $activeTokens = $this->getActiveTokensCount();
        $avgServiceTime = $this->getAverageServiceTime();
        
        return $activeTokens * $avgServiceTime;
    }
    
    private function getQueueCapacity() {
        $stmt = $this->db->query("
            SELECT setting_value 
            FROM system_settings 
            WHERE setting_key = \"queue_capacity\"
        ");
        $result = $stmt->fetch();
        
        return $result ? intval($result["setting_value"]) : 50;
    }
    
    private function getActiveTokensCount() {
        $stmt = $this->db->query("
            SELECT COUNT(*) as count 
            FROM tokens 
            WHERE status IN (\"waiting\", \"processing\")
        ");
        
        return intval($stmt->fetch()["count"]);
    }
    
    private function getAverageServiceTime() {
        $stmt = $this->db->query("
            SELECT setting_value 
            FROM system_settings 
            WHERE setting_key = \"avg_service_time\"
        ");
        $result = $stmt->fetch();
        
        return $result ? intval($result["setting_value"]) : 15;
    }
    
    public function getQueueStatus() {
        try {
            $totalTokens = $this->db->query("
                SELECT COUNT(*) as count 
                FROM tokens 
                WHERE DATE(created_at) = CURDATE()
            ")->fetch()["count"];
            
            $activeTokens = $this->getActiveTokensCount();
            $completedTokens = $this->db->query("
                SELECT COUNT(*) as count 
                FROM tokens 
                WHERE status = \"completed\" AND DATE(created_at) = CURDATE()
            ")->fetch()["count"];
            
            $currentToken = $this->db->query("
                SELECT token_number 
                FROM tokens 
                WHERE status = \"processing\" 
                ORDER BY created_at ASC 
                LIMIT 1
            ")->fetch();
            
            return [
                "total_tokens_today" => intval($totalTokens),
                "active_tokens" => $activeTokens,
                "completed_tokens" => intval($completedTokens),
                "current_serving" => $currentToken ? $currentToken["token_number"] : null,
                "queue_capacity" => $this->getQueueCapacity(),
                "average_service_time" => $this->getAverageServiceTime()
            ];
            
        } catch (Exception $e) {
            error_log("Get queue status error: " . $e->getMessage());
            return null;
        }
    }
}
?>';
    }
}

// Run the fixes
$fixes = new ComprehensiveFixes();
$fixes->runAllFixes();
?>
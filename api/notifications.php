<?php
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
?>
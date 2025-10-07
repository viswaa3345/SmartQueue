<?php
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
?>
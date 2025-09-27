<?php
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
?>
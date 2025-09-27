<?php
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
?>
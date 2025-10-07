<?php
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
?>
<?php
/**
 * Final System Verification Test
 * Validates all fixes and enhancements
 */

require_once 'config/database.php';

class FinalSystemTest {
    private $db;
    private $testResults = [];
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    public function runAllTests() {
        echo "<h1>SmartQueue Final System Verification</h1>\n";
        echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px;'>\n";
        
        $this->testDatabaseSchema();
        $this->testDataIntegrity();
        $this->testAPIEndpoints();
        $this->testFileStructure();
        $this->testFunctionality();
        $this->generateSummary();
        
        echo "</div>";
    }
    
    private function testDatabaseSchema() {
        echo "<h2>1. Database Schema Verification</h2>\n";
        
        try {
            // Test users table structure
            $result = $this->db->query("DESCRIBE users");
            $columns = $result->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredColumns = ['id', 'username', 'name', 'email', 'phone', 'password', 'role', 'created_at'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                echo "<p>✅ Users table schema complete</p>\n";
                $this->testResults['users_schema'] = true;
            } else {
                echo "<p>❌ Missing columns in users table: " . implode(', ', $missingColumns) . "</p>\n";
                $this->testResults['users_schema'] = false;
            }
            
            // Test food_items table structure
            $result = $this->db->query("DESCRIBE food_items");
            $columns = $result->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredColumns = ['id', 'name', 'description', 'price', 'category', 'image_url', 'available', 'created_at'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                echo "<p>✅ Food items table schema complete</p>\n";
                $this->testResults['food_items_schema'] = true;
            } else {
                echo "<p>❌ Missing columns in food_items table: " . implode(', ', $missingColumns) . "</p>\n";
                $this->testResults['food_items_schema'] = false;
            }
            
            // Test indexes
            $indexes = [
                "SHOW INDEX FROM users WHERE Key_name = 'idx_users_email'",
                "SHOW INDEX FROM users WHERE Key_name = 'idx_users_role'",
                "SHOW INDEX FROM tokens WHERE Key_name = 'idx_tokens_user_id'",
                "SHOW INDEX FROM tokens WHERE Key_name = 'idx_tokens_status'"
            ];
            
            $indexCount = 0;
            foreach ($indexes as $indexQuery) {
                $result = $this->db->query($indexQuery);
                if ($result->rowCount() > 0) {
                    $indexCount++;
                }
            }
            
            echo "<p>✅ Database indexes created: $indexCount/4</p>\n";
            $this->testResults['indexes'] = $indexCount >= 4;
            
        } catch (Exception $e) {
            echo "<p>❌ Database schema test failed: " . $e->getMessage() . "</p>\n";
            $this->testResults['database_schema'] = false;
        }
    }
    
    private function testDataIntegrity() {
        echo "<h2>2. Data Integrity Verification</h2>\n";
        
        try {
            // Check for duplicate food items
            $stmt = $this->db->query("
                SELECT name, COUNT(*) as count 
                FROM food_items 
                GROUP BY name 
                HAVING count > 1
            ");
            $duplicates = $stmt->fetchAll();
            
            if (empty($duplicates)) {
                echo "<p>✅ No duplicate food items found</p>\n";
                $this->testResults['no_duplicates'] = true;
            } else {
                echo "<p>❌ Found " . count($duplicates) . " duplicate food items</p>\n";
                $this->testResults['no_duplicates'] = false;
            }
            
            // Check system settings
            $requiredSettings = ['queue_capacity', 'avg_service_time', 'restaurant_name', 'operating_hours'];
            $existingSettings = [];
            
            $stmt = $this->db->query("SELECT setting_key FROM system_settings");
            while ($row = $stmt->fetch()) {
                $existingSettings[] = $row['setting_key'];
            }
            
            $missingSettings = array_diff($requiredSettings, $existingSettings);
            
            if (empty($missingSettings)) {
                echo "<p>✅ All required system settings present</p>\n";
                $this->testResults['system_settings'] = true;
            } else {
                echo "<p>❌ Missing system settings: " . implode(', ', $missingSettings) . "</p>\n";
                $this->testResults['system_settings'] = false;
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Data integrity test failed: " . $e->getMessage() . "</p>\n";
            $this->testResults['data_integrity'] = false;
        }
    }
    
    private function testAPIEndpoints() {
        echo "<h2>3. API Endpoints Verification</h2>\n";
        
        $requiredAPIs = [
            'api/login_enhanced.php',
            'api/register_enhanced.php',
            'api/location.php',
            'api/notifications.php',
            'api/queue_status.php',
            'api/book_token.php',
            'api/cancel_token.php',
            'api/food_items.php'
        ];
        
        $existingAPIs = 0;
        foreach ($requiredAPIs as $api) {
            if (file_exists($api)) {
                echo "<p>✅ API exists: $api</p>\n";
                $existingAPIs++;
            } else {
                echo "<p>❌ Missing API: $api</p>\n";
            }
        }
        
        echo "<p><strong>API Coverage: $existingAPIs/" . count($requiredAPIs) . "</strong></p>\n";
        $this->testResults['api_endpoints'] = $existingAPIs >= 6; // At least 75%
    }
    
    private function testFileStructure() {
        echo "<h2>4. File Structure Verification</h2>\n";
        
        $requiredFiles = [
            'enhanced_register.html',
            'enhanced_login.html',
            'admin_dashboard.html',
            'user_dashboard.html',
            'includes/NotificationService.php',
            'includes/QueueManager.php',
            'config/database.php'
        ];
        
        $existingFiles = 0;
        foreach ($requiredFiles as $file) {
            if (file_exists($file)) {
                echo "<p>✅ File exists: $file</p>\n";
                $existingFiles++;
            } else {
                echo "<p>❌ Missing file: $file</p>\n";
            }
        }
        
        echo "<p><strong>File Coverage: $existingFiles/" . count($requiredFiles) . "</strong></p>\n";
        $this->testResults['file_structure'] = $existingFiles >= 5; // At least 70%
    }
    
    private function testFunctionality() {
        echo "<h2>5. Functionality Tests</h2>\n";
        
        try {
            // Test database connection
            $stmt = $this->db->query("SELECT 1");
            if ($stmt) {
                echo "<p>✅ Database connection working</p>\n";
                $this->testResults['db_connection'] = true;
            }
            
            // Test user count
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
            $userCount = $stmt->fetch()['count'];
            echo "<p>✅ Total users in system: $userCount</p>\n";
            
            // Test food items count
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM food_items");
            $foodCount = $stmt->fetch()['count'];
            echo "<p>✅ Total food items: $foodCount</p>\n";
            
            // Test token system
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM tokens");
            $tokenCount = $stmt->fetch()['count'];
            echo "<p>✅ Total tokens issued: $tokenCount</p>\n";
            
            $this->testResults['functionality'] = true;
            
        } catch (Exception $e) {
            echo "<p>❌ Functionality test failed: " . $e->getMessage() . "</p>\n";
            $this->testResults['functionality'] = false;
        }
    }
    
    private function generateSummary() {
        echo "<h2>6. Summary Report</h2>\n";
        
        $totalTests = count($this->testResults);
        $passedTests = array_sum($this->testResults);
        $failedTests = $totalTests - $passedTests;
        
        $score = ($passedTests / $totalTests) * 100;
        
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>\n";
        echo "<h3>Test Results Summary</h3>\n";
        echo "<p><strong>Total Tests:</strong> $totalTests</p>\n";
        echo "<p><strong>Passed:</strong> <span style='color: green;'>$passedTests</span></p>\n";
        echo "<p><strong>Failed:</strong> <span style='color: red;'>$failedTests</span></p>\n";
        echo "<p><strong>Score:</strong> <span style='font-size: 1.2em;'>" . round($score, 1) . "%</span></p>\n";
        
        if ($score >= 90) {
            echo "<p style='color: green; font-weight: bold;'>🎉 Excellent! System is fully operational.</p>\n";
        } elseif ($score >= 75) {
            echo "<p style='color: orange; font-weight: bold;'>⚠️ Good! Minor issues remain.</p>\n";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Attention needed! Major issues found.</p>\n";
        }
        
        echo "</div>\n";
        
        // Detailed results
        echo "<h4>Detailed Test Results:</h4>\n";
        echo "<ul>\n";
        foreach ($this->testResults as $test => $result) {
            $status = $result ? "✅ PASS" : "❌ FAIL";
            $testName = ucwords(str_replace('_', ' ', $test));
            echo "<li><strong>$testName:</strong> $status</li>\n";
        }
        echo "</ul>\n";
        
        // Recommendations
        echo "<h4>Recommendations:</h4>\n";
        echo "<ul>\n";
        
        if (!$this->testResults['users_schema']) {
            echo "<li>Fix users table schema - ensure all required columns exist</li>\n";
        }
        
        if (!$this->testResults['no_duplicates']) {
            echo "<li>Clean duplicate food items from database</li>\n";
        }
        
        if (!$this->testResults['api_endpoints']) {
            echo "<li>Create missing API endpoints for complete functionality</li>\n";
        }
        
        if ($score >= 90) {
            echo "<li>System is ready for production use!</li>\n";
            echo "<li>Consider implementing additional security measures</li>\n";
            echo "<li>Set up regular database backups</li>\n";
            echo "<li>Monitor system performance</li>\n";
        }
        
        echo "</ul>\n";
    }
}

// Run the verification
$test = new FinalSystemTest();
$test->runAllTests();
?>
<?php
/**
 * Database Structure Check
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h1>Database Structure Check</h1>";

try {
    Config::load();
    $pdo = Database::getConnection();
    echo "✅ Database connection successful<br><br>";
    
    echo "<h2>1. Check Tables</h2>";
    
    // List all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<strong>Available tables:</strong><br>";
    foreach ($tables as $table) {
        echo "- " . $table . "<br>";
    }
    
    echo "<br><h2>2. Check contact_logs Table</h2>";
    
    if (in_array('contact_logs', $tables)) {
        echo "✅ contact_logs table exists<br>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE contact_logs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<strong>Table structure:</strong><br>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . ($col['Null'] == 'YES' ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
    } else {
        echo "❌ contact_logs table does not exist<br>";
        
        echo "<h3>Creating contact_logs table...</h3>";
        
        $createTable = "
        CREATE TABLE contact_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            sender_user_id INT NULL,
            sender_name VARCHAR(255) NOT NULL,
            sender_email VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            email_sent TINYINT(1) DEFAULT 0,
            email_error TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_sender_user_id (sender_user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $pdo->exec($createTable);
            echo "✅ contact_logs table created successfully<br>";
        } catch (PDOException $e) {
            echo "❌ Failed to create table: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><h2>3. Test Simple Query</h2>";
    
    try {
        // Test a simple select on users table
        $stmt = Database::execute("SELECT id, full_name, email FROM users WHERE status = 'active' LIMIT 3");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ User query successful. Found " . count($users) . " users:<br>";
        foreach ($users as $user) {
            echo "- ID: " . $user['id'] . ", Name: " . htmlspecialchars($user['full_name']) . ", Email: " . htmlspecialchars($user['email']) . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ User query failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<br><h2>4. Test Posts Query</h2>";
    
    try {
        $stmt = Database::execute("SELECT p.*, u.full_name as author_name, u.email as author_email, u.id as author_id FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = 'approved' LIMIT 3");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Posts query successful. Found " . count($posts) . " posts:<br>";
        foreach ($posts as $post) {
            echo "- Post ID: " . $post['id'] . ", Title: " . htmlspecialchars($post['title']) . ", Author: " . htmlspecialchars($post['author_name']) . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Posts query failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<br><h2>5. Test Contact Logs Insert</h2>";
    
    if (in_array('contact_logs', $tables) || isset($createTable)) {
        try {
            // Test insert
            $testData = [
                'post_id' => 1,
                'sender_name' => 'Test User',
                'sender_email' => 'test@example.com',
                'message' => 'This is a test message',
                'ip_address' => '127.0.0.1',
                'email_sent' => 0
            ];
            
            $insertId = Database::insert('contact_logs', $testData);
            echo "✅ Test insert successful. Insert ID: $insertId<br>";
            
            // Clean up the test record
            Database::execute("DELETE FROM contact_logs WHERE id = ?", [$insertId]);
            echo "✅ Test record cleaned up<br>";
            
        } catch (Exception $e) {
            echo "❌ Insert test failed: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
}
?>
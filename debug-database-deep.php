<?php
/**
 * Debug Database Issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h1>Database Debug</h1>";

try {
    Config::load();
    $pdo = Database::getConnection();
    echo "✅ Database connection successful<br><br>";
    
    echo "<h2>1. Check Users Table Structure</h2>";
    
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<strong>Users table structure:</strong><br>";
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
        
    } catch (Exception $e) {
        echo "❌ Users table error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>2. Test Different User Queries</h2>";
    
    // Try different column combinations
    $queries = [
        "SELECT id, name, email FROM users LIMIT 2",
        "SELECT id, full_name, email FROM users LIMIT 2", 
        "SELECT id, username, email FROM users LIMIT 2",
        "SELECT * FROM users LIMIT 1"
    ];
    
    foreach ($queries as $i => $query) {
        echo "<strong>Query " . ($i + 1) . ":</strong> " . $query . "<br>";
        try {
            $stmt = Database::execute($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                echo "✅ Success! Columns: " . implode(', ', array_keys($result)) . "<br>";
            } else {
                echo "✅ Query worked but no results<br>";
            }
        } catch (Exception $e) {
            echo "❌ Failed: " . $e->getMessage() . "<br>";
        }
        echo "<br>";
    }
    
    echo "<h2>3. Test Contact Logs Insert with Correct Columns</h2>";
    
    // Based on the table structure, let's use the correct columns
    $testData = [
        'post_id' => 6, // Using post ID from your data
        'sender_name' => 'Test User Debug',
        'sender_email' => 'test@example.com',
        'message' => 'This is a debug test message',
        'ip_address' => '127.0.0.1',
        'email_sent' => 0
        // Note: sender_user_id and sent_at will be handled automatically
    ];
    
    echo "Attempting to insert: " . print_r($testData, true) . "<br>";
    
    try {
        $insertId = Database::insert('contact_logs', $testData);
        echo "✅ Insert successful! ID: $insertId<br>";
        
        // Verify the insert
        $stmt = Database::execute("SELECT * FROM contact_logs WHERE id = ?", [$insertId]);
        $inserted = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inserted) {
            echo "✅ Verification successful:<br>";
            foreach ($inserted as $key => $value) {
                echo "- $key: " . htmlspecialchars($value ?? 'NULL') . "<br>";
            }
        }
        
        // Clean up
        Database::execute("DELETE FROM contact_logs WHERE id = ?", [$insertId]);
        echo "✅ Test record cleaned up<br>";
        
    } catch (Exception $e) {
        echo "❌ Insert failed: " . $e->getMessage() . "<br>";
        
        // Let's try with even more basic data
        echo "<br><strong>Trying minimal insert:</strong><br>";
        try {
            $minimalData = [
                'post_id' => 6,
                'sender_name' => 'Test',
                'sender_email' => 'test@test.com',
                'message' => 'Test message',
                'ip_address' => '127.0.0.1'
            ];
            
            $insertId2 = Database::insert('contact_logs', $minimalData);
            echo "✅ Minimal insert successful! ID: $insertId2<br>";
            
            // Clean up
            Database::execute("DELETE FROM contact_logs WHERE id = ?", [$insertId2]);
            echo "✅ Minimal test record cleaned up<br>";
            
        } catch (Exception $e2) {
            echo "❌ Even minimal insert failed: " . $e2->getMessage() . "<br>";
        }
    }
    
    echo "<h2>4. Raw SQL Test</h2>";
    
    // Try direct SQL without the Database wrapper
    try {
        $sql = "INSERT INTO contact_logs (post_id, sender_name, sender_email, message, ip_address, email_sent) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([6, 'Raw Test', 'raw@test.com', 'Raw SQL test message', '127.0.0.1', 0]);
        
        if ($result) {
            $rawId = $pdo->lastInsertId();
            echo "✅ Raw SQL insert successful! ID: $rawId<br>";
            
            // Clean up
            $pdo->prepare("DELETE FROM contact_logs WHERE id = ?")->execute([$rawId]);
            echo "✅ Raw test record cleaned up<br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ Raw SQL failed: " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>General Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
}
?>
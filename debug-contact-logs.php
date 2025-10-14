<?php
echo "<h2>Contact Logs Table Diagnostic</h2>";

try {
    require_once './includes/db.php';
    $pdo = Database::getConnection();
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Check contact_logs table structure
    echo "<h3>Contact Logs Table Structure:</h3>";
    $stmt = $pdo->prepare("DESCRIBE contact_logs");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test a simple direct insert
    echo "<h3>Testing Direct Insert:</h3>";
    try {
        $stmt = $pdo->prepare("INSERT INTO contact_logs (post_id, sender_name, sender_email, sender_phone, message, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            1,
            'Direct Test User',
            'direct@test.com',
            '123456789',
            'This is a direct insert test',
            date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Direct insert successful</p>";
        } else {
            echo "<p style='color: red;'>❌ Direct insert failed</p>";
            print_r($stmt->errorInfo());
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Direct insert error: " . $e->getMessage() . "</p>";
    }
    
    // Test Database::insert method with debug
    echo "<h3>Testing Database::insert Method:</h3>";
    try {
        $contactData = [
            'post_id' => 1,
            'sender_name' => 'Method Test User',
            'sender_email' => 'method@test.com',
            'sender_phone' => '987654321',
            'message' => 'This is a Database::insert test',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        echo "<p>Data to insert:</p>";
        echo "<pre>";
        print_r($contactData);
        echo "</pre>";
        
        $result = Database::insert('contact_logs', $contactData);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Database::insert successful - ID: $result</p>";
        } else {
            echo "<p style='color: red;'>❌ Database::insert returned false</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database::insert error: " . $e->getMessage() . "</p>";
        echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
    }
    
    // Show recent contact logs
    echo "<h3>Recent Contact Logs:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM contact_logs ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($logs)) {
        echo "<p>No contact logs found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        foreach (array_keys($logs[0]) as $column) {
            echo "<th>$column</th>";
        }
        echo "</tr>";
        
        foreach ($logs as $log) {
            echo "<tr>";
            foreach ($log as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 20px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
</style>

<p><a href="/safekeep-v2/" class="btn btn-secondary">Back to SafeKeep</a></p>
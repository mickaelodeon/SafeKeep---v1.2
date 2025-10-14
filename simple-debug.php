<?php
// Simple debug that will always work
echo "<h2>Simple Database Debug</h2>";
echo "<p>Script started successfully!</p>";

// Check if we have POST data
if (!empty($_POST)) {
    echo "<h3>✅ Form submitted!</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "<h3>ℹ️ No form data - running basic checks</h3>";
}

// Test basic PHP functionality
echo "<p>✅ PHP is working</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Test database connection step by step
echo "<h3>Testing Database Connection:</h3>";

try {
    echo "<p>1. Attempting to include db.php...</p>";
    require_once './includes/db.php';
    echo "<p>✅ db.php included successfully</p>";
    
    echo "<p>2. Attempting to get database connection...</p>";
    $pdo = Database::getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    echo "<p>3. Testing simple query...</p>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>✅ Simple query works - Found {$result['count']} users</p>";
    
    echo "<p>4. Finding your user ID...</p>";
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ?");
    $stmt->execute(['johnmichaeleborda79@gmail.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>✅ Found your user: ID {$user['id']} - {$user['full_name']}</p>";
        $yourUserId = $user['id'];
    } else {
        echo "<p>❌ Your user not found - will use NULL for sender_user_id</p>";
        $yourUserId = null;
    }
    
    echo "<p>5. Testing direct contact_logs insert...</p>";
    
    $stmt = $pdo->prepare("
        INSERT INTO contact_logs 
        (post_id, sender_user_id, sender_name, sender_email, message, ip_address) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        1, // post_id
        $yourUserId, // sender_user_id (your actual ID or NULL)
        'Simple Test User',
        'simpletest@example.com',
        'This is a simple test message to check if database insert works',
        '127.0.0.1'
    ]);
    
    if ($success) {
        $insertId = $pdo->lastInsertId();
        echo "<p style='color: green; font-weight: bold;'>🎉 SUCCESS! Contact log inserted with ID: $insertId</p>";
        
        // Now test the Database::insert method
        echo "<p>6. Testing Database::insert method...</p>";
        
        $contactData = [
            'post_id' => 1,
            'sender_user_id' => $yourUserId,
            'sender_name' => 'Database Class Test',
            'sender_email' => 'dbclass@test.com',
            'message' => 'Testing Database::insert method',
            'ip_address' => '127.0.0.1'
        ];
        
        $result = Database::insert('contact_logs', $contactData);
        
        if ($result) {
            echo "<p style='color: green; font-weight: bold;'>🎉 Database::insert also works! ID: $result</p>";
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>✅ CONTACT FORM SHOULD NOW WORK!</h4>";
            echo "<p>Both direct PDO and Database::insert methods are working.</p>";
            echo "<p>Your actual user ID is: <strong>$yourUserId</strong></p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Database::insert failed - but direct PDO works</p>";
            echo "<p>This means there's an issue with the Database::insert method itself.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Direct insert failed</p>";
        echo "<p>Error info:</p>";
        echo "<pre>";
        print_r($stmt->errorInfo());
        echo "</pre>";
    }
    
    echo "<h3>Recent Contact Logs:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM contact_logs ORDER BY sent_at DESC LIMIT 3");
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    if ($logs) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Post ID</th><th>Sender</th><th>Email</th><th>Message</th><th>Sent At</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>{$log['post_id']}</td>";
            echo "<td>{$log['sender_name']}</td>";
            echo "<td>{$log['sender_email']}</td>";
            echo "<td>" . substr($log['message'], 0, 50) . "...</td>";
            echo "<td>{$log['sent_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No contact logs found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Error details:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Database Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <form method="POST" action="">
        <button type="submit" name="test" value="1">🔄 Run Test Again</button>
    </form>
    
    <p><a href="/safekeep-v2/">← Back to SafeKeep</a></p>
</body>
</html>
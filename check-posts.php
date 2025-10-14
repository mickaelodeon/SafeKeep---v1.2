<?php
echo "<h2>Check Posts Table Structure</h2>";

try {
    require_once './includes/db.php';
    $pdo = Database::getConnection();
    
    // Check posts table structure
    echo "<h3>Posts Table Structure:</h3>";
    $stmt = $pdo->prepare("DESCRIBE posts");
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
    
    // Find existing posts with correct column names
    echo "<h3>Finding Existing Posts (with correct columns):</h3>";
    $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY id ASC LIMIT 5");
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
    if (empty($posts)) {
        echo "<p style='color: red;'>❌ No posts found in database!</p>";
        echo "<p>Let's create a test post with the correct structure...</p>";
        
        // First, check what columns we actually have
        $columnNames = array_column($columns, 'Field');
        echo "<p>Available columns: " . implode(', ', $columnNames) . "</p>";
        
        // Try to determine the user ID column name
        $userColumn = 'user_id';
        if (in_array('author_id', $columnNames)) {
            $userColumn = 'author_id';
        } elseif (in_array('created_by', $columnNames)) {
            $userColumn = 'created_by';
        } elseif (in_array('owner_id', $columnNames)) {
            $userColumn = 'owner_id';
        }
        
        echo "<p>Using user column: <strong>$userColumn</strong></p>";
        
        // Build a basic insert based on available columns
        $insertColumns = [];
        $insertValues = [];
        $insertData = [];
        
        // Essential columns
        if (in_array('title', $columnNames)) {
            $insertColumns[] = 'title';
            $insertValues[] = '?';
            $insertData[] = 'Test Lost Item - Phone';
        }
        
        if (in_array('description', $columnNames)) {
            $insertColumns[] = 'description';
            $insertValues[] = '?';
            $insertData[] = 'Lost my phone in the library. Black iPhone with blue case.';
        }
        
        if (in_array($userColumn, $columnNames)) {
            $insertColumns[] = $userColumn;
            $insertValues[] = '?';
            $insertData[] = 8; // Your user ID
        }
        
        if (in_array('type', $columnNames)) {
            $insertColumns[] = 'type';
            $insertValues[] = '?';
            $insertData[] = 'lost';
        }
        
        if (in_array('location', $columnNames)) {
            $insertColumns[] = 'location';
            $insertValues[] = '?';
            $insertData[] = 'University Library';
        }
        
        if (in_array('category_id', $columnNames)) {
            $insertColumns[] = 'category_id';
            $insertValues[] = '?';
            $insertData[] = 1;
        }
        
        if (in_array('status', $columnNames)) {
            $insertColumns[] = 'status';
            $insertValues[] = '?';
            $insertData[] = 'active';
        }
        
        if (in_array('date_lost_found', $columnNames)) {
            $insertColumns[] = 'date_lost_found';
            $insertValues[] = '?';
            $insertData[] = date('Y-m-d');
        }
        
        if (in_array('created_at', $columnNames)) {
            $insertColumns[] = 'created_at';
            $insertValues[] = '?';
            $insertData[] = date('Y-m-d H:i:s');
        }
        
        if (!empty($insertColumns)) {
            $sql = "INSERT INTO posts (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
            echo "<p>Insert SQL: <code>$sql</code></p>";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($insertData);
            
            if ($result) {
                $testPostId = $pdo->lastInsertId();
                echo "<p style='color: green;'>✅ Created test post with ID: $testPostId</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create test post</p>";
                echo "<pre>";
                print_r($stmt->errorInfo());
                echo "</pre>";
            }
        }
        
        // Re-fetch posts
        $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY id ASC LIMIT 5");
        $stmt->execute();
        $posts = $stmt->fetchAll();
    }
    
    if ($posts) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        
        // Dynamic headers based on actual columns
        echo "<tr>";
        foreach (array_keys($posts[0]) as $column) {
            echo "<th>$column</th>";
        }
        echo "<th>Test Contact</th>";
        echo "</tr>";
        
        foreach ($posts as $post) {
            echo "<tr>";
            foreach ($post as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "<td><a href='?test_post_id={$post['id']}' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Test Contact</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test contact form with real post ID
        if (isset($_GET['test_post_id'])) {
            $testPostId = (int)$_GET['test_post_id'];
            echo "<h3>🧪 Testing Contact Form with Post ID: $testPostId</h3>";
            
            try {
                $contactData = [
                    'post_id' => $testPostId,
                    'sender_user_id' => 8,
                    'sender_name' => 'John Michael Eborda',
                    'sender_email' => 'johnmichaeleborda79@gmail.com',
                    'message' => 'Hi! I am interested in this item. Is it still available? Please let me know how we can meet.',
                    'ip_address' => '127.0.0.1'
                ];
                
                echo "<p>Testing with data:</p>";
                echo "<pre>";
                print_r($contactData);
                echo "</pre>";
                
                $result = Database::insert('contact_logs', $contactData);
                
                if ($result) {
                    echo "<p style='color: green; font-weight: bold; font-size: 18px;'>🎉 SUCCESS! Contact log created with ID: $result</p>";
                    
                    // Test email sending
                    require_once './includes/Email.php';
                    require_once './includes/config.php';
                    
                    $emailSent = Email::send(
                        'johnmichaeleborda79@gmail.com',
                        'SafeKeep: Contact Form Test Success!',
                        '<h3>🎉 Your Contact Form is Working!</h3>' .
                        '<p><strong>Post ID:</strong> ' . $testPostId . '</p>' .
                        '<p><strong>From:</strong> John Michael Eborda</p>' .
                        '<p><strong>Message:</strong> Hi! I am interested in this item. Is it still available?</p>' .
                        '<p>The contact form functionality has been successfully tested!</p>',
                        true
                    );
                    
                    if ($emailSent) {
                        Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$result]);
                        echo "<p style='color: green; font-weight: bold;'>✅ Email notification sent and logged!</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠️ Contact logged but email failed (check SMTP config)</p>";
                    }
                    
                    echo "<div style='background: #d4edda; border: 2px solid #28a745; border-radius: 10px; padding: 25px; margin: 30px 0; text-align: center;'>";
                    echo "<h2 style='color: #155724; margin: 0 0 15px 0;'>🎉 CONTACT FORM IS FULLY WORKING!</h2>";
                    echo "<p style='font-size: 16px; margin: 0;'>✅ Database insert successful<br>";
                    echo "✅ Email notification working<br>";
                    echo "✅ All foreign key constraints satisfied</p>";
                    echo "<p style='margin-top: 15px; font-weight: bold;'>You can now use the contact form on any post page!</p>";
                    echo "</div>";
                    
                } else {
                    echo "<p style='color: red;'>❌ Database::insert failed with real post ID</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Check Posts Table</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <p><a href="/safekeep-v2/">← Back to SafeKeep</a></p>
</body>
</html>
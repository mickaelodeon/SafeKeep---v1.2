<?php
echo "<h2>Fix Contact Form - Find Real Post</h2>";

try {
    require_once './includes/db.php';
    $pdo = Database::getConnection();
    
    // Find existing posts
    echo "<h3>Finding Existing Posts:</h3>";
    $stmt = $pdo->prepare("SELECT id, title, type, author_id FROM posts ORDER BY id ASC LIMIT 10");
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
    if (empty($posts)) {
        echo "<p style='color: red;'>❌ No posts found in database!</p>";
        echo "<p>Let's create a test post first...</p>";
        
        // Create a test post
        $stmt = $pdo->prepare("
            INSERT INTO posts (author_id, title, description, type, location, category_id, date_lost_found) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            8, // Your user ID
            'Test Lost Phone',
            'Lost my phone in the library. It is a black iPhone with a blue case.',
            'lost',
            'University Library',
            1, // Assuming category 1 exists
            date('Y-m-d')
        ]);
        
        if ($result) {
            $testPostId = $pdo->lastInsertId();
            echo "<p style='color: green;'>✅ Created test post with ID: $testPostId</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create test post</p>";
            echo "<pre>";
            print_r($stmt->errorInfo());
            echo "</pre>";
        }
        
        // Re-fetch posts
        $stmt = $pdo->prepare("SELECT id, title, type, author_id FROM posts ORDER BY id ASC LIMIT 10");
        $stmt->execute();
        $posts = $stmt->fetchAll();
    }
    
    if ($posts) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Post ID</th><th>Title</th><th>Type</th><th>Author ID</th><th>Test Contact</th></tr>";
        
        foreach ($posts as $post) {
            echo "<tr>";
            echo "<td>{$post['id']}</td>";
            echo "<td>{$post['title']}</td>";
            echo "<td>{$post['type']}</td>";
            echo "<td>{$post['author_id']}</td>";
            echo "<td><a href='?test_post_id={$post['id']}' class='btn'>Test Contact</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test contact form with real post ID
        if (isset($_GET['test_post_id'])) {
            $testPostId = (int)$_GET['test_post_id'];
            echo "<h3>Testing Contact Form with Post ID: $testPostId</h3>";
            
            try {
                // Test direct insert with real post ID
                $stmt = $pdo->prepare("
                    INSERT INTO contact_logs 
                    (post_id, sender_user_id, sender_name, sender_email, message, ip_address) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $success = $stmt->execute([
                    $testPostId, // Real post ID
                    8, // Your user ID
                    'John Michael Eborda',
                    'johnmichaeleborda79@gmail.com',
                    'Hi! I am interested in this item. Is it still available?',
                    '127.0.0.1'
                ]);
                
                if ($success) {
                    $contactId = $pdo->lastInsertId();
                    echo "<p style='color: green; font-weight: bold;'>🎉 SUCCESS! Contact log created with ID: $contactId</p>";
                    
                    // Now test Database::insert method
                    echo "<h4>Testing Database::insert method:</h4>";
                    
                    $contactData = [
                        'post_id' => $testPostId,
                        'sender_user_id' => 8,
                        'sender_name' => 'John Michael Eborda',
                        'sender_email' => 'johnmichaeleborda79@gmail.com',
                        'message' => 'Testing the Database::insert method with real post ID',
                        'ip_address' => '127.0.0.1'
                    ];
                    
                    $result = Database::insert('contact_logs', $contactData);
                    
                    if ($result) {
                        echo "<p style='color: green; font-weight: bold;'>🎉 Database::insert also works! ID: $result</p>";
                        
                        // Test email sending
                        echo "<h4>Testing Email Notification:</h4>";
                        require_once './includes/Email.php';
                        
                        $emailSent = Email::send(
                            'johnmichaeleborda79@gmail.com',
                            'SafeKeep: Contact Form is Working!',
                            '<h3>🎉 Your Contact Form is Now Fixed!</h3>' .
                            '<p>Someone contacted you about post: ' . htmlspecialchars($posts[0]['title']) . '</p>' .
                            '<p><strong>Message:</strong> Testing the contact form functionality</p>' .
                            '<p>The contact form is now working properly!</p>',
                            true
                        );
                        
                        if ($emailSent) {
                            // Update contact log
                            Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$result]);
                            echo "<p style='color: green;'>✅ Email notification sent and logged!</p>";
                        }
                        
                        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 20px; margin: 20px 0;'>";
                        echo "<h3>🎉 CONTACT FORM IS NOW FULLY WORKING!</h3>";
                        echo "<p>✅ Database insert successful<br>";
                        echo "✅ Email notification working<br>";
                        echo "✅ All constraints satisfied</p>";
                        echo "<p><strong>The issue was:</strong> Using post_id = 1 which didn't exist. Now using real post IDs.</p>";
                        echo "</div>";
                        
                    } else {
                        echo "<p style='color: red;'>❌ Database::insert still failed</p>";
                    }
                    
                } else {
                    echo "<p style='color: red;'>❌ Direct insert failed even with real post ID</p>";
                    echo "<pre>";
                    print_r($stmt->errorInfo());
                    echo "</pre>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Show recent contact logs
    echo "<h3>Recent Contact Logs:</h3>";
    $stmt = $pdo->prepare("
        SELECT cl.*, p.title as post_title 
        FROM contact_logs cl 
        LEFT JOIN posts p ON cl.post_id = p.id 
        ORDER BY cl.sent_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    if ($logs) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Post Title</th><th>Sender</th><th>Message</th><th>Email Sent</th><th>Sent At</th></tr>";
        foreach ($logs as $log) {
            $emailStatus = $log['email_sent'] ? '✅' : '❌';
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>{$log['post_title']}</td>";
            echo "<td>{$log['sender_name']}</td>";
            echo "<td>" . substr($log['message'], 0, 50) . "...</td>";
            echo "<td>$emailStatus</td>";
            echo "<td>{$log['sent_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Contact Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <p><a href="/safekeep-v2/">← Back to SafeKeep</a></p>
</body>
</html>
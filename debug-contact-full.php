<?php
/**
 * Contact Form Debug - Test the actual contact functionality
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Contact Form Email Debug</h1>";

try {
    // Include necessary files
    require_once 'includes/config.php';
    require_once 'includes/db.php';
    require_once 'includes/Email.php';
    
    // Load configuration
    Config::load();
    
    echo "<h2>1. Configuration Check</h2>";
    echo "Email Enabled: " . (Config::get('mail.enabled') ? 'Yes' : 'No') . "<br>";
    echo "Email Host: " . Config::get('mail.host') . "<br>";
    echo "Email Username: " . Config::get('mail.username') . "<br>";
    echo "From Email: " . Config::get('mail.from_email') . "<br>";
    echo "From Name: " . Config::get('mail.from_name') . "<br>";
    
    echo "<h2>2. Database Connection Test</h2>";
    $pdo = Database::getConnection();
    echo "✅ Database connected successfully<br>";
    
    echo "<h2>3. Sample Post Data</h2>";
    // Get a sample post for testing
    $stmt = Database::execute("SELECT * FROM posts WHERE status = 'active' LIMIT 1");
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        echo "Sample Post ID: " . $post['id'] . "<br>";
        echo "Post Title: " . htmlspecialchars($post['title']) . "<br>";
        echo "Post Owner ID: " . $post['user_id'] . "<br>";
        
        // Get post owner details
        $stmt = Database::execute("SELECT * FROM users WHERE id = ?", [$post['user_id']]);
        $postOwner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($postOwner) {
            echo "Post Owner: " . htmlspecialchars($postOwner['name']) . "<br>";
            echo "Post Owner Email: " . htmlspecialchars($postOwner['email']) . "<br>";
        } else {
            echo "❌ Post owner not found<br>";
        }
        
        echo "<h2>4. Test Contact Email</h2>";
        
        // Simulate the contact form data
        $contactData = [
            'post_id' => $post['id'],
            'post_title' => $post['title'],
            'post_type' => $post['type'],
            'post_owner_name' => $postOwner['name'],
            'post_owner_email' => $postOwner['email'],
            'sender_name' => 'Test User',
            'sender_email' => 'johnmichaeleborda79@gmail.com', // Use your email for testing
            'message' => 'This is a test message from the debug script to verify email functionality is working.'
        ];
        
        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0;'>";
        echo "<strong>Test Contact Details:</strong><br>";
        echo "From: " . $contactData['sender_name'] . " (" . $contactData['sender_email'] . ")<br>";
        echo "To: " . $contactData['post_owner_name'] . " (" . $contactData['post_owner_email'] . ")<br>";
        echo "About: " . $contactData['post_title'] . " (" . $contactData['post_type'] . ")<br>";
        echo "Message: " . $contactData['message'] . "<br>";
        echo "</div>";
        
        // Create the email content (similar to what's in view.php)
        $subject = "SafeKeep: Someone is interested in your " . $contactData['post_type'] . " item - " . $contactData['post_title'];
        
        $emailBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Contact About Your Post</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #007cba; }
                .post-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .message-box { background-color: #e9f4ff; padding: 15px; border-left: 4px solid #007cba; margin: 20px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🛡️ SafeKeep</div>
                    <h2>Someone is interested in your item!</h2>
                </div>
                
                <p>Hello <strong>" . htmlspecialchars($contactData['post_owner_name']) . "</strong>,</p>
                
                <p>Good news! Someone has contacted you about your " . $contactData['post_type'] . " item on SafeKeep.</p>
                
                <div class='post-info'>
                    <h3>📋 Item Details</h3>
                    <p><strong>Title:</strong> " . htmlspecialchars($contactData['post_title']) . "</p>
                    <p><strong>Type:</strong> " . ucfirst($contactData['post_type']) . "</p>
                </div>
                
                <div class='message-box'>
                    <h3>💬 Message from " . htmlspecialchars($contactData['sender_name']) . "</h3>
                    <p>" . nl2br(htmlspecialchars($contactData['message'])) . "</p>
                </div>
                
                <h3>📞 Contact Information</h3>
                <p><strong>Name:</strong> " . htmlspecialchars($contactData['sender_name']) . "</p>
                <p><strong>Email:</strong> <a href='mailto:" . htmlspecialchars($contactData['sender_email']) . "'>" . htmlspecialchars($contactData['sender_email']) . "</a></p>
                
                <p>Please respond directly to their email address to arrange the next steps.</p>
                
                <div class='footer'>
                    <p>This message was sent through SafeKeep Lost & Found System</p>
                    <p>Please do not reply to this email - contact the person directly using the information above</p>
                </div>
            </div>
        </body>
        </html>";
        
        echo "<h2>5. Sending Test Email</h2>";
        echo "Attempting to send email...<br><br>";
        
        // Send the email
        $emailResult = Email::send($contactData['post_owner_email'], $subject, $emailBody, true);
        
        if ($emailResult) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
            echo "✅ <strong>Email sent successfully!</strong><br>";
            echo "Check the recipient's email inbox: " . $contactData['post_owner_email'];
            echo "</div>";
            
            // Also log to contact_logs table
            echo "<h2>6. Logging to Database</h2>";
            try {
                $logStmt = Database::execute("INSERT INTO contact_logs (post_id, sender_name, sender_email, message, status, created_at) VALUES (?, ?, ?, ?, 'sent', NOW())", [
                    $contactData['post_id'],
                    $contactData['sender_name'],
                    $contactData['sender_email'],
                    $contactData['message']
                ]);
                $logResult = $logStmt;
                
                if ($logResult) {
                    echo "✅ Contact logged to database successfully<br>";
                } else {
                    echo "❌ Failed to log contact to database<br>";
                }
            } catch (Exception $e) {
                echo "❌ Database logging error: " . $e->getMessage() . "<br>";
            }
            
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
            echo "❌ <strong>Email sending failed!</strong><br>";
            echo "Check error logs for more details.";
            echo "</div>";
        }
        
    } else {
        echo "❌ No active posts found for testing<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine();
    echo "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>7. PHP Error Log</h2>";
echo "<div style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;'>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $lines = file($errorLog);
    $recentLines = array_slice($lines, -10); // Last 10 lines
    foreach ($recentLines as $line) {
        echo htmlspecialchars($line) . "<br>";
    }
} else {
    echo "No error log found or accessible";
}
echo "</div>";
?>
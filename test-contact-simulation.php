<?php
/**
 * Contact Form Debug - Test actual contact form functionality
 */

session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/Email.php';

echo "<h1>Contact Form Debug Test</h1>";

try {
    Config::load();
    
    echo "<h2>1. System Check</h2>";
    echo "✅ Configuration loaded<br>";
    echo "✅ Database class loaded<br>";
    echo "✅ Email class loaded<br>";
    
    // Test database connection
    $pdo = Database::getConnection();
    echo "✅ Database connection successful<br>";
    
    echo "<h2>2. Sample Data</h2>";
    
    // Get a sample post
    $stmt = Database::execute("SELECT p.*, u.full_name as author_name, u.email as author_email, u.id as author_id FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = 'approved' LIMIT 1");
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo "❌ No approved posts found<br>";
        exit;
    }
    
    echo "Post ID: " . $post['id'] . "<br>";
    echo "Post Title: " . htmlspecialchars($post['title']) . "<br>";
    echo "Post Type: " . $post['type'] . "<br>";
    echo "Author ID: " . $post['author_id'] . "<br>";
    echo "Author Name: " . htmlspecialchars($post['author_name']) . "<br>";
    echo "Author Email: " . htmlspecialchars($post['author_email']) . "<br>";
    
    // Get a sample user (sender)
    $stmt = Database::execute("SELECT * FROM users WHERE status = 'active' AND id != ? LIMIT 1", [$post['author_id']]);
    $sender = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sender) {
        echo "❌ No other active users found for testing<br>";
        exit;
    }
    
    echo "<br>Sender ID: " . $sender['id'] . "<br>";
    echo "Sender Name: " . htmlspecialchars($sender['full_name']) . "<br>";
    echo "Sender Email: " . htmlspecialchars($sender['email']) . "<br>";
    
    echo "<h2>3. Simulate Contact Form Submission</h2>";
    
    $testMessage = "This is a test contact message from the debug script. I'm interested in your " . $post['type'] . " item: " . $post['title'] . ". Please contact me to arrange pickup/return.";
    
    echo "Test message: " . htmlspecialchars($testMessage) . "<br><br>";
    
    // Step 1: Insert into contact_logs
    echo "<strong>Step 1: Database Logging</strong><br>";
    try {
        $contactLogId = Database::insert('contact_logs', [
            'post_id' => $post['id'],
            'sender_user_id' => $sender['id'],
            'sender_name' => $sender['full_name'],
            'sender_email' => $sender['email'],
            'message' => $testMessage,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Debug Script',
            'email_sent' => 0
        ]);
        
        echo "✅ Contact logged to database with ID: $contactLogId<br>";
    } catch (Exception $e) {
        echo "❌ Database logging failed: " . $e->getMessage() . "<br>";
        exit;
    }
    
    // Step 2: Prepare email
    echo "<br><strong>Step 2: Email Preparation</strong><br>";
    
    $emailSubject = "SafeKeep: Someone contacted you about your " . ucfirst($post['type']) . " item";
    echo "Subject: " . $emailSubject . "<br>";
    
    $emailBody = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SafeKeep Notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #007bff; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">SafeKeep</h1>
            <p style="margin: 5px 0 0 0;">Lost & Found System</p>
        </div>
        
        <div style="padding: 20px; background: #f8f9fa;">
            <h2 style="color: #007bff;">Someone is interested in your ' . ucfirst($post['type']) . ' item!</h2>
            
            <div style="background: white; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <h3 style="color: #333; margin-top: 0;">Post: ' . htmlspecialchars($post['title']) . '</h3>
                
                <p><strong>From:</strong> ' . htmlspecialchars($sender['full_name']) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($sender['email']) . '</p>
                
                <div style="background: #e9ecef; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;">
                    <strong>Message:</strong><br>
                    ' . nl2br(htmlspecialchars($testMessage)) . '
                </div>
                
                <div style="margin: 20px 0;">
                    <h4>Item Details:</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li><strong>Type:</strong> ' . ucfirst($post['type']) . ' Item</li>
                        <li><strong>Category:</strong> ' . htmlspecialchars($post['category']) . '</li>
                        <li><strong>Location:</strong> ' . htmlspecialchars($post['location']) . '</li>
                    </ul>
                </div>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <p>You can reply directly to this email to contact ' . htmlspecialchars($sender['full_name']) . ' about your item.</p>
            </div>
        </div>
        
        <div style="text-align: center; padding: 15px; color: #666; font-size: 12px;">
            <p>This is an automated message from SafeKeep Lost & Found System</p>
        </div>
    </div>
</body>
</html>';
    
    echo "Recipient: " . $post['author_email'] . "<br>";
    echo "Email body prepared (HTML format)<br>";
    
    // Step 3: Send email
    echo "<br><strong>Step 3: Email Sending</strong><br>";
    
    try {
        $emailSent = Email::send($post['author_email'], $emailSubject, $emailBody, true);
        
        if ($emailSent) {
            echo "✅ Email sent successfully!<br>";
            
            // Update contact log
            Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$contactLogId]);
            echo "✅ Contact log updated with success status<br>";
            
        } else {
            echo "❌ Email sending failed<br>";
            
            // Update contact log with failure
            Database::execute("UPDATE contact_logs SET email_sent = 0, email_error = ? WHERE id = ?", ['Email sending failed', $contactLogId]);
            echo "❌ Contact log updated with failure status<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Email exception: " . $e->getMessage() . "<br>";
        Database::execute("UPDATE contact_logs SET email_sent = 0, email_error = ? WHERE id = ?", [$e->getMessage(), $contactLogId]);
    }
    
    echo "<h2>4. Verify Results</h2>";
    
    // Check the contact_logs table
    $stmt = Database::execute("SELECT * FROM contact_logs WHERE id = ?", [$contactLogId]);
    $logEntry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($logEntry) {
        echo "<strong>Contact Log Entry:</strong><br>";
        echo "ID: " . $logEntry['id'] . "<br>";
        echo "Post ID: " . $logEntry['post_id'] . "<br>";
        echo "Email Sent: " . ($logEntry['email_sent'] ? 'Yes' : 'No') . "<br>";
        if (!empty($logEntry['email_error'])) {
            echo "Email Error: " . htmlspecialchars($logEntry['email_error']) . "<br>";
        }
        echo "Created: " . $logEntry['created_at'] . "<br>";
    }
    
    if ($emailSent) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
        echo "🎉 <strong>SUCCESS!</strong> Contact form simulation completed successfully!<br>";
        echo "Check the email inbox: " . $post['author_email'];
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        echo "❌ <strong>PARTIAL SUCCESS</strong> Message logged but email failed.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine();
    echo "</div>";
}
?>
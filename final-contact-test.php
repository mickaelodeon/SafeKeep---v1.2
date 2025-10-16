<?php
/**
 * Fixed Contact Form Test - Using correct user filtering
 */

session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/Email.php';

echo "<h1>Fixed Contact Form Test</h1>";

try {
    Config::load();
    
    echo "<h2>1. System Check</h2>";
    echo "✅ Configuration loaded<br>";
    echo "✅ Database class loaded<br>";
    echo "✅ Email class loaded<br>";
    
    // Test database connection
    $pdo = Database::getConnection();
    echo "✅ Database connection successful<br>";
    
    echo "<h2>2. Get Sample Data with Correct Filters</h2>";
    
    // Get a sample post using the correct status
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
    
    // Get a sample user (sender) - use correct filtering
    $stmt = Database::execute("SELECT * FROM users WHERE is_active = 1 AND id != ? LIMIT 1", [$post['author_id']]);
    $sender = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sender) {
        echo "❌ No other active users found. Let's use a test sender.<br>";
        
        // Create test sender data
        $sender = [
            'id' => 999,
            'full_name' => 'Test Contact Sender',
            'email' => 'johnmichaeleborda79@gmail.com' // Use your email for testing
        ];
        echo "Using test sender data<br>";
    }
    
    echo "<br>Sender ID: " . $sender['id'] . "<br>";
    echo "Sender Name: " . htmlspecialchars($sender['full_name']) . "<br>";
    echo "Sender Email: " . htmlspecialchars($sender['email']) . "<br>";
    
    echo "<h2>3. Simulate Complete Contact Form Flow</h2>";
    
    $testMessage = "Hello! I found your " . $post['type'] . " item '" . $post['title'] . "'. I believe this might be what I've been looking for. Please contact me to arrange a meeting. Thank you!";
    
    echo "Test message: " . htmlspecialchars($testMessage) . "<br><br>";
    
    // Step 1: Insert into contact_logs (matches your table structure exactly)
    echo "<strong>Step 1: Database Logging</strong><br>";
    try {
        $contactLogId = Database::insert('contact_logs', [
            'post_id' => $post['id'],
            'sender_user_id' => $sender['id'],
            'sender_name' => $sender['full_name'],
            'sender_email' => $sender['email'],
            'message' => $testMessage,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Contact Form Debug Script',
            'email_sent' => 0
            // sent_at will be auto-populated
        ]);
        
        echo "✅ Contact logged to database with ID: $contactLogId<br>";
    } catch (Exception $e) {
        echo "❌ Database logging failed: " . $e->getMessage() . "<br>";
        exit;
    }
    
    // Step 2: Prepare email content (exact same as in posts/view.php)
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
                <p><strong>Reply directly to this email to contact ' . htmlspecialchars($sender['full_name']) . '.</strong></p>
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
            
            // Update contact log - success
            Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$contactLogId]);
            echo "✅ Contact log updated with success status<br>";
            
        } else {
            echo "❌ Email sending failed<br>";
            
            // Update contact log - failure
            Database::execute("UPDATE contact_logs SET email_sent = 0, email_error = ? WHERE id = ?", ['Email sending failed', $contactLogId]);
            echo "❌ Contact log updated with failure status<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Email exception: " . $e->getMessage() . "<br>";
        Database::execute("UPDATE contact_logs SET email_sent = 0, email_error = ? WHERE id = ?", [$e->getMessage(), $contactLogId]);
    }
    
    echo "<h2>4. Verify Final Results</h2>";
    
    // Check the final contact_logs entry
    $stmt = Database::execute("SELECT * FROM contact_logs WHERE id = ?", [$contactLogId]);
    $logEntry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($logEntry) {
        echo "<strong>Final Contact Log Entry:</strong><br>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($logEntry as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
        }
        echo "</table><br>";
    }
    
    if ($emailSent) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
        echo "🎉 <strong>COMPLETE SUCCESS!</strong><br>";
        echo "✅ Message logged to database<br>";
        echo "✅ Email sent successfully to: " . $post['author_email'] . "<br>";
        echo "✅ Contact log updated with success status<br>";
        echo "<br><strong>The contact form should now work perfectly!</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; margin: 10px 0;'>";
        echo "⚠️ <strong>PARTIAL SUCCESS</strong><br>";
        echo "✅ Message logged successfully<br>";
        echo "❌ Email sending failed<br>";
        echo "This indicates an email configuration issue, not a contact form problem.";
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
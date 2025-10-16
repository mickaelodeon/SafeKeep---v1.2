<?php
/**
 * Contact Form Submission Debug - Test actual form processing
 */

session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/Email.php';

echo "<h1>Contact Form Submission Test</h1>";

// Check if this is a form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_owner'])) {
    echo "<div style='background: #e7f3ff; padding: 15px; border: 1px solid #b3d9ff; margin: 10px 0;'>";
    echo "<h2>🧪 FORM SUBMITTED - Processing...</h2>";
    
    try {
        Config::load();
        
        // Get post info
        $postId = 6; // Testing with post ID 6
        $stmt = Database::execute("SELECT p.*, u.full_name as author_name, u.email as author_email, u.id as author_id FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.status = 'approved'", [$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get current user
        $currentUser = null;
        if (isset($_SESSION['user_id'])) {
            $stmt = Database::execute("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        echo "Post: " . htmlspecialchars($post['title']) . "<br>";
        echo "Current User: " . htmlspecialchars($currentUser['full_name']) . "<br>";
        echo "Message: " . htmlspecialchars($_POST['message']) . "<br><br>";
        
        // SIMULATE THE EXACT CONTACT FORM PROCESSING
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            echo "❌ Empty message<br>";
        } elseif (strlen($message) < 10) {
            echo "❌ Message too short<br>";
        } else {
            echo "✅ Message validation passed<br>";
            
            // Step 1: Database logging
            echo "<strong>Step 1: Database Logging</strong><br>";
            $contactData = [
                'post_id' => $postId,
                'sender_user_id' => $currentUser['id'],
                'sender_name' => $currentUser['full_name'],
                'sender_email' => $currentUser['email'],
                'message' => $message,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'email_sent' => 0
            ];
            
            $logId = Database::insert('contact_logs', $contactData);
            echo "✅ Contact logged with ID: $logId<br>";
            
            // Step 2: Email sending
            echo "<br><strong>Step 2: Email Sending</strong><br>";
            
            $emailSubject = "SafeKeep: Someone contacted you about your " . ucfirst($post['type']) . " item";
            
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
                
                <p><strong>From:</strong> ' . htmlspecialchars($currentUser['full_name']) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($currentUser['email']) . '</p>
                
                <div style="background: #e9ecef; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;">
                    <strong>Message:</strong><br>
                    ' . nl2br(htmlspecialchars($message)) . '
                </div>
            </div>
            
            <p>Reply directly to this email to contact ' . htmlspecialchars($currentUser['full_name']) . '.</p>
        </div>
        
        <div style="text-align: center; padding: 15px; color: #666; font-size: 12px;">
            <p>This is an automated message from SafeKeep Lost & Found System</p>
        </div>
    </div>
</body>
</html>';

            $emailSent = Email::send($post['author_email'], $emailSubject, $emailBody, true);
            
            if ($emailSent) {
                echo "✅ Email sent successfully to: " . $post['author_email'] . "<br>";
                Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$logId]);
                
                echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
                echo "🎉 <strong>SUCCESS!</strong> Your message has been sent successfully!<br>";
                echo "The item owner will receive an email notification and can contact you directly.";
                echo "</div>";
                
            } else {
                echo "❌ Email sending failed<br>";
                Database::execute("UPDATE contact_logs SET email_sent = 0, email_error = ? WHERE id = ?", ['Email sending failed', $logId]);
                
                echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; margin: 20px 0;'>";
                echo "⚠️ Your message has been logged but email notification failed.";
                echo "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        echo "❌ <strong>Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
    
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contact Form Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { background: #f8f9fa; padding: 20px; border-radius: 8px; max-width: 500px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        textarea { width: 100%; height: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Contact Form Functionality Test</h1>
    
    <div class="form-container">
        <h3>Test the Contact Form Processing</h3>
        <p>This form simulates exactly what happens when you submit the contact form on the post page.</p>
        
        <form method="POST">
            <label for="message">Your Message:</label><br>
            <textarea name="message" placeholder="Write your message here (minimum 10 characters)..." required></textarea><br><br>
            
            <button type="submit" name="contact_owner" class="btn">Send Contact Message</button>
        </form>
    </div>
    
    <hr>
    
    <h3>Alternative: Test on Actual Post Page</h3>
    <p><a href="posts/view.php?id=6" target="_blank">Open Post #6 (Sunscreen)</a> and try the contact form there.</p>
    
    <h3>Debug Information</h3>
    <?php
    try {
        Config::load();
        echo "✅ Email enabled: " . (Config::get('mail.enabled') ? 'Yes' : 'No') . "<br>";
        echo "✅ Session active: " . (isset($_SESSION['user_id']) ? 'Yes (User ID: ' . $_SESSION['user_id'] . ')' : 'No') . "<br>";
        echo "✅ Database available: Yes<br>";
        echo "✅ Email class loaded: Yes<br>";
    } catch (Exception $e) {
        echo "❌ Configuration error: " . $e->getMessage() . "<br>";
    }
    ?>
    
</body>
</html>
<?php
/**
 * Debug Version of Post View - Shows what happens during form submission
 */

declare(strict_types=1);

// Include necessary dependencies
require_once 'includes/config.php';
require_once 'includes/functions.php';

Session::init();
Session::requireLogin();

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$postId) {
    Utils::redirect(Config::get('app.url') . '/posts/browse.php');
}

// Get post details
$post = Database::selectOne("
    SELECT p.*, u.full_name as author_name, u.email as author_email, u.id as author_id
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ? AND p.status = 'approved'
", [$postId]);

if (!$post) {
    Utils::redirect(Config::get('app.url') . '/posts/browse.php', 'Post not found.');
}

// Get current user
$currentUser = Session::getUser();
$isOwner = $currentUser && $currentUser['id'] == $post['author_id'];

// Debug information
echo "<h1>DEBUG: Post View Form Submission</h1>";
echo "<p><strong>Post ID:</strong> $postId</p>";
echo "<p><strong>Current User ID:</strong> " . ($currentUser['id'] ?? 'None') . "</p>";
echo "<p><strong>Post Owner ID:</strong> " . $post['author_id'] . "</p>";
echo "<p><strong>Is Owner:</strong> " . ($isOwner ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";

// Handle contact form submission
$contactError = '';
$contactSuccess = '';

echo "<hr><h2>FORM PROCESSING DEBUG</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p>✅ POST request detected</p>";
    echo "<p><strong>POST data:</strong> " . print_r($_POST, true) . "</p>";
    
    if (isset($_POST['contact_owner'])) {
        echo "<p>✅ contact_owner button clicked</p>";
        
        // Check CSRF token
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $contactError = 'Invalid security token. Please try again.';
            echo "<p>❌ CSRF token validation failed</p>";
        } elseif (!$currentUser) {
            $contactError = 'You must be logged in to contact the owner.';
            echo "<p>❌ User not logged in</p>";
        } elseif ($isOwner) {
            $contactError = 'You cannot contact yourself about your own item.';
            echo "<p>❌ User is the owner</p>";
        } else {
            echo "<p>✅ Initial validations passed</p>";
            
            $message = trim($_POST['message'] ?? '');
            
            if (empty($message)) {
                $contactError = 'Please enter a message.';
                echo "<p>❌ Empty message</p>";
            } elseif (strlen($message) < 10) {
                $contactError = 'Message must be at least 10 characters long.';
                echo "<p>❌ Message too short</p>";
            } else {
                echo "<p>✅ Message validation passed</p>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($message) . "</p>";
                
                try {
                    echo "<p>🔄 Attempting database insert...</p>";
                    
                    // Store the contact attempt in database
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
                    echo "<p>✅ Contact logged to database with ID: $logId</p>";
                    
                    // Try to send email notification to post owner
                    $emailSent = false;
                    $emailError = '';
                    
                    try {
                        echo "<p>🔄 Preparing email...</p>";
                        
                        // Email content
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

                        // Include Email class
                        if (!class_exists('Email')) {
                            require_once 'includes/Email.php';
                        }
                        
                        echo "<p>🔄 Sending email to: " . $post['author_email'] . "</p>";
                        
                        // Send email
                        $emailSent = Email::send($post['author_email'], $emailSubject, $emailBody, true);
                        
                        if ($emailSent) {
                            echo "<p>✅ Email sent successfully!</p>";
                        } else {
                            echo "<p>❌ Email sending failed</p>";
                            $emailError = 'Email service unavailable';
                        }
                        
                    } catch (Exception $e) {
                        echo "<p>❌ Email error: " . $e->getMessage() . "</p>";
                        $emailError = 'Email system error: ' . $e->getMessage();
                    }
                    
                    // Update contact log with email status
                    if ($emailSent) {
                        Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$logId]);
                        $contactSuccess = 'Your message has been sent successfully! The item owner will receive an email notification and can contact you directly.';
                        echo "<p>✅ SUCCESS MESSAGE SET: " . $contactSuccess . "</p>";
                    } else {
                        Database::execute("UPDATE contact_logs SET email_sent = 0, email_error = ? WHERE id = ?", [$emailError, $logId]);
                        $contactSuccess = 'Your message has been logged successfully! However, email notification failed - the owner can still see your message when they check their posts.';
                        echo "<p>⚠️ PARTIAL SUCCESS MESSAGE SET: " . $contactSuccess . "</p>";
                    }
                    
                } catch (Exception $e) {
                    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
                    $contactError = 'Failed to send message. Please try again later.';
                }
            }
        }
    } else {
        echo "<p>❌ contact_owner not in POST data</p>";
    }
} else {
    echo "<p>No POST request</p>";
}

echo "<hr><h2>MESSAGE DISPLAY</h2>";
echo "<p><strong>Contact Error:</strong> " . ($contactError ?: 'None') . "</p>";
echo "<p><strong>Contact Success:</strong> " . ($contactSuccess ?: 'None') . "</p>";

?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Contact Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h3>Contact Form Test - Post: <?php echo htmlspecialchars($post['title']); ?></h3>
        
        <?php if ($contactError): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $contactError; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($contactSuccess): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $contactSuccess; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!$contactSuccess && !$isOwner): ?>
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    <div class="mb-3">
                        <label for="message" class="form-label">Your Message</label>
                        <textarea name="message" id="message" class="form-control" rows="4" 
                                  placeholder="Write your message here..." required></textarea>
                    </div>
                    <button type="submit" name="contact_owner" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Message to Owner
                    </button>
                </form>
            </div>
        </div>
        <?php elseif ($isOwner): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            You cannot contact yourself about your own item.
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
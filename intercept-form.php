<?php
/**
 * Capture Real Form Submission Debug
 * This will show exactly what happens when the real posts/view.php form is submitted
 */

// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'form_debug.log');

// Log all POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'post_data' => $_POST,
        'server' => [
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
            'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? '',
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]
    ];
    
    file_put_contents('form_submissions.log', json_encode($logData) . "\n", FILE_APPEND);
}

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';

Session::init();
Session::requireLogin();

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$postId) {
    echo "<p>❌ No post ID provided</p>";
    exit;
}

echo "<h2>🔍 Real Form Submission Interceptor</h2>";
echo "<p><strong>Post ID:</strong> {$postId}</p>";

// Get post details
try {
    $post = Database::selectOne("
        SELECT p.*, u.full_name as author_name, u.email as author_email, u.id as author_id
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND p.status = 'approved'
    ", [$postId]);

    if (!$post) {
        echo "<p>❌ Post not found</p>";
        exit;
    }
    
    echo "<p><strong>Post:</strong> " . htmlspecialchars($post['title']) . "</p>";
    echo "<p><strong>Owner:</strong> " . htmlspecialchars($post['author_name']) . " (" . htmlspecialchars($post['author_email']) . ")</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Get current user
$currentUser = Session::getUser();
$isOwner = $currentUser && $currentUser['id'] == $post['author_id'];

echo "<p><strong>Current User:</strong> " . htmlspecialchars($currentUser['full_name'] ?? 'Not logged in') . "</p>";
echo "<p><strong>Is Owner:</strong> " . ($isOwner ? 'Yes (you own this post)' : 'No (you can contact)') . "</p>";

// Handle form submission with detailed logging
$contactError = '';
$contactSuccess = '';

echo "<h3>📋 Form Processing Status</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p>✅ POST request received</p>";
    
    if (isset($_POST['contact_owner'])) {
        echo "<p>✅ contact_owner field present</p>";
        
        echo "<h4>📥 POST Data:</h4>";
        echo "<pre>" . print_r($_POST, true) . "</pre>";
        
        // Verify CSRF token
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $contactError = 'Invalid security token. Please try again.';
            echo "<p>❌ CSRF token validation failed</p>";
        } elseif (!$currentUser) {
            $contactError = 'You must be logged in to contact the owner.';
            echo "<p>❌ User not logged in</p>";
        } elseif ($isOwner) {
            $contactError = 'You cannot contact yourself about your own post.';
            echo "<p>❌ User is the owner of this post</p>";
        } else {
            echo "<p>✅ Initial validation passed</p>";
            
            $message = trim($_POST['message'] ?? '');
            echo "<p><strong>Message:</strong> '" . htmlspecialchars($message) . "' (Length: " . strlen($message) . ")</p>";
            
            if (empty($message)) {
                $contactError = 'Please enter a message.';
                echo "<p>❌ Empty message</p>";
            } elseif (strlen($message) < 10) {
                $contactError = 'Message must be at least 10 characters long.';
                echo "<p>❌ Message too short</p>";
            } else {
                echo "<p>✅ Message validation passed</p>";
                
                try {
                    echo "<p>🔄 Attempting database insertion...</p>";
                    
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
                    
                    echo "<h4>📝 Contact Data:</h4>";
                    echo "<pre>" . print_r($contactData, true) . "</pre>";
                    
                    Database::insert('contact_logs', $contactData);
                    $insertId = Database::getConnection()->lastInsertId();
                    
                    echo "<p>✅ Database insert successful! New contact log ID: {$insertId}</p>";
                    
                    // Try to send email
                    echo "<p>📧 Attempting email send...</p>";
                    
                    $emailSent = false;
                    try {
                        $postOwner = User::findById($post['author_id']);
                        
                        if ($postOwner && !empty($postOwner['email'])) {
                            echo "<p>✅ Post owner found: " . htmlspecialchars($postOwner['full_name']) . " (" . htmlspecialchars($postOwner['email']) . ")</p>";
                            
                            if (!class_exists('Email')) {
                                require_once 'includes/Email.php';
                                echo "<p>✅ Email class loaded</p>";
                            }
                            
                            $emailSubject = "SafeKeep: Someone contacted you about your " . ucfirst($post['type']) . " item";
                            $emailBody = "Someone contacted you about your post: " . $post['title'] . "\n\n";
                            $emailBody .= "From: " . $currentUser['full_name'] . " (" . $currentUser['email'] . ")\n\n";
                            $emailBody .= "Message:\n" . $message . "\n\n";
                            $emailBody .= "View your post: " . Config::get('app.url') . "/posts/view.php?id=" . $postId;
                            
                            echo "<p>📧 Email details:</p>";
                            echo "<p><strong>To:</strong> " . htmlspecialchars($postOwner['email']) . "</p>";
                            echo "<p><strong>Subject:</strong> " . htmlspecialchars($emailSubject) . "</p>";
                            
                            $emailSent = Email::send($postOwner['email'], $emailSubject, $emailBody);
                            
                            if ($emailSent) {
                                echo "<p>✅ Email sent successfully!</p>";
                                Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$insertId]);
                                echo "<p>✅ Database updated with email status</p>";
                            } else {
                                echo "<p>❌ Email sending failed</p>";
                                Database::execute("UPDATE contact_logs SET email_sent = 0, email_error = 'Email send failed' WHERE id = ?", [$insertId]);
                            }
                        } else {
                            echo "<p>❌ Post owner not found or has no email</p>";
                        }
                        
                    } catch (Exception $e) {
                        echo "<p>❌ Email error: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                    
                    if ($emailSent) {
                        $contactSuccess = 'Your message has been sent successfully! The owner will receive an email notification.';
                    } else {
                        $contactSuccess = 'Your message has been logged successfully! However, email notification failed - the owner can still see your message when they check their posts.';
                    }
                    
                    echo "<p>🎉 <strong>SUCCESS:</strong> " . htmlspecialchars($contactSuccess) . "</p>";
                    
                } catch (Exception $e) {
                    $contactError = 'Failed to send message. Please try again later.';
                    echo "<p>❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    } else {
        echo "<p>❌ contact_owner field not found in POST data</p>";
        echo "<p>Available POST fields: " . implode(', ', array_keys($_POST)) . "</p>";
    }
} else {
    echo "<p>ℹ️ No POST request received yet</p>";
}

// Show success/error messages
if ($contactSuccess) {
    echo "<div style='background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
    echo "<strong>✅ SUCCESS:</strong> " . htmlspecialchars($contactSuccess);
    echo "</div>";
}

if ($contactError) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
    echo "<strong>❌ ERROR:</strong> " . htmlspecialchars($contactError);
    echo "</div>";
}

// Show test form
if (!$isOwner && !$contactSuccess) {
    echo "<h3>🧪 Test Form (Same as posts/view.php)</h3>";
    echo '<form method="POST" style="border: 2px solid #007bff; padding: 20px; max-width: 600px;">';
    echo '<input type="hidden" name="csrf_token" value="' . Security::generateCSRFToken() . '">';
    echo '<div style="margin-bottom: 15px;">';
    echo '<label for="message">Your Message:</label><br>';
    echo '<textarea name="message" id="message" rows="4" cols="50" placeholder="Hi! I believe this is my item..." required minlength="10"></textarea>';
    echo '<div style="font-size: 12px; color: #666; margin-top: 5px;">Be specific about why you think this is your item.</div>';
    echo '</div>';
    echo '<button type="submit" name="contact_owner" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px;">Send Message to Owner</button>';
    echo '</form>';
} elseif ($isOwner) {
    echo "<div style='background: #fff3cd; color: #856404; padding: 10px; border: 1px solid #ffeaa7; margin: 10px 0;'>";
    echo "<strong>ℹ️ INFO:</strong> You own this post, so you cannot contact yourself.";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='posts/view.php?id={$postId}'>🔗 Go to Real Post #{$postId}</a></p>";
echo "<p><a href='check-email-delivery.php'>📧 Check Email Delivery Status</a></p>";
?>
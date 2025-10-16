<?php
/**
 * Debug Real Posts View Form
 * This will show exactly what happens when the real form is submitted
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files (same as posts/view.php)
require_once 'includes/config.php';
require_once 'includes/functions.php';

Session::init();

echo "<h2>🔍 Debug Real Posts/View.php Form Submission</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>📥 POST Data Received</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<h3>📊 Form Analysis</h3>";
    echo "<p>✅ Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
    echo "<p>✅ contact_owner field: " . (isset($_POST['contact_owner']) ? 'Present' : 'Missing') . "</p>";
    echo "<p>✅ csrf_token field: " . (isset($_POST['csrf_token']) ? 'Present' : 'Missing') . "</p>";
    echo "<p>✅ message field: " . (isset($_POST['message']) ? 'Present (' . strlen($_POST['message']) . ' chars)' : 'Missing') . "</p>";
    
    echo "<h3>🔄 Processing Test</h3>";
    echo "<p>This shows what the real posts/view.php should be doing...</p>";
    
    // Get current user (files already included at top)
    $currentUser = Session::getUser();
    
    if (!$currentUser) {
        echo "<p>❌ User not logged in!</p>";
        exit;
    }
    
    echo "<p>✅ User: " . htmlspecialchars($currentUser['full_name'] ?? 'Unknown') . " (ID: " . ($currentUser['id'] ?? 'Unknown') . ")</p>";
    
    // Simulate the same processing logic as posts/view.php
    if (isset($_POST['contact_owner'])) {
        echo "<p>✅ contact_owner field detected - processing...</p>";
        
        // Check CSRF token
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo "<p>❌ CSRF token validation failed!</p>";
        } else {
            echo "<p>✅ CSRF token valid</p>";
            
            $message = trim($_POST['message'] ?? '');
            echo "<p>Message content: '" . htmlspecialchars($message) . "'</p>";
            echo "<p>Message length: " . strlen($message) . " characters</p>";
            
            if (empty($message)) {
                echo "<p>❌ Empty message</p>";
            } elseif (strlen($message) < 10) {
                echo "<p>❌ Message too short</p>";
            } else {
                echo "<p>✅ Message validation passed</p>";
                
                // Test database insertion
                $postId = 10; // Use a test post ID
                
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
                
                echo "<p>📝 Contact data prepared:</p>";
                echo "<pre>" . print_r($contactData, true) . "</pre>";
                
                try {
                    Database::insert('contact_logs', $contactData);
                    $lastId = Database::getConnection()->lastInsertId();
                    echo "<p>✅ Database insert successful! New ID: {$lastId}</p>";
                    
                    // Test email sending logic
                    echo "<p>🔄 Testing email sending...</p>";
                    
                    // Get post owner (using post ID 10)
                    $postOwner = User::findById(3); // Use a known user ID for testing
                    
                    if ($postOwner) {
                        echo "<p>✅ Post owner found: " . htmlspecialchars($postOwner['full_name'] ?? 'Unknown') . "</p>";
                        echo "<p>📧 Owner email: " . htmlspecialchars($postOwner['email'] ?? 'No email') . "</p>";
                        
                        if (!empty($postOwner['email'])) {
                            // Test Email class loading
                            if (!class_exists('Email')) {
                                require_once __DIR__ . '/includes/Email.php';
                                echo "<p>✅ Email class loaded</p>";
                            } else {
                                echo "<p>✅ Email class already available</p>";
                            }
                            
                            $subject = "SafeKeep: Test Contact Message";
                            $body = "Test email from debug script";
                            
                            echo "<p>🔄 Attempting to send email...</p>";
                            
                            $emailSent = Email::send($postOwner['email'], $subject, $body);
                            
                            if ($emailSent) {
                                echo "<p>✅ Email sent successfully!</p>";
                                // Update database
                                Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$lastId]);
                                echo "<p>✅ Database updated with email status</p>";
                            } else {
                                echo "<p>❌ Email sending failed</p>";
                                Database::execute("UPDATE contact_logs SET email_sent = 0, email_error = 'Email send failed' WHERE id = ?", [$lastId]);
                            }
                        } else {
                            echo "<p>❌ Post owner has no email address</p>";
                        }
                    } else {
                        echo "<p>❌ Post owner not found</p>";
                    }
                    
                } catch (Exception $e) {
                    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    } else {
        echo "<p>❌ contact_owner field missing from POST data</p>";
    }
    
} else {
    echo "<p>This page will debug form submissions. Submit a form from posts/view.php and then visit this page.</p>";
    echo "<p>Or use the test form below:</p>";
    
    echo '<form method="POST" style="border: 1px solid #ccc; padding: 20px; max-width: 500px;">';
    echo '<h4>Test Contact Form (Same as posts/view.php)</h4>';
    echo '<input type="hidden" name="csrf_token" value="' . Security::generateCSRFToken() . '">';
    echo '<div>';
    echo '<label for="message">Message:</label><br>';
    echo '<textarea name="message" id="message" rows="4" cols="50" placeholder="Test message..." required></textarea>';
    echo '</div><br>';
    echo '<button type="submit" name="contact_owner">Send Test Message</button>';
    echo '</form>';
}

echo "<hr>";
echo "<p><a href='posts/view.php?id=10'>🔗 Go to Real Post #10</a></p>";
echo "<p><a href='test-real-contact.php'>🔗 Go to Working Test Form</a></p>";
?>
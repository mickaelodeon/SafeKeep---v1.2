<?php
/**
 * Real Contact Form Test - Matches actual posts/view.php structure
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';

Session::init();

echo "<h2>🔍 Real Contact Form Test (Matching posts/view.php)</h2>";

// Check if user is logged in
$currentUser = Session::getUser();

if (!$currentUser) {
    echo "<p>❌ <strong>ERROR:</strong> User not logged in!</p>";
    echo "<p><a href='auth/login.php'>Please log in first</a></p>";
    exit;
}

echo "<p>✅ User logged in: <strong>" . htmlspecialchars($currentUser['full_name']) . "</strong> (ID: {$currentUser['id']})</p>";

// Get a test post (not owned by current user)
$stmt = Database::getConnection()->prepare("
    SELECT p.*, u.full_name as author_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status = 'approved' AND p.user_id != ? 
    ORDER BY p.created_at DESC 
    LIMIT 1
");
$stmt->execute([$currentUser['id']]);
$testPost = $stmt->fetch();

if (!$testPost) {
    echo "<p>❌ No posts found to test with (need a post not owned by you)</p>";
    exit;
}

$postId = $testPost['id'];

// Process form submission (same logic as posts/view.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_owner'])) {
    echo "<h3>📥 Processing Contact Form Submission</h3>";
    
    echo "<p><strong>POST Data:</strong></p>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Verify CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo "<p>❌ <strong>ERROR:</strong> Invalid CSRF token</p>";
    } elseif (!$currentUser) {
        echo "<p>❌ <strong>ERROR:</strong> User not logged in</p>";
    } else {
        $message = trim($_POST['message'] ?? '');
        
        echo "<p>📝 Message: " . htmlspecialchars($message) . "</p>";
        echo "<p>📏 Message length: " . strlen($message) . " characters</p>";
        
        if (empty($message)) {
            echo "<p>❌ <strong>ERROR:</strong> Empty message</p>";
        } elseif (strlen($message) < 10) {
            echo "<p>❌ <strong>ERROR:</strong> Message too short (minimum 10 characters)</p>";
        } else {
            echo "<p>✅ Message validation passed</p>";
            
            try {
                echo "<p>🔄 Attempting to insert into database...</p>";
                
                // Store the contact attempt in database (exact same as posts/view.php)
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
                
                echo "<p><strong>Contact Data to Insert:</strong></p>";
                echo "<pre>" . print_r($contactData, true) . "</pre>";
                
                $insertResult = Database::insert('contact_logs', $contactData);
                
                if ($insertResult) {
                    $lastId = Database::getConnection()->lastInsertId();
                    echo "<p>✅ <strong>SUCCESS!</strong> Contact logged with ID: {$lastId}</p>";
                    
                    // Try to update email_sent status
                    Database::execute(
                        "UPDATE contact_logs SET email_sent = 1 WHERE post_id = ? AND sender_user_id = ? ORDER BY sent_at DESC LIMIT 1", 
                        [$postId, $currentUser['id']]
                    );
                    
                    echo "<p>✅ Email status updated</p>";
                    echo "<p>🎉 <strong>Contact form processing completed successfully!</strong></p>";
                    
                } else {
                    echo "<p>❌ <strong>ERROR:</strong> Database insert returned false</p>";
                }
                
            } catch (Exception $e) {
                echo "<p>❌ <strong>EXCEPTION:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p>📍 File: " . $e->getFile() . "</p>";
                echo "<p>📍 Line: " . $e->getLine() . "</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<p><a href='test-real-contact.php'>🔄 Test Again</a></p>";
    
} else {
    // Show the test form (exact structure as posts/view.php)
    echo "<h3>🧪 Test Contact Form</h3>";
    echo "<p>Testing with post: <strong>#{$testPost['id']}: {$testPost['title']}</strong></p>";
    echo "<p>Post owner: <strong>{$testPost['author_name']}</strong></p>";
    
    $csrfToken = Security::generateCSRFToken();
    
    echo '<div style="max-width: 500px; border: 2px solid #007bff; padding: 20px; margin: 20px 0;">';
    echo '<h4>Contact Owner Form</h4>';
    echo '<form method="POST">';
    echo '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
    echo '<div class="mb-3">';
    echo '<label for="message">Your Message</label><br>';
    echo '<textarea name="message" id="message" rows="5" cols="50" ';
    echo 'placeholder="Hi! I believe this is my item. Here are the details that prove it\'s mine..." ';
    echo 'required maxlength="1000"></textarea>';
    echo '<div><small>Be specific: describe unique features, where you lost it, or provide serial numbers.</small></div>';
    echo '</div>';
    echo '<button type="submit" name="contact_owner">Send Message to Owner</button>';
    echo '</form>';
    echo '</div>';
}

// Show recent logs
echo "<hr>";
echo "<h3>📝 Recent Contact Logs</h3>";

try {
    $logStmt = Database::getConnection()->prepare("
        SELECT cl.*, u.full_name as sender_name, p.title as post_title
        FROM contact_logs cl
        LEFT JOIN users u ON cl.sender_user_id = u.id
        LEFT JOIN posts p ON cl.post_id = p.id
        ORDER BY cl.sent_at DESC 
        LIMIT 5
    ");
    $logStmt->execute();
    $recentLogs = $logStmt->fetchAll();
    
    if ($recentLogs) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Post</th><th>Sender</th><th>Message</th><th>Email Sent</th><th>Time</th></tr>";
        foreach ($recentLogs as $log) {
            $message = strlen($log['message']) > 50 ? substr($log['message'], 0, 50) . '...' : $log['message'];
            $emailStatus = $log['email_sent'] ? '✅ Yes' : '❌ No';
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>#{$log['post_id']}: " . htmlspecialchars($log['post_title']) . "</td>";
            echo "<td>" . htmlspecialchars($log['sender_name']) . "</td>";
            echo "<td>" . htmlspecialchars($message) . "</td>";
            echo "<td>{$emailStatus}</td>";
            echo "<td>{$log['sent_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No contact logs found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error getting logs: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='debug-contact-form.php'>🔄 Back to Debug Page</a></p>";
echo "<p><a href='contact-monitor.php'>📊 Open Real-Time Monitor</a></p>";
?>
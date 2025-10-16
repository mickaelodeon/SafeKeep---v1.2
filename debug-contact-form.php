<?php
/**
 * Debug Contact Form Submission
 * This will help us see exactly what happens when the form is submitted
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';

Session::init();

// Check if user is logged in
$currentUser = Session::getUser();
echo "<h2>🔍 Contact Form Debug Information</h2>";

if (!$currentUser) {
    echo "<p>❌ <strong>ERROR:</strong> User not logged in!</p>";
    echo "<p>Please log in first to test the contact form.</p>";
    exit;
}

echo "<p>✅ User logged in: <strong>" . htmlspecialchars($currentUser['full_name']) . "</strong></p>";
echo "<p>📧 User email: <strong>" . htmlspecialchars($currentUser['email']) . "</strong></p>";
echo "<p>🆔 User ID: <strong>" . $currentUser['id'] . "</strong></p>";

// Get recent posts to test with
$stmt = Database::getConnection()->prepare("
    SELECT p.*, u.full_name as author_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status = 'approved' 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recentPosts = $stmt->fetchAll();

echo "<h3>📋 Available Posts for Testing</h3>";
if ($recentPosts) {
    foreach ($recentPosts as $post) {
        $isOwner = $currentUser['id'] == $post['user_id'];
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
        echo "<p><strong>Post #{$post['id']}: {$post['title']}</strong></p>";
        echo "<p>Owner: {$post['author_name']} " . ($isOwner ? "<em>(This is you!)</em>" : "") . "</p>";
        echo "<p>Type: {$post['type']} | Category: {$post['category']}</p>";
        echo "<p><a href='posts/view.php?id={$post['id']}' target='_blank'>View Post & Test Contact Form</a></p>";
        echo "</div>";
    }
} else {
    echo "<p>❌ No posts found!</p>";
}

// Check database connection and table structure
echo "<h3>🔧 Database Status</h3>";
try {
    $conn = Database::getConnection();
    echo "<p>✅ Database connection: OK</p>";
    
    // Check if contact_logs table exists and its structure
    $stmt = $conn->query("DESCRIBE contact_logs");
    $columns = $stmt->fetchAll();
    
    echo "<p>✅ contact_logs table structure:</p>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>{$column['Field']}</strong>: {$column['Type']}</li>";
    }
    echo "</ul>";
    
    // Get recent contact logs
    $logStmt = Database::getConnection()->prepare("
        SELECT cl.*, u.full_name as sender_name, p.title as post_title
        FROM contact_logs cl
        LEFT JOIN users u ON cl.sender_user_id = u.id
        LEFT JOIN posts p ON cl.post_id = p.id
        ORDER BY cl.sent_at DESC 
        LIMIT 10
    ");
    $logStmt->execute();
    $recentLogs = $logStmt->fetchAll();
    
    echo "<h3>📝 Recent Contact Logs (Last 10)</h3>";
    if ($recentLogs) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Post</th><th>Sender</th><th>Message</th><th>Email Sent</th><th>Time</th></tr>";
        foreach ($recentLogs as $log) {
            $message = strlen($log['message']) > 50 ? substr($log['message'], 0, 50) . '...' : $log['message'];
            $emailStatus = $log['email_sent'] ? '✅ Yes' : '❌ No';
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>#{$log['post_id']}: {$log['post_title']}</td>";
            echo "<td>{$log['sender_name']}</td>";
            echo "<td>" . htmlspecialchars($message) . "</td>";
            echo "<td>{$emailStatus}</td>";
            echo "<td>{$log['sent_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No contact logs found in database!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Check email configuration
echo "<h3>📧 Email Configuration</h3>";
try {
    $emailConfig = [
        'SMTP_HOST' => Config::get('mail.host'),
        'SMTP_PORT' => Config::get('mail.port'),
        'SMTP_USERNAME' => Config::get('mail.username'),
        'FROM_EMAIL' => Config::get('mail.from_email'),
        'FROM_NAME' => Config::get('mail.from_name'),
        'MAIL_ENABLED' => Config::get('mail.enabled')
    ];
    
    foreach ($emailConfig as $key => $value) {
        echo "<p><strong>{$key}:</strong> " . ($value ? htmlspecialchars($value) : '<em>Not set</em>') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Email config error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🧪 Testing Instructions</h3>";
echo "<ol>";
echo "<li>Pick a post from the list above that you don't own</li>";
echo "<li>Click 'View Post & Test Contact Form' to open it in a new tab</li>";
echo "<li>Fill out the contact form with a test message (at least 10 characters)</li>";
echo "<li>Click 'Send Message to Owner'</li>";
echo "<li>Come back to this page and refresh to see if a new log appears</li>";
echo "<li>Also refresh the monitoring page to see if the contact appears there</li>";
echo "</ol>";

echo "<p><strong>Expected Results:</strong></p>";
echo "<ul>";
echo "<li>A new entry should appear in the contact logs table above</li>";
echo "<li>The email should be sent (Email Sent column should show ✅ Yes)</li>";
echo "<li>You should see a success message on the post page</li>";
echo "</ul>";

echo "<p><a href='contact-monitor.php' target='_blank'>🔄 Open Real-Time Monitor</a></p>";
?>
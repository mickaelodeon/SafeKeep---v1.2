<?php
/**
 * Check Email Delivery Status
 * Let's see who owns what posts and where emails are going
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

Session::init();

echo "<h2>📧 Email Delivery Investigation</h2>";

// Get current user
$currentUser = Session::getUser();
if ($currentUser) {
    echo "<p><strong>Current User:</strong> " . htmlspecialchars($currentUser['full_name']) . " (" . htmlspecialchars($currentUser['email']) . ")</p>";
} else {
    echo "<p>❌ No user logged in</p>";
    exit;
}

// Check Post #10 details
echo "<h3>📋 Post #10 Information</h3>";
try {
    $post = Database::selectOne("
        SELECT p.*, u.full_name as owner_name, u.email as owner_email 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = 10
    ");
    
    if ($post) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Post ID</td><td>{$post['id']}</td></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($post['title']) . "</td></tr>";
        echo "<tr><td>Owner Name</td><td>" . htmlspecialchars($post['owner_name']) . "</td></tr>";
        echo "<tr><td>Owner Email</td><td>" . htmlspecialchars($post['owner_email']) . "</td></tr>";
        echo "<tr><td>Post Type</td><td>" . htmlspecialchars($post['type']) . "</td></tr>";
        echo "<tr><td>Status</td><td>" . htmlspecialchars($post['status']) . "</td></tr>";
        echo "</table>";
        
        if ($post['owner_email'] === $currentUser['email']) {
            echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffeaa7;'>";
            echo "<strong>⚠️ ISSUE FOUND:</strong> You are trying to contact yourself!<br>";
            echo "You own this post, so when you submit the contact form, the email gets sent to YOUR email address.<br>";
            echo "This is why you're not seeing contact attempts - you're the owner, not a contact seeker.";
            echo "</div>";
        } else {
            echo "<div style='background: #d1ecf1; padding: 10px; margin: 10px 0; border: 1px solid #bee5eb;'>";
            echo "<strong>✅ CORRECT SETUP:</strong> You are contacting a different user.<br>";
            echo "When you submit the contact form, the email will be sent to: " . htmlspecialchars($post['owner_email']);
            echo "</div>";
        }
    } else {
        echo "<p>❌ Post #10 not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check recent contact logs for this user
echo "<h3>📝 Your Recent Contact Attempts</h3>";
try {
    $stmt = Database::getConnection()->prepare("
        SELECT cl.*, p.title as post_title, u.full_name as post_owner
        FROM contact_logs cl
        LEFT JOIN posts p ON cl.post_id = p.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE cl.sender_user_id = ?
        ORDER BY cl.sent_at DESC
        LIMIT 10
    ");
    $stmt->execute([$currentUser['id']]);
    $contacts = $stmt->fetchAll();
    
    if ($contacts) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Post</th><th>Owner</th><th>Message</th><th>Email Sent</th><th>Time</th></tr>";
        foreach ($contacts as $contact) {
            $emailStatus = $contact['email_sent'] ? '✅ Yes' : '❌ No';
            $message = strlen($contact['message']) > 30 ? substr($contact['message'], 0, 30) . '...' : $contact['message'];
            echo "<tr>";
            echo "<td>{$contact['id']}</td>";
            echo "<td>#{$contact['post_id']}: " . htmlspecialchars($contact['post_title']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['post_owner']) . "</td>";
            echo "<td>" . htmlspecialchars($message) . "</td>";
            echo "<td>{$emailStatus}</td>";
            echo "<td>{$contact['sent_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No contact attempts found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error getting contact logs: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Show available posts you can test with (that you DON'T own)
echo "<h3>🎯 Posts You Can Test Contact Form With</h3>";
try {
    $stmt = Database::getConnection()->prepare("
        SELECT p.*, u.full_name as owner_name, u.email as owner_email
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'approved' AND p.user_id != ?
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$currentUser['id']]);
    $availablePosts = $stmt->fetchAll();
    
    if ($availablePosts) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Post ID</th><th>Title</th><th>Owner</th><th>Owner Email</th><th>Action</th></tr>";
        foreach ($availablePosts as $post) {
            echo "<tr>";
            echo "<td>#{$post['id']}</td>";
            echo "<td>" . htmlspecialchars($post['title']) . "</td>";
            echo "<td>" . htmlspecialchars($post['owner_name']) . "</td>";
            echo "<td>" . htmlspecialchars($post['owner_email']) . "</td>";
            echo "<td><a href='posts/view.php?id={$post['id']}' target='_blank'>Test Contact Form</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border: 1px solid #c3e6cb;'>";
        echo "<strong>💡 TIP:</strong> Use these posts to test the contact form. When you submit a contact form on these posts, ";
        echo "the email will be sent to the respective owner's email address (shown above).";
        echo "</div>";
        
    } else {
        echo "<p>No posts available for testing (you might own all the posts)</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error getting available posts: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>🔧 Email System Status</h3>";

// Test email configuration
try {
    $emailConfig = [
        'Host' => Config::get('mail.host'),
        'Port' => Config::get('mail.port'),
        'Username' => Config::get('mail.username'),
        'From Email' => Config::get('mail.from_email'),
        'Enabled' => Config::get('mail.enabled') ? 'Yes' : 'No'
    ];
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    foreach ($emailConfig as $key => $value) {
        $status = !empty($value) ? '✅' : '❌';
        echo "<tr><td>{$key}</td><td>{$value}</td><td>{$status}</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p>❌ Config error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
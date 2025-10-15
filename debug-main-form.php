<?php
// Debug main form submission
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Main Form Submission</h2>";

// Check if we're being redirected from posts/view.php
if (isset($_GET['post_id'])) {
    $postId = $_GET['post_id'];
    echo "<p><strong>Redirected from post ID:</strong> $postId</p>";
    
    // Check for success/error parameters
    if (isset($_GET['contact'])) {
        echo "<p><strong>Contact Status:</strong> " . $_GET['contact'] . "</p>";
    }
    if (isset($_GET['email'])) {
        echo "<p><strong>Email Status:</strong> " . $_GET['email'] . "</p>";
    }
} else {
    echo "<p>No redirect parameters found.</p>";
}

// Check contact logs for recent submissions
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h3>Recent Contact Logs</h3>";
try {
    $logs = Database::select("SELECT * FROM contact_logs ORDER BY sent_at DESC LIMIT 5");
    if ($logs) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Post ID</th><th>Sender</th><th>Message</th><th>Email Sent</th><th>Sent At</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>" . $log['id'] . "</td>";
            echo "<td>" . $log['post_id'] . "</td>";
            echo "<td>" . $log['sender_name'] . "</td>";
            echo "<td>" . substr($log['message'], 0, 50) . "...</td>";
            echo "<td>" . ($log['email_sent'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $log['sent_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No contact logs found.</p>";
    }
} catch (Exception $e) {
    echo "<p>Error retrieving logs: " . $e->getMessage() . "</p>";
}

echo "<h3>Test Form Submission to Real Post</h3>";
// Instead of testing via form submission, let's just provide a link to the actual post
echo '<p><strong>To test the contact form properly:</strong></p>';
echo '<p><a href="posts/view.php?id=10" target="_blank" style="background:#007bff; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;">Go to Post ID 10 and use the actual contact form</a></p>';
echo '<p><em>This ensures the CSRF token is generated in the correct context.</em></p>';

// Also show available posts
echo "<h3>Available Posts</h3>";
try {
    $posts = Database::select("SELECT id, title, type FROM posts WHERE is_resolved = 0 ORDER BY id DESC LIMIT 10");
    if ($posts) {
        echo "<p>Available post IDs for testing:</p>";
        echo "<ul>";
        foreach ($posts as $post) {
            echo "<li>Post ID " . $post['id'] . ": " . htmlspecialchars($post['title']) . " (" . $post['type'] . ")</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p>Error retrieving posts: " . $e->getMessage() . "</p>";
}

?>
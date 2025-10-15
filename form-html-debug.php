<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

Session::init();

$currentUser = Session::getUser();

// Get post details for ID 9 (from your recent tests)
$postId = 9;
$post = Database::selectOne("
    SELECT p.*, u.full_name as author_name, u.email as author_email, u.id as author_id
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ? AND p.status = 'approved'
", [$postId]);

$isOwner = $currentUser && $currentUser['id'] == $post['author_id'];

echo "<h2>Form HTML Debug for Post ID $postId</h2>";

echo "<h3>User Status</h3>";
echo "<p><strong>Current User:</strong> " . ($currentUser ? $currentUser['full_name'] . " (ID: " . $currentUser['id'] . ")" : "Not logged in") . "</p>";
echo "<p><strong>Post Author ID:</strong> " . $post['author_id'] . "</p>";
echo "<p><strong>Is Owner:</strong> " . ($isOwner ? "Yes" : "No") . "</p>";
echo "<p><strong>Post Resolved:</strong> " . ($post['is_resolved'] ? "Yes" : "No") . "</p>";

echo "<h3>Form Condition Check</h3>";
$showForm = $currentUser && !$isOwner && !$post['is_resolved'];
echo "<p><strong>Should show form:</strong> " . ($showForm ? "Yes" : "No") . "</p>";

if ($showForm) {
    echo "<h3>Generated Form HTML</h3>";
    echo '<div style="border: 2px solid blue; padding: 10px; background: #f0f8ff;">';
    echo '<form method="POST" style="border: 1px solid red; padding: 10px;">';
    echo '<input type="hidden" name="csrf_token" value="' . Security::generateCSRFToken() . '">';
    echo '<div class="mb-3">';
    echo '<label for="message" class="form-label">Your Message</label>';
    echo '<textarea name="message" id="message" class="form-control" rows="4" placeholder="Hi, I think this might be my item..." required></textarea>';
    echo '<div class="form-text">Be specific about why you think this is your item.</div>';
    echo '</div>';
    echo '<button type="submit" name="contact_owner" class="btn btn-primary" style="background: red; color: white; font-weight: bold;">';
    echo '<i class="fas fa-paper-plane me-1"></i>Send Message';
    echo '</button>';
    echo '</form>';
    echo '</div>';
} else {
    echo "<p style='color: red;'>Form will not be shown due to conditions.</p>";
}

echo "<h3>Test POST Submission</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: yellow; padding: 10px;'>";
    echo "<strong>POST Data Received:</strong><br>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['contact_owner'])) {
        echo "<p style='color: green; font-weight: bold;'>✓ contact_owner field found!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ contact_owner field NOT found in POST data!</p>";
    }
    echo "</div>";
}
?>
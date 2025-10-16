<?php
/**
 * Contact Form Live Debug - Check what happens during actual form submission
 */

session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

echo "<h1>Contact Form Live Debug</h1>";

try {
    Config::load();
    
    echo "<h2>1. Check Current Session</h2>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
    
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        echo "✅ User is logged in with ID: " . $_SESSION['user_id'] . "<br>";
        
        // Get user details
        $stmt = Database::execute("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentUser) {
            echo "✅ User found: " . htmlspecialchars($currentUser['full_name']) . " (" . htmlspecialchars($currentUser['email']) . ")<br>";
        } else {
            echo "❌ User not found in database<br>";
        }
    } else {
        echo "❌ User is not logged in<br>";
    }
    
    echo "<h2>2. Check POST Data</h2>";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "✅ POST request detected<br>";
        echo "POST data: <pre>" . print_r($_POST, true) . "</pre>";
        
        if (isset($_POST['contact_owner'])) {
            echo "✅ contact_owner form submitted<br>";
            
            // Check CSRF token
            if (isset($_POST['csrf_token'])) {
                echo "CSRF token provided: " . htmlspecialchars($_POST['csrf_token']) . "<br>";
            } else {
                echo "❌ No CSRF token provided<br>";
            }
            
            // Check message
            if (isset($_POST['message'])) {
                echo "Message: " . htmlspecialchars($_POST['message']) . "<br>";
                echo "Message length: " . strlen($_POST['message']) . " characters<br>";
            } else {
                echo "❌ No message provided<br>";
            }
        } else {
            echo "❌ contact_owner not in POST data<br>";
        }
    } else {
        echo "No POST request - this is a GET request<br>";
    }
    
    echo "<h2>3. Test Page Access</h2>";
    
    // Test accessing a specific post
    $postId = isset($_GET['id']) ? (int)$_GET['id'] : 6;
    echo "Testing post ID: $postId<br>";
    
    $stmt = Database::execute("SELECT p.*, u.full_name as author_name, u.email as author_email, u.id as author_id FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.status = 'approved'", [$postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        echo "✅ Post found: " . htmlspecialchars($post['title']) . "<br>";
        echo "Post owner: " . htmlspecialchars($post['author_name']) . " (" . htmlspecialchars($post['author_email']) . ")<br>";
    } else {
        echo "❌ Post not found or not approved<br>";
    }
    
    echo "<h2>4. Quick Form Test</h2>";
    
    if (isset($_SESSION['user_id']) && $post) {
        echo '<form method="POST" style="border: 2px solid #007bff; padding: 20px; background: #f8f9fa;">';
        echo '<h3>Test Contact Form</h3>';
        echo '<input type="hidden" name="csrf_token" value="test-token">';
        echo '<textarea name="message" placeholder="Test message..." required style="width: 100%; height: 100px;"></textarea><br><br>';
        echo '<button type="submit" name="contact_owner" style="background: #007bff; color: white; padding: 10px 20px; border: none;">Send Test Message</button>';
        echo '</form>';
    } else {
        echo "Cannot show form - user not logged in or post not found<br>";
    }
    
    echo "<h2>5. Configuration Check</h2>";
    echo "Email enabled: " . (Config::get('mail.enabled') ? 'Yes' : 'No') . "<br>";
    echo "Email host: " . Config::get('mail.host') . "<br>";
    echo "From email: " . Config::get('mail.from_email') . "<br>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine();
    echo "</div>";
}
?>
<?php
require_once './includes/db.php';

echo "<h2>Available Posts for Testing</h2>";

try {
    $pdo = Database::getConnection();
    
    // Show all posts with their status
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as owner_name 
        FROM posts p 
        LEFT JOIN users u ON p.user_id = u.id 
        ORDER BY p.id DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
    if ($posts) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Owner</th><th>Status</th><th>Resolved</th><th>Test Contact</th></tr>";
        
        foreach ($posts as $post) {
            $resolved = $post['is_resolved'] ? 'Yes' : 'No';
            $canContact = !$post['is_resolved'] && $post['status'] == 'approved';
            
            echo "<tr>";
            echo "<td>{$post['id']}</td>";
            echo "<td>" . htmlspecialchars($post['title']) . "</td>";
            echo "<td>{$post['type']}</td>";
            echo "<td>" . htmlspecialchars($post['owner_name']) . "</td>";
            echo "<td>{$post['status']}</td>";
            echo "<td>$resolved</td>";
            
            if ($canContact) {
                echo "<td><a href='posts/view.php?id={$post['id']}' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>View & Contact</a></td>";
            } else {
                echo "<td><span style='color: #666;'>Cannot contact (resolved/not approved)</span></td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Create a new test post if post 9 doesn't exist or is resolved
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = 9");
        $stmt->execute();
        $post9 = $stmt->fetch();
        
        if (!$post9 || $post9['is_resolved']) {
            echo "<h3>Creating New Test Post (ID will be next available):</h3>";
            
            // Find a category ID that exists
            $stmt = $pdo->prepare("SELECT id, name FROM categories LIMIT 1");
            $stmt->execute();
            $category = $stmt->fetch();
            $categoryId = $category ? $category['id'] : 1;
            
            // Create a new test post
            $stmt = $pdo->prepare("
                INSERT INTO posts (user_id, title, description, type, category, date_lost_found, location, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')
            ");
            
            $result = $stmt->execute([
                2, // Different user so you can contact them
                'Test iPhone Lost in Library',
                'Lost my iPhone 13 Pro in the main library on the second floor. It has a black case with a ring holder. Very important as it contains my thesis work!',
                'lost',
                'Electronics',
                date('Y-m-d'),
                'Main Library - 2nd Floor'
            ]);
            
            if ($result) {
                $newPostId = $pdo->lastInsertId();
                echo "<p style='color: green;'>✅ Created new test post with ID: $newPostId</p>";
                echo "<p><a href='posts/view.php?id=$newPostId' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🎯 Test Contact Form on New Post</a></p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create test post</p>";
            }
        }
        
    } else {
        echo "<p>No posts found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>

<p><a href="/safekeep-v2/">← Back to SafeKeep</a></p>
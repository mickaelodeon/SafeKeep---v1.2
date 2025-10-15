<?php
/**
 * Debug New User Login Issue
 * Check recently created users and their login credentials
 */

require_once 'includes/db.php';

try {
    echo "<h2>🔍 Debugging New User Login Issue</h2>";
    
    // Get all users sorted by creation date (newest first)
    $stmt = Database::getConnection()->prepare("
        SELECT id, full_name, email, password_hash, is_active, role, created_at, updated_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h3>📋 Recent Users (Last 10 created):</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-family: monospace;'>";
    echo "<tr style='background: #f0f0f0;'>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Password Hash (first 20 chars)</th>
            <th>Active?</th>
            <th>Role</th>
            <th>Created At</th>
            <th>Updated At</th>
          </tr>";
    
    foreach ($users as $user) {
        $activeColor = $user['is_active'] ? 'green' : 'red';
        $activeText = $user['is_active'] ? '✅ YES' : '❌ NO';
        
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>" . substr($user['password_hash'], 0, 20) . "...</td>";
        echo "<td style='color: {$activeColor}; font-weight: bold;'>{$activeText}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "<td>" . ($user['updated_at'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get the most recent user (likely the one just created)
    $newestUser = $users[0];
    
    echo "<h3>🎯 Most Recent User Details:</h3>";
    echo "<div style='background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 10px 0;'>";
    echo "<strong>Full Name:</strong> {$newestUser['full_name']}<br>";
    echo "<strong>Email:</strong> {$newestUser['email']}<br>";
    echo "<strong>Is Active:</strong> " . ($newestUser['is_active'] ? '✅ YES' : '❌ NO') . "<br>";
    echo "<strong>Role:</strong> {$newestUser['role']}<br>";
    echo "<strong>Password Hash Length:</strong> " . strlen($newestUser['password_hash']) . " characters<br>";
    echo "<strong>Password Hash Type:</strong> " . (strpos($newestUser['password_hash'], '$2y$') === 0 ? '✅ Valid bcrypt' : '❌ Not bcrypt') . "<br>";
    echo "<strong>Created:</strong> {$newestUser['created_at']}<br>";
    echo "<strong>Last Updated:</strong> " . ($newestUser['updated_at'] ?? 'Never updated') . "<br>";
    echo "</div>";
    
    // Test password verification for the newest user
    echo "<h3>🔐 Password Hash Testing:</h3>";
    echo "<p>To test password verification, we need to know what password was used during registration.</p>";
    echo "<p><strong>Note:</strong> For security, we cannot see the actual password, only test if a given password matches the hash.</p>";
    
    // Check if there are any patterns in password hashing
    echo "<h3>🧪 Password Hash Analysis:</h3>";
    $bcryptUsers = 0;
    $otherUsers = 0;
    
    foreach ($users as $user) {
        if (strpos($user['password_hash'], '$2y$') === 0) {
            $bcryptUsers++;
        } else {
            $otherUsers++;
            echo "<div style='color: red; background: #ffe6e6; padding: 5px; margin: 2px 0;'>";
            echo "⚠️ User {$user['email']} has non-bcrypt hash: " . substr($user['password_hash'], 0, 30) . "...";
            echo "</div>";
        }
    }
    
    echo "<div style='background: #e6f3ff; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Hash Type Summary:</strong><br>";
    echo "✅ Bcrypt hashes: {$bcryptUsers}<br>";
    echo "❌ Other/Invalid hashes: {$otherUsers}";
    echo "</div>";
    
    // Check login attempt simulation
    echo "<h3>🔍 Login Process Check:</h3>";
    echo "<p>The login issue might be caused by:</p>";
    echo "<ul>";
    echo "<li>❓ Password not properly hashed during registration</li>";
    echo "<li>❓ Case sensitivity in email address</li>";
    echo "<li>❓ Account not properly activated</li>";
    echo "<li>❓ Login logic not checking the right fields</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; margin: 10px 0;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f0f0f0; }
</style>
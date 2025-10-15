<?php
/**
 * Check Email Verification Status
 */

require_once 'includes/db.php';

try {
    echo "<h2>📧 Email Verification Status Check</h2>";
    
    // Check the email_verified column for all users
    $stmt = Database::getConnection()->prepare("
        SELECT id, full_name, email, is_active, email_verified, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-family: monospace;'>";
    echo "<tr style='background: #f0f0f0;'>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Active?</th>
            <th>Email Verified?</th>
            <th>Created At</th>
          </tr>";
    
    foreach ($users as $user) {
        $activeColor = $user['is_active'] ? 'green' : 'red';
        $activeText = $user['is_active'] ? '✅ YES' : '❌ NO';
        
        $verifiedColor = $user['email_verified'] ? 'green' : 'red';
        $verifiedText = $user['email_verified'] ? '✅ YES' : '❌ NO';
        
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td style='color: {$activeColor}; font-weight: bold;'>{$activeText}</td>";
        echo "<td style='color: {$verifiedColor}; font-weight: bold;'>{$verifiedText}</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🔍 Fix Needed:</h3>";
    echo "<p>If your new account shows 'Email Verified? ❌ NO', that's the problem!</p>";
    echo "<p>The login system requires BOTH conditions:</p>";
    echo "<ul>";
    echo "<li>✅ is_active = 1 (account activated by admin)</li>";
    echo "<li>❌ email_verified = 1 (email verification - missing!)</li>";
    echo "</ul>";
    
    // Check if any users need email verification fix
    $stmt = Database::getConnection()->prepare("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE is_active = 1 AND email_verified = 0
    ");
    $stmt->execute();
    $needsFix = $stmt->fetch();
    
    if ($needsFix['count'] > 0) {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 10px 0;'>";
        echo "⚠️ <strong>{$needsFix['count']} active account(s) need email verification fix!</strong>";
        echo "</div>";
        
        echo "<h3>🛠️ Quick Fix:</h3>";
        echo "<p>I can create a script to fix this automatically. The accounts are active but missing email verification.</p>";
    }
    
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
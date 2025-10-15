<?php
/**
 * Login Logic Tester
 * Test login functionality with detailed debugging
 */

session_start();
require_once 'includes/db.php';

echo "<h2>🔐 Login Logic Debugging Tool</h2>";

// Check if this is a test login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    echo "<h3>🧪 Testing Login for: " . htmlspecialchars($email) . "</h3>";
    
    try {
        // Step 1: Check if user exists
        $stmt = Database::getConnection()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo "<div style='color: red; background: #ffe6e6; padding: 10px;'>";
            echo "❌ <strong>Step 1 FAILED:</strong> No user found with email: " . htmlspecialchars($email);
            echo "</div>";
            
            // Check for similar emails (case insensitive)
            $stmt = Database::getConnection()->prepare("SELECT email FROM users WHERE LOWER(email) = LOWER(?)");
            $stmt->execute([$email]);
            $similarUser = $stmt->fetch();
            
            if ($similarUser) {
                echo "<div style='color: orange; background: #fff3cd; padding: 10px;'>";
                echo "⚠️ <strong>Found similar email:</strong> {$similarUser['email']} (check case sensitivity)";
                echo "</div>";
            }
        } else {
            echo "<div style='color: green; background: #d4edda; padding: 10px;'>";
            echo "✅ <strong>Step 1 PASSED:</strong> User found - ID: {$user['id']}, Name: {$user['full_name']}";
            echo "</div>";
            
            // Step 2: Check if account is active
            if (!$user['is_active']) {
                echo "<div style='color: red; background: #ffe6e6; padding: 10px;'>";
                echo "❌ <strong>Step 2 FAILED:</strong> Account is not active (is_active = 0)";
                echo "</div>";
            } else {
                echo "<div style='color: green; background: #d4edda; padding: 10px;'>";
                echo "✅ <strong>Step 2 PASSED:</strong> Account is active";
                echo "</div>";
                
                // Step 3: Test password verification
                echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0;'>";
                echo "<strong>Password Hash Info:</strong><br>";
                echo "Hash: " . substr($user['password_hash'], 0, 30) . "...<br>";
                echo "Length: " . strlen($user['password_hash']) . " characters<br>";
                echo "Type: " . (strpos($user['password_hash'], '$2y$') === 0 ? 'bcrypt' : 'unknown') . "<br>";
                echo "</div>";
                
                if (password_verify($password, $user['password_hash'])) {
                    echo "<div style='color: green; background: #d4edda; padding: 10px;'>";
                    echo "✅ <strong>Step 3 PASSED:</strong> Password verification successful!";
                    echo "</div>";
                    
                    echo "<div style='color: blue; background: #cce7ff; padding: 10px;'>";
                    echo "🎉 <strong>LOGIN WOULD SUCCEED:</strong> All checks passed!";
                    echo "</div>";
                } else {
                    echo "<div style='color: red; background: #ffe6e6; padding: 10px;'>";
                    echo "❌ <strong>Step 3 FAILED:</strong> Password verification failed";
                    echo "</div>";
                    
                    // Test if it's a plain text password (security issue)
                    if ($password === $user['password_hash']) {
                        echo "<div style='color: orange; background: #fff3cd; padding: 10px;'>";
                        echo "⚠️ <strong>SECURITY ISSUE:</strong> Password appears to be stored in plain text!";
                        echo "</div>";
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "<div style='color: red; background: #ffe6e6; padding: 10px;'>";
        echo "❌ <strong>Database Error:</strong> " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SafeKeep - Login Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h3>🧪 Test Login Credentials</h3>
        <p>Enter the email and password of the newly created account to test the login process:</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" name="test_login">🔍 Test Login</button>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <p><a href="debug-new-user-login.php">📋 View User Database</a></p>
        <p><a href="/safekeep-v2/">🏠 Back to SafeKeep</a></p>
    </div>
</body>
</html>
<?php
/**
 * Admin Account Debug and Reset Tool
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Admin Account Debug Tool</h2>";

try {
    $config = Config::get('database');
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h3>Checking existing admin accounts...</h3>";
    
    // Check for all possible admin accounts
    $adminEmails = [
        'johnmichaeleborda79@gmail.com',
        'admin@safekeep.com', 
        'admin@school.edu',
        'johnmichaeleborda79@school.edu'
    ];
    
    foreach ($adminEmails as $email) {
        $stmt = $db->prepare("SELECT id, full_name, email, role, is_active, email_verified, created_at FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<strong>Found Account:</strong><br>";
            echo "ID: " . $user['id'] . "<br>";
            echo "Name: " . htmlspecialchars($user['full_name']) . "<br>";
            echo "Email: " . htmlspecialchars($user['email']) . "<br>";
            echo "Role: " . htmlspecialchars($user['role']) . "<br>";
            echo "Active: " . ($user['is_active'] ? 'YES' : 'NO') . "<br>";
            echo "Email Verified: " . ($user['email_verified'] ? 'YES' : 'NO') . "<br>";
            echo "Created: " . $user['created_at'] . "<br>";
            echo "</div>";
        } else {
            echo "<p>❌ No account found for: " . htmlspecialchars($email) . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Creating/Updating Primary Admin Account</h3>";
    
    // Create or update the primary admin account
    $adminEmail = 'johnmichaeleborda79@gmail.com';
    $adminPassword = 'admin123';
    $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    // Check if admin exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingAdmin) {
        // Update existing admin
        $stmt = $db->prepare("
            UPDATE users 
            SET password_hash = ?, role = 'admin', is_active = 1, email_verified = 1, full_name = ?
            WHERE email = ?
        ");
        $stmt->execute([$adminHash, 'SafeKeep Administrator', $adminEmail]);
        echo "✅ Updated existing admin account<br>";
    } else {
        // Create new admin
        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified, created_at, updated_at) 
            VALUES (?, ?, ?, 'admin', 1, 1, NOW(), NOW())
        ");
        $stmt->execute(['SafeKeep Administrator', $adminEmail, $adminHash]);
        echo "✅ Created new admin account<br>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 15px 0;'>";
    echo "<h4>✅ Admin Login Credentials:</h4>";
    echo "<strong>Email:</strong> " . htmlspecialchars($adminEmail) . "<br>";
    echo "<strong>Password:</strong> " . htmlspecialchars($adminPassword) . "<br>";
    echo "<strong>Role:</strong> Administrator<br>";
    echo "<strong>Status:</strong> Active & Verified<br>";
    echo "</div>";
    
    // Test password verification
    echo "<h3>Password Verification Test</h3>";
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $storedHash = $stmt->fetchColumn();
    
    if ($storedHash && password_verify($adminPassword, $storedHash)) {
        echo "✅ Password verification test PASSED<br>";
    } else {
        echo "❌ Password verification test FAILED<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
</style>
<?php
echo "<h2>Activating Your Gmail Accounts</h2>";

try {
    $host = 'localhost';
    $dbname = 'safekeep_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connected to database successfully!</p>";
    
    // Activate and update passwords for your Gmail accounts
    $accounts = [
        [
            'email' => 'johnmichaeleborda79@gmail.com',
            'password' => 'password123'
        ],
        [
            'email' => 'johnmichaeleborda.education@gmail.com', 
            'password' => 'education123'
        ]
    ];
    
    foreach ($accounts as $account) {
        // Update the account: activate it and set a known password
        $hashedPassword = password_hash($account['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?, 
                is_active = 1, 
                email_verified = 1,
                verification_token = NULL
            WHERE email = ?
        ");
        
        $result = $stmt->execute([$hashedPassword, $account['email']]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Activated and updated: {$account['email']}</p>";
            echo "<p style='margin-left: 20px;'><strong>Password set to:</strong> {$account['password']}</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update: {$account['email']}</p>";
        }
    }
    
    // Show updated user status
    echo "<h3>Updated Account Status:</h3>";
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, is_active, email_verified, last_login 
        FROM users 
        WHERE email IN ('johnmichaeleborda79@gmail.com', 'johnmichaeleborda.education@gmail.com')
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Active</th><th>Verified</th><th>Last Login</th></tr>";
    
    foreach ($users as $user) {
        $activeStatus = $user['is_active'] ? '✅ Yes' : '❌ No';
        $verifiedStatus = $user['email_verified'] ? '✅ Yes' : '❌ No';
        
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>$activeStatus</td>";
        echo "<td>$verifiedStatus</td>";
        echo "<td>{$user['last_login']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3 style='color: green;'>🎉 Your Accounts Are Now Ready!</h3>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 20px 0;'>";
    echo "<h4>Login Credentials:</h4>";
    echo "<p><strong>📧 Email:</strong> johnmichaeleborda79@gmail.com<br>";
    echo "<strong>🔑 Password:</strong> password123</p>";
    echo "<p><strong>📧 Email:</strong> johnmichaeleborda.education@gmail.com<br>";
    echo "<strong>🔑 Password:</strong> education123</p>";
    echo "</div>";
    
    echo "<p style='text-align: center;'>";
    echo "<a href='/safekeep-v2/auth/login.php' style='background: #007bff; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🚀 Login Now</a>";
    echo "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 20px 0; }
    th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>
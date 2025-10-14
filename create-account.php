<?php
echo "<h2>SafeKeep User Account Creation</h2>";

// Try to connect to database
try {
    $host = 'localhost';
    $dbname = 'safekeep_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connected to database successfully!</p>";
    
    // Check existing users
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Existing Users:</h3>";
    if (empty($users)) {
        echo "<p>No users found in database.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Create both Gmail accounts if they don't exist
    $accounts = [
        [
            'email' => 'johnmichaeleborda79@gmail.com',
            'first_name' => 'John Michael',
            'last_name' => 'Eborda',
            'password' => 'password123',
            'student_id' => 'JME2024'
        ],
        [
            'email' => 'johnmichaeleborda.education@gmail.com',
            'first_name' => 'John Michael',
            'last_name' => 'Eborda Education',
            'password' => 'education123',
            'student_id' => 'JMEEDU2024'
        ]
    ];
    
    echo "<h3>Creating/Updating Gmail Accounts:</h3>";
    
    foreach ($accounts as $account) {
        // Check if account exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$account['email']]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // Update password for existing user
            $hashedPassword = password_hash($account['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $result = $stmt->execute([$hashedPassword, $account['email']]);
            
            if ($result) {
                echo "<p style='color: blue;'>🔄 Updated password for {$account['email']}</p>";
                echo "<p><strong>Login with:</strong> {$account['email']} / {$account['password']}</p>";
            }
        } else {
            // Create new user
            $hashedPassword = password_hash($account['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password, role, student_id, created_at) 
                VALUES (?, ?, ?, ?, 'student', ?, NOW())
            ");
            
            $result = $stmt->execute([
                $account['first_name'],
                $account['last_name'],
                $account['email'],
                $hashedPassword,
                $account['student_id']
            ]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Created new account: {$account['email']}</p>";
                echo "<p><strong>Login with:</strong> {$account['email']} / {$account['password']}</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>🎯 Try These Login Credentials:</h3>";
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>Email:</strong> johnmichaeleborda79@gmail.com<br><strong>Password:</strong> password123</p>";
    echo "<p><strong>Email:</strong> johnmichaeleborda.education@gmail.com<br><strong>Password:</strong> education123</p>";
    echo "<p><strong>Email:</strong> admin@safekeep.com<br><strong>Password:</strong> admin123</p>";
    echo "</div>";
    
    echo "<p><a href='/safekeep-v2/auth/login.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔑 Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database Connection Error: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<p>🔧 <strong>Fix:</strong> Check your MySQL credentials in includes/Database.php</p>";
    } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "<p>🔧 <strong>Fix:</strong> Start XAMPP MySQL service</p>";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p>🔧 <strong>Fix:</strong> Create the 'safekeep_db' database in phpMyAdmin</p>";
    }
    
    echo "<p><strong>Quick fixes to try:</strong></p>";
    echo "<ul>";
    echo "<li>Open XAMPP Control Panel and start MySQL</li>";
    echo "<li>Go to <a href='http://localhost/phpmyadmin'>phpMyAdmin</a> and create 'safekeep_db' database</li>";
    echo "<li>Import your database backup if you have one</li>";
    echo "</ul>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 20px 0; }
    th, td { padding: 10px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>
<?php
require_once 'includes/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>SafeKeep Users Database Check</h2>";
    
    // Get all users
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, role, student_id, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p style='color: red;'>❌ No users found in database!</p>";
        echo "<p>Let's create your account...</p>";
        
        // Create your Gmail account
        $email = 'johnmichaeleborda79@gmail.com';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO users (first_name, last_name, email, password, role, student_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'John Michael',
            'Eborda', 
            $email,
            $password,
            'student',
            'JME2024'
        ]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Created account for $email</p>";
            echo "<p><strong>Login credentials:</strong></p>";
            echo "<p>Email: $email<br>Password: password123</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create account</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Found " . count($users) . " users in database:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Student ID</th><th>Created</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['student_id']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if your Gmail account exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['johnmichaeleborda79@gmail.com']);
        $gmailUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$gmailUser) {
            echo "<p style='color: orange;'>⚠️ Your Gmail account (johnmichaeleborda79@gmail.com) is not registered!</p>";
            echo "<p>Creating it now...</p>";
            
            $email = 'johnmichaeleborda79@gmail.com';
            $password = password_hash('password123', PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("
                INSERT INTO users (first_name, last_name, email, password, role, student_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                'John Michael',
                'Eborda', 
                $email,
                $password,
                'student',
                'JME2024'
            ]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Created your Gmail account!</p>";
                echo "<p><strong>Login with:</strong></p>";
                echo "<p>Email: $email<br>Password: password123</p>";
            }
        } else {
            echo "<p style='color: green;'>✅ Your Gmail account exists!</p>";
            echo "<p><strong>Try logging in with:</strong></p>";
            echo "<p>Email: johnmichaeleborda79@gmail.com<br>Password: (try 'password123' or the password you remember setting)</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure XAMPP MySQL is running!</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 20px 0; }
    th, td { padding: 10px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>
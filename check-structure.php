<?php
echo "<h2>SafeKeep Database Structure Check</h2>";

try {
    $host = 'localhost';
    $dbname = 'safekeep_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connected to database successfully!</p>";
    
    // Check what tables exist
    $stmt = $pdo->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables in safekeep_db:</h3>";
    if (empty($tables)) {
        echo "<p style='color: red;'>No tables found in database!</p>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
    // Check users table structure
    if (in_array('users', $tables)) {
        echo "<h3>Users Table Structure:</h3>";
        $stmt = $pdo->prepare("DESCRIBE users");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show actual users data
        echo "<h3>Existing Users:</h3>";
        $stmt = $pdo->prepare("SELECT * FROM users LIMIT 10");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($users)) {
            echo "<p>No users found.</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            // Get column names from first user
            echo "<tr>";
            foreach (array_keys($users[0]) as $column) {
                echo "<th>$column</th>";
            }
            echo "</tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                foreach ($user as $value) {
                    echo "<td>$value</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Now try to create/update your accounts based on actual table structure
        echo "<h3>Creating Your Gmail Accounts:</h3>";
        
        // Check what columns actually exist
        $columnNames = array_column($columns, 'Field');
        
        // Determine the correct column names
        $nameColumn = in_array('name', $columnNames) ? 'name' : 
                     (in_array('username', $columnNames) ? 'username' : 
                     (in_array('first_name', $columnNames) ? 'first_name' : null));
        
        if ($nameColumn) {
            $accounts = [
                [
                    'email' => 'johnmichaeleborda79@gmail.com',
                    'name' => 'John Michael Eborda',
                    'password' => 'password123'
                ],
                [
                    'email' => 'johnmichaeleborda.education@gmail.com',
                    'name' => 'John Michael Eborda Education',
                    'password' => 'education123'
                ]
            ];
            
            foreach ($accounts as $account) {
                // Check if account exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$account['email']]);
                $existingUser = $stmt->fetch();
                
                if ($existingUser) {
                    // Update password
                    $hashedPassword = password_hash($account['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $result = $stmt->execute([$hashedPassword, $account['email']]);
                    
                    if ($result) {
                        echo "<p style='color: blue;'>🔄 Updated password for {$account['email']}</p>";
                    }
                } else {
                    // Create new user with correct column names
                    $hashedPassword = password_hash($account['password'], PASSWORD_DEFAULT);
                    
                    if (in_array('role', $columnNames)) {
                        $stmt = $pdo->prepare("INSERT INTO users ($nameColumn, email, password, role) VALUES (?, ?, ?, 'student')");
                        $result = $stmt->execute([$account['name'], $account['email'], $hashedPassword]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO users ($nameColumn, email, password) VALUES (?, ?, ?)");
                        $result = $stmt->execute([$account['name'], $account['email'], $hashedPassword]);
                    }
                    
                    if ($result) {
                        echo "<p style='color: green;'>✅ Created account: {$account['email']}</p>";
                    }
                }
                
                echo "<p><strong>Login with:</strong> {$account['email']} / {$account['password']}</p>";
            }
        } else {
            echo "<p style='color: red;'>Could not determine name column structure. Manual account creation needed.</p>";
        }
    } else {
        echo "<p style='color: red;'>Users table not found! Need to create it.</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎯 Try These Login Credentials:</h3>";
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>Email:</strong> johnmichaeleborda79@gmail.com<br><strong>Password:</strong> password123</p>";
    echo "<p><strong>Email:</strong> johnmichaeleborda.education@gmail.com<br><strong>Password:</strong> education123</p>";
    echo "</div>";
    
    echo "<p><a href='/safekeep-v2/auth/login.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔑 Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 20px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>
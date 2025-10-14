<?php
echo "<h2>Database Insert Deep Debug</h2>";

if ($_POST) {
    try {
        require_once './includes/db.php';
        $pdo = Database::getConnection();
        
        // First, let's try a minimal insert to see what works
        echo "<h3>Testing Minimal Insert:</h3>";
        
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_logs (post_id, sender_name, sender_email, message, ip_address) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                1,
                'Test Minimal',
                'test@minimal.com',
                'Minimal test message',
                '127.0.0.1'
            ]);
            
            if ($result) {
                $insertId = $pdo->lastInsertId();
                echo "<p style='color: green;'>✅ Minimal insert successful! ID: $insertId</p>";
            } else {
                echo "<p style='color: red;'>❌ Minimal insert failed</p>";
                echo "<pre>";
                print_r($stmt->errorInfo());
                echo "</pre>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Minimal insert error: " . $e->getMessage() . "</p>";
        }
        
        // Now test with sender_user_id
        echo "<h3>Testing with sender_user_id:</h3>";
        
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_logs (post_id, sender_user_id, sender_name, sender_email, message, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                1,
                8, // Your user ID
                'Test With User ID',
                'test@userID.com',
                'Test with user ID message',
                '127.0.0.1'
            ]);
            
            if ($result) {
                $insertId = $pdo->lastInsertId();
                echo "<p style='color: green;'>✅ Insert with user_id successful! ID: $insertId</p>";
            } else {
                echo "<p style='color: red;'>❌ Insert with user_id failed</p>";
                echo "<pre>";
                print_r($stmt->errorInfo());
                echo "</pre>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Insert with user_id error: " . $e->getMessage() . "</p>";
        }
        
        // Let's see what the Database::insert method is actually doing
        echo "<h3>Debugging Database::insert Method:</h3>";
        
        // Enable error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        try {
            // Let's see if we can catch more details
            $contactData = [
                'post_id' => 1,
                'sender_user_id' => 8,
                'sender_name' => 'Debug Test',
                'sender_email' => 'debug@test.com',
                'message' => 'Debug message',
                'ip_address' => '127.0.0.1'
            ];
            
            echo "<p>Attempting Database::insert with data:</p>";
            echo "<pre>";
            print_r($contactData);
            echo "</pre>";
            
            // Try to get more error info by checking the Database class
            $reflection = new ReflectionClass('Database');
            $methods = $reflection->getMethods();
            
            echo "<p>Available Database methods:</p>";
            echo "<ul>";
            foreach ($methods as $method) {
                if ($method->isPublic() && $method->isStatic()) {
                    echo "<li>" . $method->getName() . "()</li>";
                }
            }
            echo "</ul>";
            
            // Try the insert
            $result = Database::insert('contact_logs', $contactData);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Database::insert successful! ID: $result</p>";
            } else {
                echo "<p style='color: red;'>❌ Database::insert returned false/null</p>";
                
                // Check if there's an error log method
                if (method_exists('Database', 'getLastError')) {
                    echo "<p>Last error: " . Database::getLastError() . "</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Database::insert error: " . $e->getMessage() . "</p>";
            echo "<p>Stack trace:</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        
        // Check if there's a specific constraint on sender_user_id
        echo "<h3>Checking User ID Constraint:</h3>";
        
        try {
            $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
            $stmt->execute([8]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<p style='color: green;'>✅ User ID 8 exists: {$user['full_name']}</p>";
            } else {
                echo "<p style='color: red;'>❌ User ID 8 does not exist - this might be the problem!</p>";
                
                // Get your actual user ID
                $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ?");
                $stmt->execute(['johnmichaeleborda79@gmail.com']);
                $actualUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($actualUser) {
                    echo "<p style='color: green;'>✅ Your actual user ID is: {$actualUser['id']} ({$actualUser['full_name']})</p>";
                } else {
                    echo "<p style='color: red;'>❌ Could not find your user record!</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ User check error: " . $e->getMessage() . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ General error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Click the button to run deep database debugging:</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Insert Deep Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h3>Deep Debug Database Insert</h3>
        <form method="POST" action="">
            <button type="submit" class="btn btn-danger">🔍 Run Deep Debug</button>
        </form>
        
        <hr>
        <p><a href="/safekeep-v2/" class="btn btn-secondary">Back to SafeKeep</a></p>
    </div>
</body>
</html>

<style>
    body { font-family: Arial, sans-serif; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px; }
    ul { background: #f8f9fa; padding: 15px; }
</style>
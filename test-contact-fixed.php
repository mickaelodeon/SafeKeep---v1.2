<?php
echo "<h2>Contact Form Debug Test</h2>";

// Check if form was submitted
if ($_POST) {
    echo "<h3>✅ Form Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Test database connection using the Database class
    try {
        require_once './includes/db.php';
        echo "<p style='color: green;'>✅ Database class loaded successfully</p>";
        
        $pdo = Database::getConnection();
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        
        // Test if contact_logs table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'contact_logs'");
        $stmt->execute();
        $tableExists = $stmt->fetch();
        
        if ($tableExists) {
            echo "<p style='color: green;'>✅ contact_logs table exists</p>";
            
            // Test inserting a contact log using Database class methods
            try {
                $contactData = [
                    'post_id' => (int)$_POST['post_id'],
                    'sender_name' => $_POST['sender_name'],
                    'sender_email' => $_POST['sender_email'],
                    'sender_phone' => $_POST['sender_phone'] ?? '',
                    'message' => $_POST['message'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                // Use the Database::insert method if it exists
                if (method_exists('Database', 'insert')) {
                    $result = Database::insert('contact_logs', $contactData);
                    echo "<p style='color: green;'>✅ Contact log inserted using Database::insert() - ID: $result</p>";
                } else {
                    // Fallback to direct PDO
                    $stmt = $pdo->prepare("INSERT INTO contact_logs (post_id, sender_name, sender_email, sender_phone, message, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                    $result = $stmt->execute([
                        $contactData['post_id'],
                        $contactData['sender_name'],
                        $contactData['sender_email'],
                        $contactData['sender_phone'],
                        $contactData['message'],
                        $contactData['created_at']
                    ]);
                    
                    if ($result) {
                        echo "<p style='color: green;'>✅ Contact log inserted successfully (PDO fallback)</p>";
                    } else {
                        echo "<p style='color: red;'>❌ Failed to insert contact log</p>";
                    }
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Insert error: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ contact_logs table does not exist</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    }
    
    // Test email functionality
    try {
        require_once './includes/Email.php';
        echo "<p style='color: green;'>✅ Email class loaded successfully</p>";
        
        // Test config loading for email
        require_once './includes/config.php';
        echo "<p style='color: green;'>✅ Config class loaded successfully</p>";
        
        // Test sending email using the Email::send method
        $result = Email::send(
            $_POST['sender_email'],
            'SafeKeep: Contact Form Test',
            '<h3>Test Email from Contact Form Debug</h3>' .
            '<p><strong>From:</strong> ' . htmlspecialchars($_POST['sender_name']) . '</p>' .
            '<p><strong>Message:</strong> ' . nl2br(htmlspecialchars($_POST['message'])) . '</p>' .
            '<p>This email confirms that the contact form email functionality is working.</p>',
            true
        );
        
        if ($result) {
            echo "<p style='color: green;'>✅ Email sent successfully!</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Email sending failed (but this might be normal if SMTP isn't configured)</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Email error: " . $e->getMessage() . "</p>";
    }
    
    // Test required classes for posts/view.php
    echo "<h3>Testing Required Classes:</h3>";
    
    try {
        require_once './includes/functions.php';
        echo "<p style='color: green;'>✅ Functions.php loaded (contains Security & Utils classes)</p>";
        
        // Test Security class
        if (class_exists('Security')) {
            echo "<p style='color: green;'>✅ Security class available</p>";
        } else {
            echo "<p style='color: red;'>❌ Security class missing</p>";
        }
        
        // Test Utils class
        if (class_exists('Utils')) {
            echo "<p style='color: green;'>✅ Utils class available</p>";
        } else {
            echo "<p style='color: red;'>❌ Utils class missing</p>";
        }
        
        // Test Database class methods
        if (method_exists('Database', 'insert')) {
            echo "<p style='color: green;'>✅ Database::insert() method available</p>";
        } else {
            echo "<p style='color: red;'>❌ Database::insert() method missing</p>";
        }
        
        if (method_exists('Database', 'execute')) {
            echo "<p style='color: green;'>✅ Database::execute() method available</p>";
        } else {
            echo "<p style='color: red;'>❌ Database::execute() method missing</p>";
        }
        
        // Test Config class
        if (class_exists('Config')) {
            echo "<p style='color: green;'>✅ Config class available</p>";
        } else {
            echo "<p style='color: red;'>❌ Config class missing</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Class loading error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>No form data received. Fill out the form below to test:</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contact Form Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h3>Test Contact Form</h3>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Post ID</label>
                <input type="number" class="form-control" name="post_id" value="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Your Name</label>
                <input type="text" class="form-control" name="sender_name" value="Test User" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Your Email</label>
                <input type="email" class="form-control" name="sender_email" value="test@example.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Your Phone (Optional)</label>
                <input type="tel" class="form-control" name="sender_phone" value="123456789">
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" rows="4" required>This is a test message to check if the contact form works properly.</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Test Send Message</button>
        </form>
        
        <hr>
        <p><a href="/safekeep-v2/" class="btn btn-secondary">Back to SafeKeep</a></p>
    </div>
</body>
</html>
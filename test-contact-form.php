<?php
echo "<h2>Contact Form Debug Test</h2>";

// Check if form was submitted
if ($_POST) {
    echo "<h3>✅ Form Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Test database connection
    try {
        require_once './includes/db.php';
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        
        // Test if contact_logs table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'contact_logs'");
        $stmt->execute();
        $tableExists = $stmt->fetch();
        
        if ($tableExists) {
            echo "<p style='color: green;'>✅ contact_logs table exists</p>";
            
            // Test inserting a contact log
            $stmt = $pdo->prepare("INSERT INTO contact_logs (post_id, sender_name, sender_email, sender_phone, message) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([1, 'Test User', 'test@example.com', '123456789', 'Test message']);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Successfully inserted test contact log</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to insert contact log</p>";
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
        
        // Test sending email using basic send method
        $result = Email::send(
            'test@example.com',
            'SafeKeep: Contact Form Test',
            '<h3>Test Email from Contact Form Debug</h3><p>This is a test message to verify email functionality.</p>',
            true
        );
        
        if ($result) {
            echo "<p style='color: green;'>✅ Email sent successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Email sending failed</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Email error: " . $e->getMessage() . "</p>";
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
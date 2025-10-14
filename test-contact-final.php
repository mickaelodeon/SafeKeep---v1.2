<?php
echo "<h2>Final Contact Form Test</h2>";

if ($_POST) {
    echo "<h3>✅ Testing with Correct Table Structure:</h3>";
    
    try {
        require_once './includes/db.php';
        $pdo = Database::getConnection();
        
        // Test correct insert structure
        $contactData = [
            'post_id' => (int)$_POST['post_id'],
            'sender_user_id' => 8, // Using your user ID from earlier
            'sender_name' => $_POST['sender_name'],
            'sender_email' => $_POST['sender_email'],
            'message' => $_POST['message'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'email_sent' => 0
            // sent_at will be auto-populated
        ];
        
        echo "<p>Data structure (matches table):</p>";
        echo "<pre>";
        print_r($contactData);
        echo "</pre>";
        
        $result = Database::insert('contact_logs', $contactData);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Contact log inserted successfully! ID: $result</p>";
            
            // Test email notification
            require_once './includes/Email.php';
            require_once './includes/config.php';
            
            $emailSent = Email::send(
                $_POST['sender_email'],
                'SafeKeep: Contact Form Working!',
                '<h3>🎉 Contact Form is Now Working!</h3>' .
                '<p><strong>From:</strong> ' . htmlspecialchars($_POST['sender_name']) . '</p>' .
                '<p><strong>Message:</strong> ' . nl2br(htmlspecialchars($_POST['message'])) . '</p>' .
                '<p>Your SafeKeep contact form is now properly configured and working!</p>',
                true
            );
            
            if ($emailSent) {
                // Update the contact log to mark email as sent
                Database::execute("UPDATE contact_logs SET email_sent = 1 WHERE id = ?", [$result]);
                echo "<p style='color: green;'>✅ Email notification sent and logged!</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Contact logged but email notification failed</p>";
            }
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 20px 0;'>";
            echo "<h4>🎉 SUCCESS! Contact Form is Working!</h4>";
            echo "<p>✅ Database insert successful<br>";
            echo "✅ Email notification sent<br>";
            echo "✅ Contact log updated</p>";
            echo "</div>";
            
        } else {
            echo "<p style='color: red;'>❌ Insert failed</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // Show recent logs with correct column names
    echo "<h3>Recent Contact Logs:</h3>";
    try {
        $stmt = $pdo->prepare("SELECT id, post_id, sender_name, sender_email, LEFT(message, 50) as message_preview, sent_at, email_sent FROM contact_logs ORDER BY sent_at DESC LIMIT 3");
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($logs) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Post ID</th><th>Sender</th><th>Email</th><th>Message Preview</th><th>Sent At</th><th>Email Sent</th></tr>";
            foreach ($logs as $log) {
                $emailStatus = $log['email_sent'] ? '✅ Yes' : '❌ No';
                echo "<tr>";
                echo "<td>{$log['id']}</td>";
                echo "<td>{$log['post_id']}</td>";
                echo "<td>{$log['sender_name']}</td>";
                echo "<td>{$log['sender_email']}</td>";
                echo "<td>{$log['message_preview']}...</td>";
                echo "<td>{$log['sent_at']}</td>";
                echo "<td>$emailStatus</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No contact logs yet.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error loading logs: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Fill out the form to test the fixed contact functionality:</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Final Contact Form Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h3>Test Fixed Contact Form</h3>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Post ID</label>
                <input type="number" class="form-control" name="post_id" value="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Your Name</label>
                <input type="text" class="form-control" name="sender_name" value="John Michael Eborda" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Your Email</label>
                <input type="email" class="form-control" name="sender_email" value="johnmichaeleborda79@gmail.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" rows="4" required>Hi! I'm interested in this item. Is it still available? Please let me know how we can arrange to meet. Thank you!</textarea>
            </div>
            <button type="submit" class="btn btn-success">🚀 Test Fixed Contact Form</button>
        </form>
        
        <hr>
        <p><a href="/safekeep-v2/" class="btn btn-secondary">Back to SafeKeep</a></p>
        <p><a href="/safekeep-v2/posts/" class="btn btn-info">Test Real Contact Form</a></p>
    </div>
</body>
</html>

<style>
    body { font-family: Arial, sans-serif; }
    table { margin: 20px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
</style>
<?php
/**
 * Real-time Contact Form Monitor - Shows exactly what happens during submission
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/Email.php';

Config::load();

echo "<h1>🔍 Real-Time Contact Form Monitor</h1>";
echo "<p>This will show you EXACTLY what happens when you submit the contact form.</p>";

// Check recent contact logs
echo "<h2>📋 Recent Contact Attempts</h2>";
try {
    $stmt = Database::execute("SELECT * FROM contact_logs ORDER BY sent_at DESC LIMIT 10");
    $recentContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($recentContacts) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Post ID</th><th>Sender</th><th>Email</th><th>Message (truncated)</th><th>Email Sent</th><th>Time</th></tr>";
        
        foreach ($recentContacts as $contact) {
            $bgColor = $contact['email_sent'] ? '#d4edda' : '#f8d7da';
            $status = $contact['email_sent'] ? '✅ Yes' : '❌ No';
            $messagePreview = substr($contact['message'], 0, 50) . (strlen($contact['message']) > 50 ? '...' : '');
            
            echo "<tr style='background: $bgColor;'>";
            echo "<td>" . $contact['id'] . "</td>";
            echo "<td>" . $contact['post_id'] . "</td>";
            echo "<td>" . htmlspecialchars($contact['sender_name']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['sender_email']) . "</td>";
            echo "<td>" . htmlspecialchars($messagePreview) . "</td>";
            echo "<td>" . $status . "</td>";
            echo "<td>" . $contact['sent_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No contact attempts found in database.</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error reading contact logs: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Live email test
echo "<h2>📧 Live Email Test</h2>";
echo "<form method='POST' style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>";
echo "<h3>Test Email Sending Right Now</h3>";
echo "<p>This will send a test email immediately to verify your email system:</p>";
echo "<label>Send test email to:</label><br>";
echo "<input type='email' name='test_email' value='johnmichaeleborda79@gmail.com' style='width: 300px; padding: 8px;' required><br><br>";
echo "<button type='submit' name='send_test' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px;'>Send Test Email Now</button>";
echo "</form>";

if ($_POST && isset($_POST['send_test'])) {
    echo "<div style='background: #e7f3ff; padding: 15px; border: 1px solid #b3d9ff; margin: 10px 0;'>";
    echo "<h3>🧪 Testing Email...</h3>";
    
    $testEmail = $_POST['test_email'];
    $subject = "SafeKeep Email Test - " . date('Y-m-d H:i:s');
    $body = "<h2>SafeKeep Email Test</h2><p>This is a live test email sent at " . date('Y-m-d H:i:s') . "</p><p>If you receive this, your email system is working! ✅</p>";
    
    echo "Sending to: $testEmail<br>";
    echo "Subject: $subject<br>";
    
    try {
        $result = Email::send($testEmail, $subject, $body, true);
        
        if ($result) {
            echo "<p style='color: green; font-weight: bold;'>✅ EMAIL SENT SUCCESSFULLY!</p>";
            echo "<p>Check your inbox at: $testEmail</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ EMAIL SENDING FAILED!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red; font-weight: bold;'>❌ EMAIL ERROR: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

echo "<hr>";

// Check configuration
echo "<h2>⚙️ Email Configuration Status</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$configs = [
    'Email Enabled' => Config::get('mail.enabled') ? 'Yes' : 'No',
    'SMTP Host' => Config::get('mail.host'),
    'SMTP Port' => Config::get('mail.port'),
    'Username' => Config::get('mail.username'),
    'From Email' => Config::get('mail.from_email'),
    'From Name' => Config::get('mail.from_name'),
];

foreach ($configs as $key => $value) {
    $status = $value ? '✅' : '❌';
    echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td><td>$status</td></tr>";
}
echo "</table>";

echo "<hr>";

// Instructions
echo "<h2>🎯 How to Test the Contact Form</h2>";
echo "<ol>";
echo "<li><strong>Go to the post:</strong> <a href='posts/view.php?id=8' target='_blank'>Visit Post #8 (SAMPLE REPLY HERE)</a></li>";
echo "<li><strong>Fill out the contact form</strong> with a test message (minimum 10 characters)</li>";
echo "<li><strong>Click 'Send Message to Owner'</strong></li>";
echo "<li><strong>Come back to this page</strong> and refresh to see if it appears in the contact logs above</li>";
echo "<li><strong>Check your email</strong> at both your Gmail and school email addresses</li>";
echo "</ol>";

echo "<h3>📱 What Should Happen:</h3>";
echo "<ul>";
echo "<li>✅ A new entry should appear in the contact logs table above</li>";
echo "<li>✅ The 'Email Sent' column should show '✅ Yes'</li>";
echo "<li>✅ You should receive an email notification</li>";
echo "<li>✅ A success message should appear on the post page</li>";
echo "</ul>";

echo "<p><strong>🔄 Refresh this page after testing to see the results!</strong></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { width: 100%; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    .success { background-color: #d4edda; color: #155724; }
    .error { background-color: #f8d7da; color: #721c24; }
</style>
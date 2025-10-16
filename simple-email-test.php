<?php
/**
 * Simple Email Test
 * Direct test of the Email class functionality
 */

require_once 'includes/config.php';
require_once 'includes/Email.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Email Test</h1>";

try {
    echo "<h2>Testing Email Configuration</h2>";
    
    Config::load();
    
    echo "Email enabled: " . (Config::get('mail.enabled') ? 'Yes' : 'No') . "<br>";
    echo "Email host: " . Config::get('mail.host') . "<br>";
    echo "Email port: " . Config::get('mail.port') . "<br>";
    echo "Email username: " . Config::get('mail.username') . "<br>";
    echo "Email from: " . Config::get('mail.from_email') . "<br>";
    
    echo "<h2>Sending Test Email</h2>";
    
    $testEmail = Config::get('mail.username'); // Send to the configured email
    $subject = "SafeKeep Email Test - " . date('H:i:s');
    $message = "
    <h2>SafeKeep Email Test</h2>
    <p>This is a test email to verify the SafeKeep email functionality is working.</p>
    <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    <p><strong>Status:</strong> If you receive this email, the system is working correctly! ✅</p>
    ";
    
    echo "Sending to: $testEmail<br>";
    echo "Subject: $subject<br>";
    echo "Sending...<br><br>";
    
    $result = Email::send($testEmail, $subject, $message, true);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
        echo "✅ <strong>SUCCESS!</strong> Email sent successfully!<br>";
        echo "Check your email inbox for the test message.";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        echo "❌ <strong>FAILED!</strong> Email could not be sent.";
        echo "</div>";
    }
    
    echo "<h2>Testing Built-in Email Methods</h2>";
    
    // Test the test email method
    echo "Testing Email::test() method...<br>";
    $testResult = Email::test($testEmail);
    
    if ($testResult) {
        echo "✅ Email::test() method succeeded<br>";
    } else {
        echo "❌ Email::test() method failed<br>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
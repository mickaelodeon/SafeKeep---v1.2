<?php
/**
 * Detailed Email Debugging Tool
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Import PHPMailer classes at the top level
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h2>Detailed Email Debug Test</h2>";

try {
    // Check if vendor/autoload.php exists
    echo "<h3>Checking Dependencies:</h3>";
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        echo "<p>✅ Vendor autoload found</p>";
        require_once __DIR__ . '/vendor/autoload.php';
        
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            echo "<p>✅ PHPMailer class available</p>";
        } else {
            echo "<p>❌ PHPMailer class not found after autoload</p>";
        }
    } else {
        echo "<p>❌ Vendor autoload not found</p>";
        
        // List directory contents
        echo "<p>Root directory contents:</p><ul>";
        $files = scandir(__DIR__);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "<li>" . htmlspecialchars($file) . (is_dir(__DIR__ . '/' . $file) ? '/' : '') . "</li>";
            }
        }
        echo "</ul>";
    }
    
    // Test with detailed PHPMailer debugging
    echo "<h3>Direct PHPMailer Test:</h3>";
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $mail = new PHPMailer(true);
        
        try {
            // Enable verbose debug output
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) {
                echo "<pre style='background: #f5f5f5; padding: 5px; margin: 2px; border-left: 3px solid #007bff;'>DEBUG $level: " . htmlspecialchars($str) . "</pre>";
            };
            
            $mailConfig = Config::get('mail');
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->SMTPSecure = $mailConfig['encryption'];
            $mail->Port = $mailConfig['port'];
            
            echo "<p><strong>SMTP Settings:</strong></p>";
            echo "<ul>";
            echo "<li>Host: " . htmlspecialchars($mailConfig['host']) . "</li>";
            echo "<li>Port: " . htmlspecialchars((string)$mailConfig['port']) . "</li>";
            echo "<li>Encryption: " . htmlspecialchars($mailConfig['encryption']) . "</li>";
            echo "<li>Username: " . htmlspecialchars($mailConfig['username']) . "</li>";
            echo "<li>Password: " . (empty($mailConfig['password']) ? 'NOT SET' : '***SET***') . "</li>";
            echo "</ul>";
            
            // Recipients
            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress('johnmichaeleborda79@gmail.com', 'Test Recipient');
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'SafeKeep SMTP Test - ' . date('Y-m-d H:i:s');
            $mail->Body = '<h2>SMTP Test Successful!</h2><p>This email was sent using PHPMailer with SMTP authentication.</p><p>Time: ' . date('Y-m-d H:i:s') . '</p>';
            $mail->AltBody = 'SMTP Test Successful! This email was sent using PHPMailer. Time: ' . date('Y-m-d H:i:s');
            
            echo "<h4>Attempting to send email...</h4>";
            $mail->send();
            
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 6px; margin: 20px 0;'>";
            echo "<h4>✅ EMAIL SENT SUCCESSFULLY!</h4>";
            echo "<p>The email was sent using PHPMailer with SMTP authentication.</p>";
            echo "<p>Check your inbox (and spam folder) for the test email.</p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 6px; margin: 20px 0;'>";
            echo "<h4>❌ PHPMailer Error:</h4>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Error Info:</strong> " . htmlspecialchars($mail->ErrorInfo) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 6px;'>";
        echo "<h4>⚠️ PHPMailer Not Available</h4>";
        echo "<p>PHPMailer class is not available. This might be why emails are failing.</p>";
        echo "<p>The system will fall back to PHP's built-in mail() function, which may not work properly on Railway.</p>";
        echo "</div>";
        
        // Test built-in mail function
        echo "<h4>Testing built-in mail() function:</h4>";
        $result = mail(
            'johnmichaeleborda79@gmail.com',
            'SafeKeep Fallback Test - ' . date('Y-m-d H:i:s'),
            'This is a test using PHP built-in mail() function.',
            'From: noreply@safekeep.app'
        );
        
        if ($result) {
            echo "<p>✅ Built-in mail() returned true</p>";
        } else {
            echo "<p>❌ Built-in mail() returned false</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 6px;'>";
    echo "<h4>❌ Script Error:</h4>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3, h4 { color: #333; }
pre { overflow-x: auto; }
</style>
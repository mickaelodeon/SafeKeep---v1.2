<?php
/**
 * SendGrid Email Test
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h2>SendGrid Email Test</h2>";

// Check if SendGrid settings are configured
$mailConfig = Config::get('mail');

echo "<h3>Current Configuration:</h3>";
echo "<table style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th style='border: 1px solid #ddd; padding: 8px;'>Setting</th><th style='border: 1px solid #ddd; padding: 8px;'>Value</th></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>Host</td><td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($mailConfig['host']) . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>Port</td><td style='border: 1px solid #ddd; padding: 8px;'>" . $mailConfig['port'] . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>Username</td><td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($mailConfig['username']) ? 'Set' : 'Not Set') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>Password</td><td style='border: 1px solid #ddd; padding: 8px;'>" . (!empty($mailConfig['password']) ? 'Set' : 'Not Set') . "</td></tr>";
echo "<tr><td style='border: 1px solid #ddd; padding: 8px;'>From Email</td><td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($mailConfig['from_email']) . "</td></tr>";
echo "</table>";

// Check if this looks like SendGrid configuration
$isSendGrid = (strpos($mailConfig['host'], 'sendgrid') !== false);
$hasApiKey = ($mailConfig['username'] === 'apikey');

if ($isSendGrid && $hasApiKey) {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 6px; margin: 15px 0;'>";
    echo "<p>✅ SendGrid configuration detected!</p>";
    echo "</div>";
    
    // Test SendGrid connectivity
    echo "<h3>Testing SendGrid Connection:</h3>";
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $mail = new PHPMailer(true);
        
        try {
            // Enable debug for testing
            $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
            $mail->Debugoutput = function($str, $level) {
                echo "<pre style='background: #f5f5f5; padding: 5px; margin: 2px;'>" . htmlspecialchars($str) . "</pre>";
            };
            
            // SendGrid SMTP settings
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $mailConfig['port'];
            
            // Set sender and recipient
            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress('johnmichaeleborda79@gmail.com', 'Test Recipient');
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'SafeKeep SendGrid Test - ' . date('Y-m-d H:i:s');
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #007bff;">✅ SendGrid Email Test Successful!</h2>
                <p>This email was successfully sent using SendGrid SMTP through Railway.</p>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">
                    <h3>Test Details:</h3>
                    <ul>
                        <li><strong>Service:</strong> SendGrid SMTP</li>
                        <li><strong>Host:</strong> ' . htmlspecialchars($mailConfig['host']) . '</li>
                        <li><strong>Port:</strong> ' . $mailConfig['port'] . '</li>
                        <li><strong>Sent at:</strong> ' . date('Y-m-d H:i:s T') . '</li>
                    </ul>
                </div>
                <p style="color: #28a745;"><strong>Contact Owner functionality should now work properly!</strong></p>
            </div>';
            
            $mail->AltBody = 'SendGrid Email Test Successful! Sent at: ' . date('Y-m-d H:i:s');
            
            $mail->send();
            
            echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 6px; margin: 20px 0;'>";
            echo "<h4>🎉 SUCCESS! Email sent via SendGrid!</h4>";
            echo "<p>✅ SendGrid SMTP connection successful</p>";
            echo "<p>✅ Email sent to johnmichaeleborda79@gmail.com</p>";
            echo "<p>✅ Contact Owner functionality should now work</p>";
            echo "<p><strong>Check your inbox (and spam folder) for the test email.</strong></p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 6px; margin: 15px 0;'>";
            echo "<h4>❌ SendGrid Error:</h4>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Error Info:</strong> " . htmlspecialchars($mail->ErrorInfo) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>❌ PHPMailer not available for testing</p>";
    }
    
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 6px; margin: 15px 0;'>";
    echo "<h4>⚠️ SendGrid Not Configured</h4>";
    echo "<p>To use SendGrid, set these Railway environment variables:</p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key</pre>";
    echo "</div>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3 { color: #333; }
table { border-collapse: collapse; }
pre { overflow-x: auto; }
</style>
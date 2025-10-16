<?php
/**
 * Email Debug Script
 * Test email functionality and configuration
 */

require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/Email.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Email Configuration Debug</h1>";

try {
    // Load configuration
    Config::load();
    
    echo "<h2>1. Configuration Check</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
    
    $emailConfig = [
        'MAIL_ENABLED' => Config::get('mail.enabled'),
        'MAIL_HOST' => Config::get('mail.host'),
        'MAIL_PORT' => Config::get('mail.port'),
        'MAIL_ENCRYPTION' => Config::get('mail.encryption'),
        'MAIL_USERNAME' => Config::get('mail.username'),
        'MAIL_PASSWORD' => Config::get('mail.password') ? str_repeat('*', strlen(Config::get('mail.password'))) : 'NOT SET',
        'MAIL_FROM_EMAIL' => Config::get('mail.from_email'),
        'MAIL_FROM_NAME' => Config::get('mail.from_name'),
    ];
    
    foreach ($emailConfig as $key => $value) {
        $status = $value ? '✅' : '❌';
        echo "<tr><td>$key</td><td>$value</td><td>$status</td></tr>";
    }
    echo "</table>";
    
    echo "<h2>2. PHPMailer Check</h2>";
    
    // Check if PHPMailer is available
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "✅ PHPMailer is available<br>";
        
        // Get version if possible
        try {
            $phpmailer = new PHPMailer\PHPMailer\PHPMailer();
            echo "✅ PHPMailer can be instantiated<br>";
        } catch (Exception $e) {
            echo "❌ Error instantiating PHPMailer: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ PHPMailer class not found<br>";
        echo "Checking for composer autoload...<br>";
        
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            echo "✅ Composer autoload found<br>";
        } else {
            echo "❌ Composer autoload not found - Please run 'composer install'<br>";
        }
    }
    
    echo "<h2>3. Email Test</h2>";
    
    if (!Config::get('mail.enabled')) {
        echo "❌ Email is disabled in configuration<br>";
    } else {
        echo "✅ Email is enabled<br>";
        
        // Test email sending
        $testEmail = "johnmichaeleborda79@gmail.com"; // Send to the same email for testing
        echo "Attempting to send test email to: $testEmail<br><br>";
        
        $subject = "SafeKeep Email Test - " . date('Y-m-d H:i:s');
        $body = "
        <h2>SafeKeep Email Test</h2>
        <p>This is a test email from SafeKeep to verify email functionality.</p>
        <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Server:</strong> " . $_SERVER['SERVER_NAME'] ?? 'Unknown' . "</p>
        <p>If you received this email, the email system is working correctly! ✅</p>
        ";
        
        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0;'>";
        echo "<strong>Email Details:</strong><br>";
        echo "To: $testEmail<br>";
        echo "Subject: $subject<br>";
        echo "Format: HTML<br>";
        echo "</div>";
        
        // Send the email
        $result = Email::send($testEmail, $subject, $body, true);
        
        if ($result) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
            echo "✅ <strong>Email sent successfully!</strong><br>";
            echo "Please check your inbox (and spam folder) for the test email.";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
            echo "❌ <strong>Email sending failed!</strong><br>";
            echo "Check the error log for more details.";
            echo "</div>";
        }
    }
    
    echo "<h2>4. Error Log Check</h2>";
    
    // Check recent PHP error log entries
    $errorLog = ini_get('error_log');
    if ($errorLog && file_exists($errorLog)) {
        echo "Error log location: $errorLog<br>";
        
        // Read last 20 lines of error log
        $lines = file($errorLog);
        $recentLines = array_slice($lines, -20);
        
        echo "<div style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; margin: 10px 0; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;'>";
        foreach ($recentLines as $line) {
            if (stripos($line, 'mail') !== false || stripos($line, 'email') !== false || stripos($line, 'smtp') !== false) {
                echo "<span style='background: yellow;'>" . htmlspecialchars($line) . "</span><br>";
            } else {
                echo htmlspecialchars($line) . "<br>";
            }
        }
        echo "</div>";
    } else {
        echo "No error log found or accessible<br>";
    }
    
    echo "<h2>5. Additional Information</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Item</th><th>Value</th></tr>";
    
    $info = [
        'PHP Version' => PHP_VERSION,
        'OpenSSL Version' => OPENSSL_VERSION_TEXT ?? 'Not available',
        'cURL Available' => function_exists('curl_init') ? 'Yes' : 'No',
        'mail() Function' => function_exists('mail') ? 'Yes' : 'No',
        'Date/Time' => date('Y-m-d H:i:s T'),
        'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    ];
    
    foreach ($info as $key => $value) {
        echo "<tr><td>$key</td><td>$value</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
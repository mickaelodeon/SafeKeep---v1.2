<?php
/**
 * Direct Email Test - Minimal test to isolate email issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load PHPMailer if available
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

echo "<h1>Direct Email Test</h1>";

// Test 1: Basic PHP mail() function
echo "<h2>Test 1: PHP mail() function</h2>";
$to = "johnmichaeleborda79@gmail.com";
$subject = "Test from PHP mail() - " . date('H:i:s');
$message = "This is a test email using PHP's built-in mail() function.";
$headers = "From: safekeep@localhost\r\nContent-Type: text/plain; charset=UTF-8";

$result1 = mail($to, $subject, $message, $headers);
echo "PHP mail() result: " . ($result1 ? "✅ Success" : "❌ Failed") . "<br><br>";

// Test 2: Check if PHPMailer is available
echo "<h2>Test 2: PHPMailer availability</h2>";
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    echo "✅ Autoloader found<br>";
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "✅ PHPMailer class available<br>";
        
        // Test 3: Direct PHPMailer test
        echo "<h2>Test 3: Direct PHPMailer</h2>";
        
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'johnmichaeleborda79@gmail.com';
            $mail->Password = 'gyws dssq prnh lkna';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom('johnmichaeleborda79@gmail.com', 'SafeKeep Test');
            $mail->addAddress('johnmichaeleborda79@gmail.com');
            
            // Content
            $mail->isHTML(false);
            $mail->Subject = 'Direct PHPMailer Test - ' . date('H:i:s');
            $mail->Body = 'This is a direct PHPMailer test from SafeKeep debug script.';
            
            $mail->send();
            echo "✅ Direct PHPMailer: Email sent successfully!<br>";
            
        } catch (Exception $e) {
            echo "❌ Direct PHPMailer failed: {$mail->ErrorInfo}<br>";
            echo "Exception: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ PHPMailer class not found<br>";
    }
    
} else {
    echo "❌ Vendor autoloader not found<br>";
}

// Test 4: SafeKeep Email class
echo "<h2>Test 4: SafeKeep Email class</h2>";
try {
    require_once 'includes/config.php';
    require_once 'includes/Email.php';
    
    echo "✅ SafeKeep Email class loaded<br>";
    
    Config::load();
    echo "✅ Configuration loaded<br>";
    
    $subject4 = "SafeKeep Email Class Test - " . date('H:i:s');
    $body4 = "This is a test using the SafeKeep Email class.";
    
    $result4 = Email::send($to, $subject4, $body4, false);
    echo "SafeKeep Email::send() result: " . ($result4 ? "✅ Success" : "❌ Failed") . "<br>";
    
} catch (Exception $e) {
    echo "❌ SafeKeep Email class error: " . $e->getMessage() . "<br>";
}

echo "<h2>Environment Information</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "OpenSSL: " . (extension_loaded('openssl') ? '✅ Loaded' : '❌ Not loaded') . "<br>";
echo "cURL: " . (extension_loaded('curl') ? '✅ Loaded' : '❌ Not loaded') . "<br>";
echo "Mail function: " . (function_exists('mail') ? '✅ Available' : '❌ Not available') . "<br>";
echo "Current time: " . date('Y-m-d H:i:s T') . "<br>";
?>
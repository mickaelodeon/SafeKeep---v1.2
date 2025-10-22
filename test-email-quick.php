<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Email Test - SafeKeep</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 6px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 6px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 6px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 6px; overflow-x: auto; }
        h1 { color: #007bff; }
        h3 { color: #6c757d; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>🚀 Quick Email Test</h1>
    <p>Testing email configuration with timeout protection...</p>

<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

Config::load();

echo "<h3>1. Email Configuration Check</h3>";
echo "<pre>";
echo "MAIL_ENABLED: " . (Config::get('mail.enabled') ? 'true' : 'false') . "\n";
echo "MAIL_HOST: " . Config::get('mail.host') . "\n";
echo "MAIL_PORT: " . Config::get('mail.port') . "\n";
echo "MAIL_USERNAME: " . (Config::get('mail.username') ? '✅ Set' : '❌ Not set') . "\n";
echo "MAIL_PASSWORD: " . (Config::get('mail.password') ? '✅ Set' : '❌ Not set') . "\n";
echo "MAIL_FROM_EMAIL: " . Config::get('mail.from_email') . "\n";
echo "</pre>";

echo "<h3>2. PHPMailer Availability</h3>";
if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "<div class='success'>✅ PHPMailer is available</div>";
} else {
    echo "<div class='error'>❌ PHPMailer not found - will use fallback</div>";
}

echo "<h3>3. Quick Email Send Test (10 second timeout)</h3>";

// Set script timeout
set_time_limit(20);

$testEmail = 'johnmichaeleborda79@gmail.com';

try {
    require_once __DIR__ . '/includes/Email.php';
    
    $startTime = microtime(true);
    
    echo "<div class='info'>📤 Attempting to send test email to: $testEmail</div>";
    
    $subject = "SafeKeep Quick Test - " . date('H:i:s');
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px;'>
        <h2 style='color: #007bff;'>✅ Email Test Successful!</h2>
        <p>This email was sent from SafeKeep at: <strong>" . date('Y-m-d H:i:s T') . "</strong></p>
        <p style='color: #28a745;'>Your contact owner form should now work properly!</p>
    </div>";
    
    $result = Email::send($testEmail, $subject, $body, true);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    if ($result) {
        echo "<div class='success'>✅ Email sent successfully in {$duration} seconds!</div>";
        echo "<p><strong>Check your inbox:</strong> $testEmail</p>";
    } else {
        echo "<div class='error'>❌ Email send failed (took {$duration} seconds)</div>";
        echo "<p>Check error logs for details</p>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h3>4. Recommendations</h3>";
echo "<div class='info'>";
echo "<p><strong>If email is taking too long or timing out:</strong></p>";
echo "<ul>";
echo "<li>✅ <strong>FIXED:</strong> Added 10-second timeout to prevent endless loading</li>";
echo "<li>✅ <strong>FIXED:</strong> Contact form now works even if email fails</li>";
echo "<li>Check Railway environment variables for SendGrid credentials</li>";
echo "<li>Consider using SendGrid API instead of SMTP for better reliability</li>";
echo "</ul>";
echo "</div>";

?>

<hr>
<p style="text-align: center; color: #6c757d;">
    <a href="<?php echo Config::get('app.url'); ?>">← Back to SafeKeep</a>
</p>

</body>
</html>

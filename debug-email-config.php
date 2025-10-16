<?php
/**
 * Email Configuration Debug and Setup Tool
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Email System Debug & Configuration</h2>";

// Check current email configuration
echo "<h3>Current Email Configuration</h3>";
$mailConfig = Config::get('mail');

echo "<table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Setting</th>";
echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Value</th>";
echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Status</th>";
echo "</tr>";

$settings = [
    'Enabled' => ['value' => $mailConfig['enabled'] ? 'true' : 'false', 'status' => $mailConfig['enabled'] ? '✅ Enabled' : '❌ Disabled'],
    'Host' => ['value' => $mailConfig['host'], 'status' => !empty($mailConfig['host']) ? '✅ Set' : '❌ Empty'],
    'Port' => ['value' => $mailConfig['port'], 'status' => $mailConfig['port'] > 0 ? '✅ Set' : '❌ Invalid'],
    'Encryption' => ['value' => $mailConfig['encryption'], 'status' => !empty($mailConfig['encryption']) ? '✅ Set' : '❌ Empty'],
    'Username' => ['value' => !empty($mailConfig['username']) ? '***@gmail.com' : 'Not set', 'status' => !empty($mailConfig['username']) ? '✅ Set' : '❌ Empty'],
    'Password' => ['value' => !empty($mailConfig['password']) ? '***********' : 'Not set', 'status' => !empty($mailConfig['password']) ? '✅ Set' : '❌ Empty'],
    'From Email' => ['value' => $mailConfig['from_email'], 'status' => !empty($mailConfig['from_email']) ? '✅ Set' : '❌ Empty'],
    'From Name' => ['value' => $mailConfig['from_name'], 'status' => !empty($mailConfig['from_name']) ? '✅ Set' : '❌ Empty']
];

foreach ($settings as $name => $setting) {
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 10px; font-weight: bold;'>{$name}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars((string)$setting['value']) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 10px;'>{$setting['status']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check environment variables
echo "<h3>Environment Variables Check</h3>";
$envVars = [
    'MAIL_ENABLED' => $_ENV['MAIL_ENABLED'] ?? $_SERVER['MAIL_ENABLED'] ?? 'Not set',
    'MAIL_HOST' => $_ENV['MAIL_HOST'] ?? $_SERVER['MAIL_HOST'] ?? 'Not set',
    'MAIL_USERNAME' => $_ENV['MAIL_USERNAME'] ?? $_SERVER['MAIL_USERNAME'] ?? 'Not set',
    'MAIL_PASSWORD' => $_ENV['MAIL_PASSWORD'] ?? $_SERVER['MAIL_PASSWORD'] ?? 'Not set'
];

echo "<table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Environment Variable</th>";
echo "<th style='border: 1px solid #ddd; padding: 10px; text-align: left;'>Value</th>";
echo "</tr>";

foreach ($envVars as $key => $value) {
    $displayValue = $value;
    if ($key === 'MAIL_PASSWORD' && $value !== 'Not set') {
        $displayValue = '***********';
    }
    if ($key === 'MAIL_USERNAME' && $value !== 'Not set') {
        $displayValue = '***@gmail.com';
    }
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 10px; font-weight: bold;'>{$key}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($displayValue) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Main issue diagnosis
echo "<h3>🔍 Issue Diagnosis</h3>";

if (!$mailConfig['enabled']) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 6px; margin: 15px 0;'>";
    echo "<h4>❌ Main Issue: Email is DISABLED</h4>";
    echo "<p><strong>Problem:</strong> MAIL_ENABLED is set to 'false' or not set.</p>";
    echo "<p><strong>Solution:</strong> Set MAIL_ENABLED=true in Railway environment variables.</p>";
    echo "</div>";
}

if (empty($mailConfig['username']) || empty($mailConfig['password'])) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 6px; margin: 15px 0;'>";
    echo "<h4>⚠️ Missing Credentials</h4>";
    echo "<p><strong>Problem:</strong> Gmail credentials not configured.</p>";
    echo "<p><strong>Solution:</strong> Set MAIL_USERNAME and MAIL_PASSWORD in Railway environment variables.</p>";
    echo "</div>";
}

// Test email sending if configured
if ($mailConfig['enabled'] && !empty($mailConfig['username']) && !empty($mailConfig['password'])) {
    echo "<h3>📧 Email Test</h3>";
    
    try {
        require_once __DIR__ . '/includes/Email.php';
        
        $testEmail = 'johnmichaeleborda79@gmail.com';
        $subject = 'SafeKeep Email Test - ' . date('Y-m-d H:i:s');
        $body = '<h2>✅ Email Test Successful!</h2>
                 <p>This is a test email from SafeKeep Lost & Found system.</p>
                 <p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>
                 <p>If you received this email, the contact owner functionality should work properly.</p>';
        
        echo "<p>Attempting to send test email to: " . htmlspecialchars($testEmail) . "</p>";
        
        $result = Email::send($testEmail, $subject, $body, true);
        
        if ($result) {
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 6px;'>";
            echo "<h4>✅ Email Test SUCCESSFUL!</h4>";
            echo "<p>Test email was sent successfully. Contact owner functionality should work.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 6px;'>";
            echo "<h4>❌ Email Test FAILED</h4>";
            echo "<p>Email could not be sent. Check credentials and settings.</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 6px;'>";
        echo "<h4>❌ Email Test ERROR</h4>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

// Configuration instructions
echo "<h3>⚙️ How to Fix Email Configuration</h3>";
echo "<div style='background: #d1ecf1; padding: 20px; border: 1px solid #bee5eb; border-radius: 6px;'>";
echo "<h4>Railway Environment Variables Setup:</h4>";
echo "<ol>";
echo "<li><strong>Go to Railway Dashboard</strong> → Your SafeKeep Service → Variables tab</li>";
echo "<li><strong>Add these environment variables:</strong></li>";
echo "<ul style='margin: 10px 0;'>";
echo "<li><code>MAIL_ENABLED=true</code></li>";
echo "<li><code>MAIL_HOST=smtp.gmail.com</code></li>";
echo "<li><code>MAIL_PORT=587</code></li>";
echo "<li><code>MAIL_ENCRYPTION=tls</code></li>";
echo "<li><code>MAIL_USERNAME=your-gmail@gmail.com</code></li>";
echo "<li><code>MAIL_PASSWORD=your-app-password</code></li>";
echo "<li><code>MAIL_FROM_EMAIL=noreply@safekeep.app</code></li>";
echo "<li><code>MAIL_FROM_NAME=SafeKeep Lost & Found</code></li>";
echo "</ul>";
echo "<li><strong>Important:</strong> Use Gmail App Password, not regular password</li>";
echo "<li><strong>Deploy:</strong> Railway will automatically redeploy with new variables</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 6px; margin: 15px 0;'>";
echo "<h4>📱 Gmail App Password Setup:</h4>";
echo "<p>1. Go to your Google Account settings</p>";
echo "<p>2. Security → 2-Step Verification → App passwords</p>";
echo "<p>3. Generate an app password for 'Mail'</p>";
echo "<p>4. Use this 16-character password (not your Gmail password)</p>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3 { color: #333; }
table { border-collapse: collapse; width: 100%; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>
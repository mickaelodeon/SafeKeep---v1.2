<?php
/**
 * Alternative Email Configuration for Railway
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Railway Email Solutions</h2>";

echo "<h3>🚨 Current Issue: Network Connectivity</h3>";
echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 6px; margin: 15px 0;'>";
echo "<p><strong>Problem:</strong> Railway cannot connect to Gmail SMTP (smtp.gmail.com:587)</p>";
echo "<p><strong>Error:</strong> Network is unreachable (101)</p>";
echo "<p><strong>Cause:</strong> Railway's network restrictions or firewall blocking external SMTP</p>";
echo "</div>";

echo "<h3>💡 Solutions</h3>";

echo "<h4>Solution 1: Use Railway's Built-in Mail Service</h4>";
echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 6px; margin: 15px 0;'>";
echo "<p><strong>Recommended:</strong> Railway provides built-in email services</p>";
echo "<ol>";
echo "<li>Go to Railway Dashboard → Add-ons → Email Service</li>";
echo "<li>Configure Railway email service</li>";
echo "<li>Use Railway's SMTP settings instead of Gmail</li>";
echo "</ol>";
echo "</div>";

echo "<h4>Solution 2: Use SendGrid (Free Tier Available)</h4>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 6px; margin: 15px 0;'>";
echo "<p><strong>Alternative:</strong> SendGrid works well with Railway</p>";
echo "<p><strong>Setup:</strong></p>";
echo "<ol>";
echo "<li>Sign up for SendGrid (free 100 emails/day)</li>";
echo "<li>Get API key from SendGrid</li>";
echo "<li>Update Railway environment variables:</li>";
echo "<ul>";
echo "<li><code>MAIL_HOST=smtp.sendgrid.net</code></li>";
echo "<li><code>MAIL_PORT=587</code></li>";
echo "<li><code>MAIL_USERNAME=apikey</code></li>";
echo "<li><code>MAIL_PASSWORD=your-sendgrid-api-key</code></li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

echo "<h4>Solution 3: Use Mailgun</h4>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 6px; margin: 15px 0;'>";
echo "<p><strong>Another option:</strong> Mailgun also works with Railway</p>";
echo "<p><strong>Setup:</strong></p>";
echo "<ol>";
echo "<li>Sign up for Mailgun</li>";
echo "<li>Get SMTP credentials</li>";
echo "<li>Update Railway environment variables:</li>";
echo "<ul>";
echo "<li><code>MAIL_HOST=smtp.mailgun.org</code></li>";
echo "<li><code>MAIL_PORT=587</code></li>";
echo "<li><code>MAIL_USERNAME=your-mailgun-username</code></li>";
echo "<li><code>MAIL_PASSWORD=your-mailgun-password</code></li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

echo "<h4>Solution 4: Temporary Fallback - Disable Email</h4>";
echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 6px; margin: 15px 0;'>";
echo "<p><strong>Quick Fix:</strong> Disable email temporarily</p>";
echo "<p>Set <code>MAIL_ENABLED=false</code> in Railway environment variables.</p>";
echo "<p>The contact form will still work and log messages, but won't send emails.</p>";
echo "</div>";

// Test Railway network connectivity
echo "<h3>🔍 Network Connectivity Test</h3>";

echo "<h4>Testing connectivity to different SMTP servers:</h4>";

$smtpServers = [
    'Gmail' => ['host' => 'smtp.gmail.com', 'port' => 587],
    'SendGrid' => ['host' => 'smtp.sendgrid.net', 'port' => 587],
    'Mailgun' => ['host' => 'smtp.mailgun.org', 'port' => 587],
    'Railway SMTP' => ['host' => 'smtp.railway.app', 'port' => 587]
];

foreach ($smtpServers as $name => $server) {
    echo "<p><strong>Testing {$name}:</strong> ";
    $connection = @fsockopen($server['host'], $server['port'], $errno, $errstr, 5);
    if ($connection) {
        echo "<span style='color: green;'>✅ Reachable</span></p>";
        fclose($connection);
    } else {
        echo "<span style='color: red;'>❌ Unreachable (Error: {$errno} - {$errstr})</span></p>";
    }
}

echo "<h3>📝 Recommended Next Steps</h3>";
echo "<div style='background: #e7f3ff; padding: 20px; border: 1px solid #b8daff; border-radius: 6px; margin: 20px 0;'>";
echo "<h4>🎯 Best Solution: Use SendGrid</h4>";
echo "<p><strong>Why SendGrid:</strong></p>";
echo "<ul>";
echo "<li>✅ Free tier (100 emails/day)</li>";
echo "<li>✅ Reliable delivery</li>";
echo "<li>✅ Works well with Railway</li>";
echo "<li>✅ Simple setup</li>";
echo "</ul>";
echo "<p><strong>Quick Setup:</strong></p>";
echo "<ol>";
echo "<li>Go to <a href='https://signup.sendgrid.com/' target='_blank'>SendGrid Signup</a></li>";
echo "<li>Create account and verify email</li>";
echo "<li>Go to Settings → API Keys → Create API Key</li>";
echo "<li>In Railway, update these environment variables:</li>";
echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 4px;'>";
echo "<code>MAIL_HOST=smtp.sendgrid.net<br>";
echo "MAIL_PORT=587<br>";
echo "MAIL_USERNAME=apikey<br>";
echo "MAIL_PASSWORD=your-sendgrid-api-key</code>";
echo "</div>";
echo "<li>Test again - emails should work!</li>";
echo "</ol>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3, h4 { color: #333; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
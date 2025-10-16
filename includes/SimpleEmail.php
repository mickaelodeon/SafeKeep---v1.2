<?php
/**
 * Simple Email Class - No PHPMailer Required
 * Fallback for when Composer dependencies are missing
 */

declare(strict_types=1);

class SimpleEmail
{
    /**
     * Send email using built-in PHP mail() function
     */
    public static function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $config = Config::get('mail');
        
        // If email is disabled, return true for development
        if (!$config['enabled']) {
            error_log("Email disabled - Would send to: $to, Subject: $subject");
            return true;
        }
        
        // Headers
        $headers = [];
        $headers[] = 'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>';
        $headers[] = 'Reply-To: ' . $config['reply_to'];
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headers[] = 'MIME-Version: 1.0';
        
        if ($isHtml) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        
        // Send email
        $result = mail($to, $subject, $body, implode("\r\n", $headers));
        
        if ($result) {
            error_log("Simple email sent successfully to: $to");
        } else {
            error_log("Simple email failed to send to: $to");
        }
        
        return $result;
    }
    
    /**
     * Test simple email sending
     */
    public static function test(): bool
    {
        $to = 'johnmichaeleborda79@gmail.com';
        $subject = 'SafeKeep Simple Email Test - ' . date('Y-m-d H:i:s');
        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #007bff;">✅ Simple Email Test Successful!</h2>
            <p>This email was sent using PHP built-in mail() function.</p>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <h3>Test Details:</h3>
                <ul>
                    <li><strong>Method:</strong> PHP mail() function</li>
                    <li><strong>Sent at:</strong> ' . date('Y-m-d H:i:s T') . '</li>
                </ul>
            </div>
            <p style="color: #28a745;"><strong>Contact Owner functionality should now work!</strong></p>
        </div>';
        
        return self::send($to, $subject, $body, true);
    }
}
?>
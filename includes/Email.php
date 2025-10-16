<?php
/**
 * Email Utility Class
 * Handles email sending using PHPMailer or built-in mail()
 */

declare(strict_types=1);

// Include Composer autoloader for PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Email
{
    private static array $config;
    private static bool $initialized = false;

    /**
     * Initialize email configuration
     */
    private static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$config = [
            'enabled' => filter_var(Config::get('mail.enabled'), FILTER_VALIDATE_BOOLEAN),
            'host' => Config::get('mail.host'),
            'port' => (int)Config::get('mail.port'),
            'encryption' => Config::get('mail.encryption'),
            'username' => Config::get('mail.username'),
            'password' => Config::get('mail.password'),
            'from_email' => Config::get('mail.from_email'),
            'from_name' => Config::get('mail.from_name'),
            'reply_to' => Config::get('mail.reply_to')
        ];

        self::$initialized = true;
    }

    /**
     * Send an email
     */
    public static function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        self::init();

        // If email is disabled, log and return success for development
        if (!self::$config['enabled']) {
            error_log("Email disabled - Would send to: $to, Subject: $subject");
            return true;
        }

        try {
            // Try to use PHPMailer if available
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                return self::sendWithPHPMailer($to, $subject, $body, $isHtml);
            } else {
                // Fallback to simple email class
                require_once __DIR__ . '/SimpleEmail.php';
                return SimpleEmail::send($to, $subject, $body, $isHtml);
            }
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            
            // Try simple email as final fallback
            try {
                require_once __DIR__ . '/SimpleEmail.php';
                return SimpleEmail::send($to, $subject, $body, $isHtml);
            } catch (Exception $fallbackError) {
                error_log('Simple email fallback also failed: ' . $fallbackError->getMessage());
                return false;
            }
        }
    }

    /**
     * Send email using PHPMailer (preferred method)
     */
    private static function sendWithPHPMailer(string $to, string $subject, string $body, bool $isHtml): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = self::$config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = self::$config['username'];
            $mail->Password = self::$config['password'];
            $mail->SMTPSecure = self::$config['encryption'];
            $mail->Port = self::$config['port'];

            // Recipients
            $mail->setFrom(self::$config['from_email'], self::$config['from_name']);
            $mail->addAddress($to);
            $mail->addReplyTo(self::$config['reply_to'], self::$config['from_name']);

            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $body;

            if ($isHtml) {
                // Create plain text version
                $mail->AltBody = strip_tags($body);
            }

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log('PHPMailer Error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Send email using built-in mail() function (fallback)
     */
    private static function sendWithBuiltInMail(string $to, string $subject, string $body, bool $isHtml): bool
    {
        $headers = [
            'From: ' . self::$config['from_name'] . ' <' . self::$config['from_email'] . '>',
            'Reply-To: ' . self::$config['reply_to'],
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0'
        ];

        if ($isHtml) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Send password reset email
     */
    public static function sendPasswordReset(string $email, string $name, string $resetLink): bool
    {
        $subject = 'Reset Your ' . Config::get('app.name') . ' Password';
        $body = self::getPasswordResetTemplate($name, $resetLink);

        return self::send($email, $subject, $body, true);
    }

    /**
     * Send welcome email to new users
     */
    public static function sendWelcome(string $email, string $name): bool
    {
        $subject = 'Welcome to ' . Config::get('app.name');
        $body = self::getWelcomeTemplate($name);

        return self::send($email, $subject, $body, true);
    }

    /**
     * Test email configuration
     */
    public static function test(string $to): bool
    {
        $subject = 'Test Email from ' . Config::get('app.name');
        $body = self::getTestTemplate();

        return self::send($to, $subject, $body, true);
    }

    /**
     * Password reset email template
     */
    private static function getPasswordResetTemplate(string $name, string $resetLink): string
    {
        $appName = Config::get('app.name');
        $appUrl = Config::get('app.url');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Reset Your Password</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #007cba; }
                .button { display: inline-block; padding: 12px 30px; background-color: #007cba; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .button:hover { background-color: #005a8b; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; text-align: center; }
                .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🛡️ {$appName}</div>
                    <h1>Password Reset Request</h1>
                </div>
                
                <p>Hello <strong>{$name}</strong>,</p>
                
                <p>We received a request to reset your password for your {$appName} account. If you made this request, click the button below to reset your password:</p>
                
                <div style='text-align: center;'>
                    <a href='{$resetLink}' class='button'>Reset My Password</a>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Important:</strong>
                    <ul>
                        <li>This link will expire in <strong>1 hour</strong></li>
                        <li>If you didn't request this reset, please ignore this email</li>
                        <li>Your password will not be changed unless you click the link above</li>
                    </ul>
                </div>
                
                <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                <p style='word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 3px;'>{$resetLink}</p>
                
                <div class='footer'>
                    <p>This email was sent from {$appName} - Lost & Found System</p>
                    <p>If you have questions, please contact your system administrator</p>
                    <p><a href='{$appUrl}'>Visit {$appName}</a></p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Welcome email template
     */
    private static function getWelcomeTemplate(string $name): string
    {
        $appName = Config::get('app.name');
        $appUrl = Config::get('app.url');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Welcome to {$appName}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #28a745; }
                .button { display: inline-block; padding: 12px 30px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🛡️ {$appName}</div>
                    <h1>Welcome!</h1>
                </div>
                
                <p>Hello <strong>{$name}</strong>,</p>
                
                <p>Welcome to {$appName}! Your account has been successfully created.</p>
                
                <p>You can now:</p>
                <ul>
                    <li>Report lost items</li>
                    <li>Post found items</li>
                    <li>Browse and search items</li>
                    <li>Manage your posts</li>
                </ul>
                
                <div style='text-align: center;'>
                    <a href='{$appUrl}' class='button'>Start Using {$appName}</a>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Test email template
     */
    private static function getTestTemplate(): string
    {
        $appName = Config::get('app.name');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Test Email</title>
        </head>
        <body>
            <h1>🧪 Test Email from {$appName}</h1>
            <p>This is a test email to verify that your email configuration is working correctly.</p>
            <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p>If you received this email, your email system is configured properly! ✅</p>
        </body>
        </html>";
    }
}
?>
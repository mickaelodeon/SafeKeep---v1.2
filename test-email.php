<?php
/**
 * Email System Test
 * Test the Gmail SMTP integration and email functionality
 */

declare(strict_types=1);

// Include necessary dependencies
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/Email.php';

// Start session for any session-based functionality
Session::init();

// Test results array
$testResults = [];

// Function to run a test and capture results
function runTest($testName, $testFunction) {
    global $testResults;
    
    try {
        $result = $testFunction();
        $testResults[] = [
            'name' => $testName,
            'status' => 'SUCCESS',
            'message' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        $testResults[] = [
            'name' => $testName,
            'status' => 'FAILED',
            'message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Test 1: Configuration Check
runTest('Email Configuration Check', function() {
    $config = Config::get('mail');
    
    if (!$config['enabled']) {
        throw new Exception('Email is disabled in configuration. Set MAIL_ENABLED=true in .env');
    }
    
    if (empty($config['username']) || $config['username'] === 'your-email@gmail.com') {
        throw new Exception('Gmail username not configured. Update MAIL_USERNAME in .env');
    }
    
    if (empty($config['password']) || $config['password'] === 'your-app-password') {
        throw new Exception('Gmail app password not configured. Update MAIL_PASSWORD in .env');
    }
    
    return "Configuration is valid. SMTP: {$config['host']}:{$config['port']}, User: {$config['username']}";
});

// Test 2: PHPMailer Availability
runTest('PHPMailer Availability', function() {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        throw new Exception('PHPMailer not found. Run: composer install');
    }
    
    return 'PHPMailer is available';
});

// Test 3: SMTP Connection Test
runTest('SMTP Connection Test', function() {
    $config = Config::get('mail');
    
    // Create PHPMailer instance for connection test
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        
        // Test connection without sending
        $mail->SMTPDebug = 0; // Disable debug output
        $mail->Timeout = 10; // 10 second timeout
        
        // This will test the connection
        $mail->smtpConnect();
        $mail->smtpClose();
        
        return 'SMTP connection successful';
    } catch (Exception $e) {
        throw new Exception('SMTP connection failed: ' . $e->getMessage());
    }
});

// Test 4: Password Reset Email Test
runTest('Password Reset Email Test', function() {
    $testEmail = 'johnmichaeleborda79@gmail.com'; // Use your email for testing
    $testName = 'Test User';
    $testLink = Config::get('app.url') . '/auth/reset-password.php?token=test123';
    
    $result = Email::sendPasswordReset($testEmail, $testName, $testLink);
    
    if (!$result) {
        throw new Exception('Password reset email failed to send');
    }
    
    return "Password reset email sent successfully to {$testEmail}";
});

// Test 5: Welcome Email Test
runTest('Welcome Email Test', function() {
    $testEmail = 'johnmichaeleborda79@gmail.com'; // Use your email for testing
    $testName = 'Test User';
    
    $result = Email::sendWelcome($testEmail, $testName);
    
    if (!$result) {
        throw new Exception('Welcome email failed to send');
    }
    
    return "Welcome email sent successfully to {$testEmail}";
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKeep Email Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-success { border-left: 4px solid #28a745; }
        .test-failed { border-left: 4px solid #dc3545; }
        .test-running { border-left: 4px solid #ffc107; }
        
        .status-success { color: #28a745; }
        .status-failed { color: #dc3545; }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-envelope me-2"></i>SafeKeep Email System Test
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <!-- Test Overview -->
                        <div class="alert alert-info mb-4">
                            <h5><i class="fas fa-info-circle me-2"></i>Test Overview</h5>
                            <p class="mb-0">
                                This test will verify that your Gmail SMTP configuration is working correctly 
                                and that SafeKeep can send emails for password resets and user registration.
                            </p>
                        </div>

                        <!-- Configuration Display -->
                        <div class="mb-4">
                            <h5><i class="fas fa-cog me-2"></i>Current Configuration</h5>
                            <div class="code-block">
<?php 
$mailConfig = Config::get('mail');
echo "SMTP Host: " . $mailConfig['host'] . "\n";
echo "SMTP Port: " . $mailConfig['port'] . "\n"; 
echo "SMTP User: " . $mailConfig['username'] . "\n";
echo "SMTP Password: " . str_repeat('*', strlen($mailConfig['password'])) . "\n";
echo "SMTP Encryption: " . $mailConfig['encryption'] . "\n";
echo "Email Enabled: " . ($mailConfig['enabled'] ? 'Yes' : 'No') . "\n";
echo "From Email: " . $mailConfig['from_email'] . "\n";
echo "From Name: " . $mailConfig['from_name'] . "\n";
?>
                            </div>
                        </div>

                        <!-- Test Results -->
                        <h5><i class="fas fa-tasks me-2"></i>Test Results</h5>
                        
                        <?php 
                        $totalTests = count($testResults);
                        $passedTests = count(array_filter($testResults, function($test) { 
                            return $test['status'] === 'SUCCESS'; 
                        }));
                        ?>
                        
                        <div class="alert alert-<?php echo $passedTests === $totalTests ? 'success' : 'warning'; ?> mb-3">
                            <strong>Overall Status:</strong> 
                            <?php echo $passedTests; ?> of <?php echo $totalTests; ?> tests passed
                            <?php if ($passedTests === $totalTests): ?>
                                <i class="fas fa-check-circle ms-2"></i>
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle ms-2"></i>
                            <?php endif; ?>
                        </div>

                        <?php foreach ($testResults as $test): ?>
                        <div class="card mb-3 test-<?php echo strtolower($test['status']); ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php if ($test['status'] === 'SUCCESS'): ?>
                                                <i class="fas fa-check-circle status-success me-2"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle status-failed me-2"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($test['name']); ?>
                                        </h6>
                                        <p class="mb-0 text-muted">
                                            <?php echo htmlspecialchars($test['message']); ?>
                                        </p>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $test['timestamp']; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Manual Test Instructions -->
                        <div class="mt-4">
                            <h5><i class="fas fa-user-check me-2"></i>Manual Verification</h5>
                            <div class="alert alert-secondary">
                                <ol class="mb-0">
                                    <li><strong>Check Your Email Inbox</strong> - Look for test emails from SafeKeep</li>
                                    <li><strong>Test Forgot Password</strong> - Go to <a href="<?php echo Config::get('app.url'); ?>/auth/forgot-password.php" target="_blank">Forgot Password</a> and enter your email</li>
                                    <li><strong>Test Registration</strong> - Create a new account to receive welcome email</li>
                                    <li><strong>Check Spam Folder</strong> - Sometimes emails go to spam initially</li>
                                </ol>
                            </div>
                        </div>

                        <!-- Troubleshooting -->
                        <?php if ($passedTests < $totalTests): ?>
                        <div class="mt-4">
                            <h5><i class="fas fa-tools me-2"></i>Troubleshooting</h5>
                            <div class="alert alert-warning">
                                <h6>Common Issues:</h6>
                                <ul class="mb-0">
                                    <li><strong>SMTP Connection Failed:</strong> Check Gmail App Password and 2FA settings</li>
                                    <li><strong>Authentication Failed:</strong> Verify MAIL_USERNAME and MAIL_PASSWORD in .env</li>
                                    <li><strong>Configuration Error:</strong> Ensure MAIL_ENABLED=true in .env file</li>
                                    <li><strong>PHPMailer Missing:</strong> Run <code>composer install</code> in project directory</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="mt-4 text-center">
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary me-2">
                                <i class="fas fa-redo me-1"></i>Run Tests Again
                            </a>
                            <a href="<?php echo Config::get('app.url'); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-1"></i>Back to SafeKeep
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
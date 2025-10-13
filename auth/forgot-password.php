<?php
/**
 * Forgot Password Page
 * Password reset request functionality
 */

declare(strict_types=1);

// Start output buffering to handle potential redirects
ob_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/Email.php';

// Initialize session first
Session::init();

$pageTitle = 'Forgot Password';
$errors = [];
$success = false;
$email = '';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    Utils::redirect(Config::get('app.url') . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = Security::sanitizeInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        } else {
            $user = User::findByEmail($email);
            
            if ($user) {
                // Generate reset token
                $resetToken = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                try {
                    Database::update(
                        'users',
                        [
                            'reset_token' => $resetToken,
                            'reset_token_expires' => $expiresAt
                        ],
                        'id = ?',
                        [$user['id']]
                    );
                    
                    // Generate reset link
                    $resetLink = Config::get('app.url') . '/auth/reset-password.php?token=' . $resetToken;
                    
                    // Try to send email
                    $emailSent = Email::sendPasswordReset($email, $user['full_name'], $resetLink);
                    
                    // For development - also show the reset link directly if email fails
                    if (!$emailSent) {
                        $_SESSION['reset_link'] = $resetLink;
                        $_SESSION['reset_email'] = $email;
                        $_SESSION['email_failed'] = true;
                    } else {
                        $_SESSION['email_sent'] = true;
                    }
                    
                    // Log the password reset request
                    Utils::logAuditAction($user['id'], 'password_reset_request', 'user', $user['id']);
                    
                    $success = true;
                    
                } catch (Exception $e) {
                    $errors[] = 'Failed to process reset request. Please try again.';
                    error_log('Password reset error: ' . $e->getMessage());
                }
            } else {
                // Always show success to prevent email enumeration
                $success = true;
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark text-center">
                <h4 class="mb-0"><i class="fas fa-key me-2"></i>Reset Password</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    If an account with that email exists, we've sent a password reset link to your inbox.
                </div>
                
                <?php if (isset($_SESSION['reset_link']) && isset($_SESSION['email_failed'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Email Delivery Issue:</strong><br>
                    We couldn't send the reset email automatically. Please use this direct link:<br>
                    <a href="<?php echo $_SESSION['reset_link']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="fas fa-link me-1"></i>Reset Password Now
                    </a>
                    <small class="d-block mt-2 text-muted">
                        Link for: <?php echo htmlspecialchars($_SESSION['reset_email']); ?>
                    </small>
                </div>
                <?php 
                // Clear the session variables after showing
                unset($_SESSION['reset_link'], $_SESSION['reset_email'], $_SESSION['email_failed']);
                ?>
                <?php endif; ?>
                
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary">Return to Login</a>
                </div>
                <?php else: ?>
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <p class="text-muted mb-4">
                    Enter your email address and we'll send you a link to reset your password.
                </p>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email); ?>"
                               required
                               autofocus>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                        </button>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                <small class="text-muted">
                    Remember your password? 
                    <a href="login.php" class="text-decoration-none">Sign in here</a>
                </small>
            </div>
        </div>
        
        <!-- Development Note -->
        <?php if (Config::get('app.env') === 'development'): ?>
        <div class="alert alert-info mt-3">
            <small>
                <i class="fas fa-info-circle me-1"></i>
                <strong>Development Note:</strong> Email functionality not configured. 
                Reset tokens would be generated and stored in database.
            </small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php';
// Flush output buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>
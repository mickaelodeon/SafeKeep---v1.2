<?php
/**
 * Password Reset Page
 * Allow users to set new password with valid reset token
 */

declare(strict_types=1);

// Start output buffering to handle potential redirects
ob_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize session first
Session::init();

$pageTitle = 'Reset Password';
$errors = [];
$success = false;
$validToken = false;
$token = '';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    Utils::redirect(Config::get('app.url') . '/index.php');
}

// Get and validate token from URL
$token = $_GET['token'] ?? '';
if (empty($token)) {
    $errors[] = 'Invalid or missing reset token.';
} else {
    // Check if token exists and is not expired
    $user = Database::selectOne(
        "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()",
        [$token]
    );
    
    if ($user) {
        $validToken = true;
    } else {
        $errors[] = 'Invalid or expired reset token. Please request a new password reset.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate passwords
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        } else {
            // Additional password validation
            $passwordErrors = Security::validatePassword($password);
            if (!empty($passwordErrors)) {
                $errors = array_merge($errors, $passwordErrors);
            } else {
                // Update user password and clear reset token
                try {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    Database::update(
                        'users',
                        [
                            'password_hash' => $hashedPassword,
                            'reset_token' => null,
                            'reset_token_expires' => null,
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        'reset_token = ?',
                        [$token]
                    );
                    
                    // Log the password reset action
                    Utils::logAuditAction($user['id'], 'password_reset', 'user', $user['id']);
                    
                    $success = true;
                    
                } catch (Exception $e) {
                    $errors[] = 'Failed to reset password. Please try again.';
                    error_log('Password reset error: ' . $e->getMessage());
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white text-center">
                <h4 class="mb-0"><i class="fas fa-lock me-2"></i>Reset Password</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Your password has been successfully reset!
                </div>
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login with New Password
                    </a>
                </div>
                
                <?php elseif ($validToken): ?>
                
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
                    Please enter your new password below.
                </p>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-key me-1"></i>New Password
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required
                               minlength="8"
                               autocomplete="new-password">
                        <div class="form-text">
                            Password must be at least 8 characters with uppercase, lowercase, number, and special character.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-key me-1"></i>Confirm New Password
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required
                               minlength="8"
                               autocomplete="new-password">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Reset Password
                        </button>
                    </div>
                </form>
                
                <?php else: ?>
                
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="text-center">
                    <a href="forgot-password.php" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>Request New Reset Link
                    </a>
                    <a href="login.php" class="btn btn-outline-primary ms-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Back to Login
                    </a>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (form && password && confirmPassword) {
        // Real-time password validation
        password.addEventListener('input', function() {
            const value = this.value;
            const isValid = value.length >= 8 &&
                           /[A-Z]/.test(value) &&
                           /[a-z]/.test(value) &&
                           /[0-9]/.test(value) &&
                           /[^A-Za-z0-9]/.test(value);
            
            this.classList.toggle('is-valid', isValid && value.length > 0);
            this.classList.toggle('is-invalid', !isValid && value.length > 0);
        });
        
        // Confirm password validation
        confirmPassword.addEventListener('input', function() {
            const isValid = this.value === password.value && this.value.length > 0;
            this.classList.toggle('is-valid', isValid);
            this.classList.toggle('is-invalid', !isValid && this.value.length > 0);
        });
        
        // Form submission validation
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity() || password.value !== confirmPassword.value) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
});
</script>

<?php 
require_once __DIR__ . '/../includes/footer.php';
// Flush output buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>